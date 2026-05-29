<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    private JWTService $jwtService;
    private EventRepository $eventRepository;
    private ClubRepository $clubRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JWTService $jwtService,
        EventRepository $eventRepository,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->jwtService = $jwtService;
        $this->eventRepository = $eventRepository;
        $this->clubRepository = $clubRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/events.php', name: 'api_events')]
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
                        if (!$id) return $this->jsonError('Missing event ID');
                        
                        $event = $this->eventRepository->find((int)$id);
                        if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);
                        
                        return $this->jsonSuccess($this->mapEventToArray($event));
                    } elseif ($action === 'getAll') {
                        $events = $this->eventRepository->findAllEventsOrdered();
                        $data = array_map([$this, 'mapEventToArray'], $events);
                        return $this->jsonSuccess($data);
                    } elseif ($action === 'getByClub') {
                        $clubName = $request->query->get('club');
                        if (!$clubName) return $this->jsonError('Missing club name');
                        
                        $events = $this->eventRepository->getEventsByClubName($clubName);
                        $data = array_map([$this, 'mapEventToArray'], $events);
                        return $this->jsonSuccess($data);
                    } else {
                        return $this->jsonError('Unsupported GET action');
                    }

                case 'POST':
                    $this->requireRole($request, ['admin', 'student']);
                    $payload = json_decode($request->getContent(), true) ?? [];
                    if (empty($payload)) {
                        $payload = $request->request->all();
                    }

                    if (!isset($payload['title'], $payload['club'], $payload['date'], $payload['time'], $payload['location'], $payload['description'])) {
                        return $this->jsonError('Missing required fields');
                    }

                    $club = $this->resolveClub($payload['club']);
                    if (!$club) return $this->jsonError('Invalid club name');

                    $eventId = $this->eventRepository->getNextEventId();
                    
                    $event = new Event();
                    $event->setId((string)$eventId);
                    $event->setClub($club);
                    $event->setTitle($payload['title']);
                    $event->setImage($payload['image'] ?? '');
                    $event->setEventDate(new \DateTime($payload['date']));
                    $event->setEventTime(new \DateTime($payload['time']));
                    $event->setLocation($payload['location']);
                    $event->setDescription($payload['description']);
                    $event->setParticipants((int)($payload['participants'] ?? 0));
                    $event->setMaxParticipants((int)($payload['maxParticipants'] ?? 0));
                    $event->setFeatured(!empty($payload['featured']));
                    $event->setIsApproved(false);

                    $this->entityManager->persist($event);
                    $this->entityManager->flush();

                    return $this->jsonSuccess($this->mapEventToArray($event), 201, 'Event created successfully');

                case 'PUT':
                case 'PATCH':
                    $this->requireRole($request, ['admin', 'student']);
                    $id = $request->query->get('id');
                    if (!$id) return $this->jsonError('Missing event ID');

                    $event = $this->eventRepository->find((int)$id);
                    if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);

                    if ($action === 'approve') {
                        $this->requireRole($request, ['admin']);
                        $event->setIsApproved(true);
                        $this->entityManager->flush();
                        return $this->jsonSuccess($this->mapEventToArray($event), 200, 'Event approved successfully');
                    }

                    $payload = json_decode($request->getContent(), true) ?? [];
                    if (empty($payload)) {
                        $payload = $request->request->all();
                    }

                    if (isset($payload['club'])) {
                        $club = $this->resolveClub($payload['club']);
                        if (!$club) return $this->jsonError('Invalid club name');
                        $event->setClub($club);
                    }
                    if (isset($payload['title'])) $event->setTitle($payload['title']);
                    if (isset($payload['image'])) $event->setImage($payload['image']);
                    if (isset($payload['date'])) $event->setEventDate(new \DateTime($payload['date']));
                    if (isset($payload['time'])) $event->setEventTime(new \DateTime($payload['time']));
                    if (isset($payload['location'])) $event->setLocation($payload['location']);
                    if (isset($payload['description'])) $event->setDescription($payload['description']);
                    if (isset($payload['participants'])) $event->setParticipants((int)$payload['participants']);
                    if (isset($payload['maxParticipants'])) $event->setMaxParticipants((int)$payload['maxParticipants']);
                    if (isset($payload['featured'])) $event->setFeatured((bool)$payload['featured']);

                    $event->setUpdatedAt(new \DateTime());
                    $this->entityManager->flush();

                    return $this->jsonSuccess($this->mapEventToArray($event), 200, 'Event updated successfully');

                case 'DELETE':
                    $this->requireRole($request, ['admin']);
                    $id = $request->query->get('id');
                    if (!$id) return $this->jsonError('Missing event ID');

                    $event = $this->eventRepository->find((int)$id);
                    if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);

                    $this->entityManager->remove($event);
                    $this->entityManager->flush();

                    return $this->jsonSuccess(['id' => (int)$id, 'deleted' => true], 200, 'Event deleted successfully');

                default:
                    return $this->jsonError('Unsupported HTTP method');
            }
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    private function resolveClub(string $clubName)
    {
        return $this->clubRepository->createQueryBuilder('c')
            ->where('LOWER(c.name) = LOWER(:name)')
            ->setParameter('name', trim($clubName))
            ->getQuery()
            ->getOneOrNullResult();
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

    private function mapEventToArray(Event $event): array
    {
        return [
            'id' => (int)$event->getId(),
            'title' => $event->getTitle(),
            'club' => $event->getClub() ? $event->getClub()->getName() : '',
            'clubLogo' => $event->getClub() ? $event->getClub()->getLogo() : '',
            'image' => $event->getImage(),
            'date' => $event->getEventDate() ? $event->getEventDate()->format('Y-m-d') : '',
            'time' => $event->getEventTime() ? $event->getEventTime()->format('H:i') : '',
            'location' => $event->getLocation(),
            'description' => $event->getDescription(),
            'participants' => $event->getParticipants(),
            'maxParticipants' => $event->getMaxParticipants(),
            'featured' => $event->isFeatured(),
            'is_approved' => $event->isApproved(),
            'status' => $event->getStatus(),
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
