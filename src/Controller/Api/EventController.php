<?php

namespace App\Controller\Api;

use App\Api\ApiResponse;
use App\Api\Serializer;
use App\Entity\Club;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $events,
        private readonly ClubRepository $clubs,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $club = $request->query->get('club');
        $list = $club
            ? $this->events->findByClubName($club)
            : $this->events->findAllOrdered();

        return ApiResponse::success(Serializer::events($list));
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $event = $this->events->find($id);
        if (!$event) {
            return ApiResponse::error('Event not found', 'NOT_FOUND', 404);
        }
        return ApiResponse::success(Serializer::event($event));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach (['title', 'club', 'date', 'time', 'location', 'description'] as $field) {
            if (empty($data[$field])) {
                return ApiResponse::error('Missing required fields', 'MISSING_FIELDS');
            }
        }

        $club = $this->resolveClub($data['club']);
        if (!$club) {
            return ApiResponse::error('Invalid club name', 'INVALID_CLUB');
        }

        if (!$this->canManageClub($club)) {
            return ApiResponse::error("Access denied. You can only manage your own club's events.", 'FORBIDDEN', 403);
        }

        $event = new Event();
        $event->setId((string) $this->events->nextId());
        $event->setClub($club);
        $this->applyPayload($event, $data);
        $event->setIsApproved(false);

        $this->em->persist($event);
        $this->em->flush();

        return ApiResponse::success(Serializer::event($event), 201, 'Event created successfully');
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->events->find($id);
        if (!$event) {
            return ApiResponse::error('Event not found', 'NOT_FOUND', 404);
        }

        if (!$this->canManageClub($event->getClub())) {
            return ApiResponse::error("Access denied. You can only manage your own club's events.", 'FORBIDDEN', 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Allow moving the event to another club (admins only, in practice).
        if (!empty($data['club'])) {
            $club = $this->resolveClub($data['club']);
            if (!$club) {
                return ApiResponse::error('Invalid club name', 'INVALID_CLUB');
            }
            if (!$this->canManageClub($club)) {
                return ApiResponse::error("Access denied. You can only manage your own club's events.", 'FORBIDDEN', 403);
            }
            $event->setClub($club);
        }

        $this->applyPayload($event, $data);
        $event->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return ApiResponse::success(Serializer::event($event), 200, 'Event updated successfully');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function approve(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // only the admin approves pending events

        $event = $this->events->find($id);
        if (!$event) {
            return ApiResponse::error('Event not found', 'NOT_FOUND', 404);
        }

        $event->setIsApproved(true);
        $event->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return ApiResponse::success(Serializer::event($event), 200, 'Event approved successfully');
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN'); // only the admin deletes events

        $event = $this->events->find($id);
        if (!$event) {
            return ApiResponse::error('Event not found', 'NOT_FOUND', 404);
        }

        $this->em->remove($event);
        $this->em->flush();

        return ApiResponse::success(['id' => $id, 'deleted' => true], 200, 'Event deleted successfully');
    }

    // ---- helpers ----

    private function resolveClub(string $name): ?Club
    {
        return $this->clubs->findOneByNameCI(trim($name));
    }

    /** Admin (any club) or the moderator of the given club. */
    private function canManageClub(?Club $club): bool
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User || !$club) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        return $user->getClubId() !== null
            && strtolower($user->getClubId()) === strtolower((string) $club->getId());
    }

    private function applyPayload(Event $event, array $data): void
    {
        if (isset($data['title']))           $event->setTitle((string) $data['title']);
        if (isset($data['image']))           $event->setImage((string) $data['image']);
        if (isset($data['location']))        $event->setLocation((string) $data['location']);
        if (isset($data['description']))     $event->setDescription((string) $data['description']);
        if (isset($data['participants']))    $event->setParticipants((int) $data['participants']);
        if (isset($data['maxParticipants'])) $event->setMaxParticipants((int) $data['maxParticipants']);
        if (isset($data['featured']))        $event->setFeatured((bool) $data['featured']);
        if (isset($data['tags']) && is_array($data['tags'])) $event->setTags($data['tags']);

        if (!empty($data['date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['date']);
            if ($date) $event->setEventDate($date);
        }
        if (!empty($data['time'])) {
            $time = \DateTime::createFromFormat('H:i', $data['time'])
                ?: \DateTime::createFromFormat('H:i:s', $data['time']);
            if ($time) $event->setEventTime($time);
        }

        // image is NOT NULL — default to empty string when creating without one
        if ($event->getImage() === null) {
            $event->setImage('');
        }
    }
}
