<?php

namespace App\Controller;

use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAllEventsOrdered();
        return $this->render('index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/clubs', name: 'clubs')]
    public function clubs(ClubRepository $clubRepository): Response
    {
        $clubs = $clubRepository->findAll();
        return $this->render('clubs.html.twig', [
            'clubs' => $clubs,
        ]);
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAllEventsOrdered();
        return $this->render('calendar.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event', name: 'event_detail')]
    public function eventDetail(Request $request, EventRepository $eventRepository): Response
    {
        $id = $request->query->get('id');
        $event = $id ? $eventRepository->find((int)$id) : null;

        return $this->render('event.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/club-dashboard', name: 'club_dashboard')]
    public function clubDashboard(Request $request, ClubRepository $clubRepository, EventRepository $eventRepository): Response
    {
        $clubId = $request->query->get('club');
        $club = $clubId ? $clubRepository->find($clubId) : null;
        $events = $clubId ? $eventRepository->findBy(['club' => $club], ['eventDate' => 'DESC']) : [];

        return $this->render('club-dashboard.html.twig', [
            'club' => $club,
            'events' => $events,
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('login.html.twig');
    }

    #[Route('/register', name: 'register')]
    public function register(): Response
    {
        return $this->render('register.html.twig');
    }
}
