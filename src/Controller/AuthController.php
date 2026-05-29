<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private JWTService $jwtService;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(JWTService $jwtService, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->jwtService = $jwtService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/auth.php', name: 'api_auth')]
    public function handle(Request $request): JsonResponse
    {
        $action = $request->query->get('action');
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            return new JsonResponse(null, 200);
        }

        try {
            if ($method === 'POST' && $action === 'login') {
                $payload = json_decode($request->getContent(), true) ?? [];
                if (empty($payload)) {
                    $payload = $request->request->all();
                }

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

            } elseif ($method === 'POST' && $action === 'register') {
                $payload = json_decode($request->getContent(), true) ?? [];
                if (empty($payload)) {
                    $payload = $request->request->all();
                }

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

            } elseif ($method === 'GET' && $action === 'me') {
                $user = $this->authenticate($request);
                if (!$user) {
                    return $this->jsonError('Authentication required. Invalid or missing token.', 'AUTH_REQUIRED', 401);
                }

                return $this->jsonSuccess(['user' => $user]);

            } else {
                return $this->jsonError('Unsupported action', 'BAD_REQUEST');
            }
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'SERVER_ERROR', 500);
        }
    }

    private function authenticate(Request $request): ?array
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $decoded = $this->jwtService->decode($token);
        return $decoded ? $decoded : null;
    }

    private function jsonSuccess($data, int $status = 200, ?string $message = null): JsonResponse
    {
        $response = ['success' => true, 'data' => $data];
        if ($message) {
            $response['message'] = $message;
        }
        return $this->json($response, $status);
    }

    private function jsonError(string $message, $code = 400, int $status = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], $status);
    }
}
