<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/clubs', name: 'api_clubs_')]
class ClubController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        // TODO (Doctrine): return $this->json($clubRepository->findAll());
        return $this->json(['message' => 'Get all clubs — awaiting Doctrine']);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        // TODO (Doctrine):
        // $club = $clubRepository->find($id);
        // if (!$club) return $this->json(['error' => 'Club not found'], 404);
        // return $this->json($club);

        return $this->json(['message' => "Get club $id — awaiting Doctrine"]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data)) {
            return $this->json(['error' => 'Request body is empty', 'code' => 'MISSING_DATA'], 400);
        }

        // TODO (Doctrine):
        // $club = new Club(); ... persist + flush
        // return $this->json($club, 201);

        return $this->json(['message' => 'Create club — awaiting Doctrine'], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];

        // TODO (Doctrine):
        // $club = $clubRepository->find($id);
        // if (!$club) return $this->json(['error' => 'Club not found'], 404);
        // ... update fields + flush
        // return $this->json($club);

        return $this->json(['message' => "Update club $id — awaiting Doctrine"]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // TODO (Doctrine):
        // $club = $clubRepository->find($id);
        // if (!$club) return $this->json(['error' => 'Club not found'], 404);
        // $entityManager->remove($club); $entityManager->flush();

        return $this->json(['id' => $id, 'deleted' => true], 200);
    }
}
