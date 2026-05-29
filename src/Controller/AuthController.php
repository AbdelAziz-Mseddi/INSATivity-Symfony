<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends ApiController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(JWTService $jwtService, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct($jwtService);
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $payload = $this->getPayload($request);

            $username = trim($payload['username'] ?? '');
            $password = $payload['password'] ?? '';

            if (!$username || !$password) {
                return $this->jsonError('Username and password are required', 'MISSING_CREDENTIALS');
            }

            $user = $this->userRepository->findByEmailOrUsername($username);

            if (!$user || !password_verify($password, $user->getPassword())) {
                return $this->jsonError('Invalid credentials', 'INVALID_CREDENTIALS', 401);
            }

            $userData = [
                'id' => $user->getId(),
                'full_name' => $user->getFullName(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'major' => $user->getMajor(),
                'role' => $user->getRole(),
            ];

            $token = $this->jwtService->encode($userData);

            return $this->jsonSuccess(['token' => $token, 'user' => $userData], 200, 'Login successful');
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $payload = $this->getPayload($request);

            $fullName = trim($payload['fullName'] ?? '');
            $username = trim($payload['username'] ?? '');
            $email = trim($payload['email'] ?? '');
            $major = trim($payload['major'] ?? '');
            $password = $payload['password'] ?? '';

            if (!$fullName || !$username || !$email || !$major || !$password) {
                return $this->jsonError('All fields are required', 'MISSING_FIELDS');
            }
            
            $email = strtolower($email);
            if (!str_contains($email, '@')) {
                $email .= '@insat.ucar.tn';
            }

            if ($this->userRepository->findByEmailOrUsername($username) || $this->userRepository->findOneBy(['email' => $email])) {
                return $this->jsonError('Username or email already exists', 'ALREADY_EXISTS');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $user = new User();
            $user->setFullName($fullName);
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setMajor($major);
            $user->setPasswordHash($passwordHash);
            $user->setRole('student');

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $userData = [
                'id' => $user->getId(),
                'full_name' => $user->getFullName(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'major' => $user->getMajor(),
                'role' => $user->getRole(),
            ];

            $token = $this->jwtService->encode($userData);

            return $this->jsonSuccess(['token' => $token, 'user' => $userData], 201, 'Registration successful');
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET', 'OPTIONS'])]
    public function me(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $user = $this->authenticate($request);
            if (!$user) {
                return $this->jsonError('Authentication required. Invalid or missing token.', 'AUTH_REQUIRED', 401);
            }

            return $this->jsonSuccess(['user' => $user]);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}
