<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use App\Service\JWTService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events')]
class EventController extends ApiController
{
    private EventRepository $eventRepository;
    private ClubRepository $clubRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        JWTService $jwtService,
        EventRepository $eventRepository,
        ClubRepository $clubRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($jwtService);
        $this->eventRepository = $eventRepository;
        $this->clubRepository = $clubRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'api_events_list', methods: ['GET', 'OPTIONS'])]
    public function list(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $clubName = $request->query->get('club');
            
            if ($clubName) {
                $events = $this->eventRepository->getEventsByClubName($clubName);
            } else {
                $events = $this->eventRepository->findAllEventsOrdered();
            }
            
            $data = array_map([$this, 'mapEventToArray'], $events);
            return $this->jsonSuccess($data);
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('/{id}', name: 'api_event_show', methods: ['GET', 'OPTIONS'])]
    public function show(int $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        try {
            $event = $this->eventRepository->find($id);
            if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);
            
            return $this->jsonSuccess($this->mapEventToArray($event));
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    #[Route('', name: 'api_event_create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        if ($csrfError = $this->verifyCsrf($request)) return $csrfError;

        try {
            $this->requireRole($request, ['admin', 'student']);
            $payload = $this->getPayload($request);

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
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('/{id}', name: 'api_event_update', methods: ['PUT', 'PATCH', 'OPTIONS'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        if ($csrfError = $this->verifyCsrf($request)) return $csrfError;

        try {
            $this->requireRole($request, ['admin', 'student']);

            $event = $this->eventRepository->find($id);
            if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);

            $payload = $this->getPayload($request);

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
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('/{id}/approve', name: 'api_event_approve', methods: ['PATCH', 'OPTIONS'])]
    public function approve(int $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        if ($csrfError = $this->verifyCsrf($request)) return $csrfError;

        try {
            $this->requireRole($request, ['admin']);

            $event = $this->eventRepository->find($id);
            if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);

            $event->setIsApproved(true);
            $this->entityManager->flush();
            return $this->jsonSuccess($this->mapEventToArray($event), 200, 'Event approved successfully');
        } catch (\Throwable $e) {
            $status = in_array($e->getCode(), [401, 403, 404]) ? $e->getCode() : 500;
            return $this->jsonError($e->getMessage(), 'SERVER_ERROR', $status);
        }
    }

    #[Route('/{id}', name: 'api_event_delete', methods: ['DELETE', 'OPTIONS'])]
    public function delete(int $id, Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') return new JsonResponse(null, 200);

        if ($csrfError = $this->verifyCsrf($request)) return $csrfError;

        try {
            $this->requireRole($request, ['admin']);

            $event = $this->eventRepository->find($id);
            if (!$event) return $this->jsonError('Event not found', 'NOT_FOUND', 404);

            $this->entityManager->remove($event);
            $this->entityManager->flush();

            return $this->jsonSuccess(['id' => $id, 'deleted' => true], 200, 'Event deleted successfully');
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
}
