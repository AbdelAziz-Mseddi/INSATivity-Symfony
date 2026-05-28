<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
       return $this->json(['error' => 'Erreur de configuration du login ou identifiants invalides.'], 401);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true) ?? [];
        $fullName = trim($data['fullName'] ?? '');
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $major    = trim($data['major'] ?? '');
        $password = $data['password'] ?? '';

        if (!$fullName || !$username || !$email || !$major || !$password) {
            return $this->json([
                'success' => false,
                'error'   => 'All fields are required',
                'code'    => 'MISSING_FIELDS',
            ], 400);
        }

        $email = strtolower($email);
        if (!str_contains($email, '@')) {
            $email .= '@insat.ucar.tn';
        }

        $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'error' => 'Username already exists'
            ], 409);
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        if (method_exists($user, 'setFullName')) { $user->setFullName($fullName); }
        if (method_exists($user, 'setMajor')) { $user->setMajor($major); }


        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User registered successfully'
        ], 201);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'roles'    => $user->getRoles()
        ]);
    }
}
