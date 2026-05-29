<?php

namespace App\Controller;

use App\Service\JWTService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/media')]
class MediaController extends ApiController
{
    private string $projectDir;
    private array $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    private int $maxSize = 5242880; // 5MB

    public function __construct(JWTService $jwtService, ParameterBagInterface $params)
    {
        parent::__construct($jwtService);
        $this->projectDir = $params->get('kernel.project_dir');
    }

    #[Route('/upload', name: 'api_media_upload', methods: ['POST', 'OPTIONS'])]
    public function upload(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $this->requireRole($request, ['admin', 'student']);

            $uploadsDir = $this->projectDir . '/public/assets/uploads/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

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
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('', name: 'api_media_delete', methods: ['DELETE', 'OPTIONS'])]
    public function delete(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $this->requireRole($request, ['admin', 'student']);

            $payload = $this->getPayload($request);
            $path = $payload['path'] ?? $request->query->get('path');

            if (!$path) {
                return $this->jsonError("Missing file path");
            }

            $uploadsDir = $this->projectDir . '/public/assets/uploads/';
            $cleanPath = str_replace(['..', '\\'], ['', '/'], $path);
            $fullPath = $this->projectDir . '/public/' . ltrim($cleanPath, '/');

            if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath($uploadsDir)) !== 0) {
                return $this->jsonError('File not found', 'NOT_FOUND', 404);
            }

            unlink($fullPath);
            return $this->jsonSuccess(['deleted' => true], 200, 'File deleted successfully');
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }
}
