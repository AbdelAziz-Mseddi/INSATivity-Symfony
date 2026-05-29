<?php

namespace App\Controller;

use App\Entity\Club;
use App\Repository\ClubRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/clubs')]
class ClubController extends ApiController
{
    private ClubRepository $clubRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JWTService $jwtService,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($jwtService);
        $this->clubRepository = $clubRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'api_clubs_list', methods: ['GET', 'OPTIONS'])]
    public function list(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $clubs = $this->clubRepository->findAll();
            $data = array_map([$this, 'mapClubToArray'], $clubs);
            return $this->jsonSuccess($data);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('/{id}', name: 'api_club_show', methods: ['GET', 'OPTIONS'])]
    public function show(string $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $club = $this->clubRepository->find($id);
            if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);
            
            return $this->jsonSuccess($this->mapClubToArray($club));
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('', name: 'api_club_create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $this->requireRole($request, ['admin']);
            $payload = $this->getPayload($request);

            if (!isset($payload['id'], $payload['name'], $payload['category'], $payload['logo'], $payload['banner'], $payload['description'])) {
                return $this->jsonError('Missing required fields');
            }

            if ($this->clubRepository->find($payload['id'])) {
                return $this->jsonError('Club with this ID already exists');
            }

            $club = new Club();
            $club->setId($payload['id']);
            $club->setName($payload['name']);
            $club->setCategory($payload['category']);
            $club->setLogo($payload['logo']);
            $club->setBanner($payload['banner']);
            $club->setDescription($payload['description']);

            $this->entityManager->persist($club);
            $this->entityManager->flush();

            return $this->jsonSuccess($this->mapClubToArray($club), 201, 'Club created successfully');
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('/{id}', name: 'api_club_update', methods: ['PUT', 'PATCH', 'OPTIONS'])]
    public function update(string $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $this->requireRole($request, ['admin']);

            $club = $this->clubRepository->find($id);
            if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);

            $payload = $this->getPayload($request);

            if (isset($payload['name'])) $club->setName($payload['name']);
            if (isset($payload['category'])) $club->setCategory($payload['category']);
            if (isset($payload['logo'])) $club->setLogo($payload['logo']);
            if (isset($payload['banner'])) $club->setBanner($payload['banner']);
            if (isset($payload['description'])) $club->setDescription($payload['description']);

            $club->setUpdatedAt(new \DateTime());
            $this->entityManager->flush();

            return $this->jsonSuccess($this->mapClubToArray($club), 200, 'Club updated successfully');
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('/{id}', name: 'api_club_delete', methods: ['DELETE', 'OPTIONS'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $this->requireRole($request, ['admin']);

            $club = $this->clubRepository->find($id);
            if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);

            $this->entityManager->remove($club);
            $this->entityManager->flush();

            return $this->jsonSuccess(['id' => $id, 'deleted' => true], 200, 'Club deleted successfully');
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    private function mapClubToArray(Club $club): array
    {
        return [
            'id' => $club->getId(),
            'name' => $club->getName(),
            'category' => $club->getCategory(),
            'logo' => $club->getLogo(),
            'banner' => $club->getBanner(),
            'description' => $club->getDescription(),
        ];
    }
}
