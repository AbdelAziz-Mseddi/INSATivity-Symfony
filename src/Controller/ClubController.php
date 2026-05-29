<?php

namespace App\Controller;

use App\Entity\Club;
use App\Repository\ClubRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ClubController extends AbstractController
{
    private JWTService $jwtService;
    private ClubRepository $clubRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JWTService $jwtService,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->jwtService = $jwtService;
        $this->clubRepository = $clubRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/clubs.php', name: 'api_clubs')]
    public function handle(Request $request): JsonResponse
    {
        $action = $request->query->get('action');
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            return new JsonResponse(null, 200);
        }

        try {
            switch ($method) {
                case 'GET':
                    if ($action === 'get') {
                        $id = $request->query->get('id');
                        if (!$id) return $this->jsonError('Missing club ID');
                        
                        $club = $this->clubRepository->find($id);
                        if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);
                        
                        return $this->jsonSuccess($this->mapClubToArray($club));
                    } elseif ($action === 'getAll') {
                        $clubs = $this->clubRepository->findAll();
                        $data = array_map([$this, 'mapClubToArray'], $clubs);
                        return $this->jsonSuccess($data);
                    } else {
                        return $this->jsonError('Unsupported GET action');
                    }

                case 'POST':
                    $this->requireRole($request, ['admin']);
                    $payload = json_decode($request->getContent(), true) ?? [];
                    if (empty($payload)) {
                        $payload = $request->request->all();
                    }

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

                case 'PUT':
                case 'PATCH':
                    $this->requireRole($request, ['admin']);
                    $id = $request->query->get('id');
                    if (!$id) return $this->jsonError('Missing club ID');

                    $club = $this->clubRepository->find($id);
                    if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);

                    $payload = json_decode($request->getContent(), true) ?? [];
                    if (empty($payload)) {
                        $payload = $request->request->all();
                    }

                    if (isset($payload['name'])) $club->setName($payload['name']);
                    if (isset($payload['category'])) $club->setCategory($payload['category']);
                    if (isset($payload['logo'])) $club->setLogo($payload['logo']);
                    if (isset($payload['banner'])) $club->setBanner($payload['banner']);
                    if (isset($payload['description'])) $club->setDescription($payload['description']);

                    $club->setUpdatedAt(new \DateTime());
                    $this->entityManager->flush();

                    return $this->jsonSuccess($this->mapClubToArray($club), 200, 'Club updated successfully');

                case 'DELETE':
                    $this->requireRole($request, ['admin']);
                    $id = $request->query->get('id');
                    if (!$id) return $this->jsonError('Missing club ID');

                    $club = $this->clubRepository->find($id);
                    if (!$club) return $this->jsonError('Club not found', 'NOT_FOUND', 404);

                    $this->entityManager->remove($club);
                    $this->entityManager->flush();

                    return $this->jsonSuccess(['id' => $id, 'deleted' => true], 200, 'Club deleted successfully');

                default:
                    return $this->jsonError('Unsupported HTTP method');
            }
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    private function requireRole(Request $request, array $allowedRoles): array
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

        $role = $user['role'] ?? 'student';
        if (!in_array($role, $allowedRoles, true)) {
            throw new \Exception('Access denied. Insufficient permissions.', 403);
        }

        return $user;
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
