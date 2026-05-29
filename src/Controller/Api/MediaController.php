<?php

namespace App\Controller\Api;

use App\Api\ApiResponse;
use App\Entity\User;
use App\Service\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/media', name: 'api_media_')]
class MediaController extends AbstractController
{
    public function __construct(private readonly MediaService $media) {}

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        if (($deny = $this->denyUnlessManager()) !== null) {
            return $deny;
        }

        $file = $request->files->get('file');
        if (!$file) {
            return ApiResponse::error("Missing 'file' in request", 'MISSING_FILE');
        }

        $prefix = (string) $request->request->get('prefix', '');

        try {
            $data = $this->media->upload($file, $prefix);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 'UPLOAD_FAILED', 400);
        }

        return ApiResponse::success($data, 201, 'File uploaded successfully');
    }

    #[Route('', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        if (($deny = $this->denyUnlessManager()) !== null) {
            return $deny;
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $path = $data['path'] ?? $request->query->get('path');
        if (!$path) {
            return ApiResponse::error('Missing file path', 'MISSING_PATH');
        }

        try {
            $this->media->delete((string) $path);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 'DELETE_FAILED', 400);
        }

        return ApiResponse::success(['deleted' => true], 200, 'File deleted successfully');
    }

    /** Media management is limited to admins and club moderators. */
    private function denyUnlessManager(): ?JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if ($user instanceof User && ($user->isAdmin() || $user->isModerator())) {
            return null;
        }
        return ApiResponse::error('Access denied. Moderators or admins only.', 'FORBIDDEN', 403);
    }
}
