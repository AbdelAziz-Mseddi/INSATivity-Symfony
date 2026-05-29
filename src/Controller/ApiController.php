<?php

namespace App\Controller;

use App\Service\JWTService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
    protected JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    protected function jsonSuccess($data, int $status = 200, ?string $message = null): JsonResponse
    {
        $response = ['success' => true, 'data' => $data];
        if ($message) {
            $response['message'] = $message;
        }
        return $this->json($response, $status);
    }

    protected function jsonError(string $message, $code = 400, int $status = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], $status);
    }

    protected function authenticate(Request $request): ?array
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        $decoded = $this->jwtService->decode($token);
        return $decoded ? $decoded : null;
    }

    protected function requireRole(Request $request, array $allowedRoles): array
    {
        $user = $this->authenticate($request);

        if (!$user) {
            throw new \Exception('Authentication required. Missing or invalid Bearer token.', 401);
        }

        $role = $user['role'] ?? 'student';
        if (!in_array($role, $allowedRoles, true)) {
            throw new \Exception('Access denied. Insufficient permissions.', 403);
        }

        return $user;
    }

    protected function getPayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        if (empty($payload)) {
            $payload = $request->request->all();
        }
        return $payload;
    }
}
