<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $club = $request->query->get('club');

        if ($club) {
            // TODO (Doctrine): return $this->json($eventRepository->findByClub($club));
            return $this->json(['message' => "Get events by club '$club' — awaiting Doctrine"]);
        }

        // TODO (Doctrine): return $this->json($eventRepository->findAll());
        return $this->json(['message' => 'Get all events — awaiting Doctrine']);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        // TODO (Doctrine):
        // $event = $eventRepository->find($id);
        // if (!$event) return $this->json(['error' => 'Event not found'], 404);
        // return $this->json($event);

        return $this->json(['message' => "Get event $id — awaiting Doctrine"]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $data = json_decode($request->getContent(), true) ?? [];
        if (empty($data)) {
            return $this->json(['error' => 'Request body is empty', 'code' => 'MISSING_DATA'], 400);
        }

        // TODO (Doctrine):
        // $event = new Event(); ... persist + flush
        // return $this->json($event, 201);

        return $this->json(['message' => 'Create event — awaiting Doctrine'], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_STUDENT');

        $data = json_decode($request->getContent(), true) ?? [];

        // TODO (Doctrine):
        // $event = $eventRepository->find($id);
        // if (!$event) return $this->json(['error' => 'Event not found'], 404);
        // ... update fields + flush
        // return $this->json($event);

        return $this->json(['message' => "Update event $id — awaiting Doctrine"]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function approve(int $id): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // TODO (Doctrine):
        // $event = $eventRepository->find($id);
        // if (!$event) return $this->json(['error' => 'Event not found'], 404);
        // $event->setApproved(true); $entityManager->flush();
        // return $this->json($event);

        return $this->json(['message' => "Approve event $id — awaiting Doctrine/Security"]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        // TODO (Security): $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // TODO (Doctrine):
        // $event = $eventRepository->find($id);
        // if (!$event) return $this->json(['error' => 'Event not found'], 404);
        // $entityManager->remove($event); $entityManager->flush();

        return $this->json(['id' => $id, 'deleted' => true], 200);
    }
}
