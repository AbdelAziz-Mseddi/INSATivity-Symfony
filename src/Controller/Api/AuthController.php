<?php

namespace App\Controller\Api;

use App\Api\ApiResponse;
use App\Api\Serializer;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    /**
     * Login is handled by the json_login firewall (see security.yaml) and its
     * success/failure handlers. This controller is only reached if the firewall
     * is misconfigured.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return ApiResponse::error('Authentication handler not invoked.', 'LOGIN_MISCONFIGURED', 401);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $users,
        EntityManagerInterface $em
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true) ?? [];
        $fullName = trim($data['fullName'] ?? '');
        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $major    = trim($data['major'] ?? '');
        $password = $data['password'] ?? '';

        if (!$fullName || !$username || !$email || !$major || !$password) {
            return ApiResponse::error('All fields are required', 'MISSING_FIELDS');
        }

        $email = strtolower($email);
        if (!str_contains($email, '@')) {
            $email .= '@insat.ucar.tn';
        }

        if ($users->findByEmailOrUsername($username) || $users->findByEmailOrUsername($email)) {
            return ApiResponse::error('User with this email or username already exists', 'CONFLICT', 409);
        }

        $user = (new User())
            ->setUsername($username)
            ->setEmail($email)
            ->setFullName($fullName)
            ->setMajor($major)
            ->setRole('student');
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return ApiResponse::success(['user' => Serializer::user($user)], 201, 'Registration successful');
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ApiResponse::error('Not authenticated', 'AUTH_MISSING', 401);
        }

        return ApiResponse::success(['user' => Serializer::user($user)]);
    }
}
