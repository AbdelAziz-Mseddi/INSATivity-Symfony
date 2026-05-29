<?php

namespace App\Controller;

use App\Service\JWTService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MediaController extends AbstractController
{
    private JWTService $jwtService;
    private string $projectDir;
    private array $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    private int $maxSize = 5242880; // 5MB

    public function __construct(JWTService $jwtService, ParameterBagInterface $params)
    {
        $this->jwtService = $jwtService;
        $this->projectDir = $params->get('kernel.project_dir');
    }

    #[Route('/media.php', name: 'api_media')]
    public function handle(Request $request): JsonResponse
    {
        $action = $request->query->get('action');
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            return new JsonResponse(null, 200);
        }

        try {
            $this->authenticate($request);

            $uploadsDir = $this->projectDir . '/public/assets/uploads/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            if ($method === 'POST' && $action === 'upload') {
                $file = $request->files->get('file');
                if (!$file) {
                    return $this->jsonError("Missing 'file' in request");
                }

                if ($file->getSize() > $this->maxSize) {
                    return $this->jsonError('File too large');
                }

                $mime = $file->getMimeType();
                if (!isset($this->allowedTypes[$mime])) {
                    return $this->jsonError('Invalid file type');
                }

                $prefix = $request->request->get('prefix', '');
                $ext = $this->allowedTypes[$mime];
                $name = ($prefix ? $prefix . '_' : '') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

                try {
                    $file->move($uploadsDir, $name);
                } catch (FileException $e) {
                    return $this->jsonError('Upload failed: ' . $e->getMessage());
                }

                return $this->jsonSuccess(['path' => '../assets/uploads/' . $name], 201, 'File uploaded successfully');

            } elseif ($method === 'DELETE' && $action === 'delete') {
                $payload = json_decode($request->getContent(), true) ?? [];
                $path = $payload['path'] ?? $request->query->get('path');

                if (!$path) {
                    return $this->jsonError("Missing file path");
                }

                $cleanPath = str_replace(['..', '\\'], ['', '/'], $path);
                $fullPath = $this->projectDir . '/public/' . ltrim($cleanPath, '/');

                if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath($uploadsDir)) !== 0) {
                    return $this->jsonError('File not found', 'NOT_FOUND', 404);
                }

                unlink($fullPath);
                return $this->jsonSuccess(['deleted' => true], 200, 'File deleted successfully');

            } else {
                return $this->jsonError('Unsupported action or method');
            }

        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    private function authenticate(Request $request): array
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new \Exception('Authentication required. Missing Bearer token.', 401);
        }

        $token = $matches[1];
        $user = $this->jwtService->decode($token);

        if (!$user) {
            throw new \Exception('Invalid or expired token.', 401);
        }

        return $user;
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
