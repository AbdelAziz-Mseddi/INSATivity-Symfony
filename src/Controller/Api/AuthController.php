<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true) ?? [];
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (!$username || !$password) {
            return $this->json([
                'success' => false,
                'error'   => 'Username and password are required',
                'code'    => 'MISSING_CREDENTIALS',
            ], 400);
        }

        // TODO (Doctrine):  $user = $userRepository->findByEmailOrUsername($username);
        // TODO (Security):  verify password, generate + return JWT token

        return $this->json(['message' => 'Login endpoint ready'], 200);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
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

        // TODO (Doctrine):  create + persist new User entity
        // TODO (Security):  hash password before persisting, generate + return JWT token

        return $this->json(['message' => 'Register endpoint ready'], 201);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // TODO (Security): firewall protects this route
        // $user = $this->getUser();
        // if (!$user) return $this->json(['error' => 'Not authenticated'], 401);
        // return $this->json(['user' => $user->toArray()]);

        return $this->json(['message' => 'Me endpoint ready']);
    }
}
