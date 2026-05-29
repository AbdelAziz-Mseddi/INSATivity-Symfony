<?php

namespace App\Controller\Api;

use App\Api\ApiResponse;
use App\Api\Serializer;
use App\Entity\Club;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/clubs', name: 'api_clubs_')]
class ClubController extends AbstractController
{
    public function __construct(
        private readonly ClubRepository $clubs,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function getAll(): JsonResponse
    {
        return ApiResponse::success(Serializer::clubs($this->clubs->findAllOrdered()));
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $club = $this->clubs->find($id);
        if (!$club) {
            return ApiResponse::error('Club not found', 'NOT_FOUND', 404);
        }
        return ApiResponse::success(Serializer::club($club));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];
        foreach (['id', 'name', 'category', 'description'] as $field) {
            if (empty($data[$field])) {
                return ApiResponse::error('Missing required fields', 'MISSING_FIELDS');
            }
        }

        $id = trim((string) $data['id']);
        if ($this->clubs->find($id)) {
            return ApiResponse::error('Club ID or name already exists', 'CONFLICT', 409);
        }

        $club = (new Club())
            ->setId($id)
            ->setName(trim((string) $data['name']))
            ->setCategory(trim((string) $data['category']))
            ->setDescription(trim((string) $data['description']))
            ->setBanner(trim((string) ($data['banner'] ?? '')))
            ->setLogo(trim((string) ($data['logo'] ?? "../assets/images/{$id}/profile.jpg")));

        $this->em->persist($club);
        $this->em->flush();

        return ApiResponse::success(Serializer::club($club), 201, 'Club created successfully');
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $club = $this->clubs->find($id);
        if (!$club) {
            return ApiResponse::error('Club not found', 'NOT_FOUND', 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['name']))        $club->setName((string) $data['name']);
        if (isset($data['category']))    $club->setCategory((string) $data['category']);
        if (isset($data['description'])) $club->setDescription((string) $data['description']);
        if (isset($data['banner']))      $club->setBanner((string) $data['banner']);
        if (isset($data['logo']))        $club->setLogo((string) $data['logo']);
        $club->setUpdatedAt(new \DateTime());

        $this->em->flush();

        return ApiResponse::success(Serializer::club($club), 200, 'Club updated successfully');
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $club = $this->clubs->find($id);
        if (!$club) {
            return ApiResponse::error('Club not found', 'NOT_FOUND', 404);
        }

        $this->em->remove($club);
        $this->em->flush();

        return ApiResponse::success(['id' => $id, 'deleted' => true], 200, 'Club deleted successfully');
    }
}
