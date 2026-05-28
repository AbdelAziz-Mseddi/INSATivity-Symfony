<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/media', name: 'api_media_')]
class MediaController extends AbstractController
{
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => "Missing 'file' in request", 'code' => 'MISSING_FILE'], 400);
        }

        $prefix = $request->request->get('prefix', '');

        // TODO: $data = $mediaService->upload($file, $prefix);
        // return $this->json($data, 201);

        return $this->json([
            'message'  => 'Upload endpoint ready — awaiting Media service',
            'filename' => $file->getClientOriginalName(),
            'prefix'   => $prefix,
        ], 201);
    }

    #[Route('', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true) ?? [];
        $path = $data['path'] ?? $request->query->get('path');

        if (!$path) {
            return $this->json(['error' => 'Missing file path', 'code' => 'MISSING_PATH'], 400);
        }

        // TODO: $mediaService->delete($path);

        return $this->json(['deleted' => true, 'path' => $path], 200);
    }
}
