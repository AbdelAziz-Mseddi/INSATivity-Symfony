<?php

namespace App\Controller\Frontend;

use App\Api\Serializer;
use App\Entity\User;
use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Serves the Twig pages. The events feed (home) is loaded client-side from the
 * JSON API; the other pages are server-rendered with data from the repositories.
 * Route names match the path('...') calls used in the templates.
 */
class PageController extends AbstractController
{
    public function __construct(
        private readonly ClubRepository $clubs,
        private readonly EventRepository $events,
    ) {}

    #[Route('/', name: 'home', methods: ['GET'])]
    #[Route('/index.html', name: 'events', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/calendar.html', name: 'calendar', methods: ['GET'])]
    public function calendar(): Response
    {
        $upcoming = array_values(array_filter(
            $this->events->findAllOrdered(),
            fn ($e) => $e->getStatus() === 'published'
        ));

        return $this->render('calendar.html.twig', [
            'currentMonth'   => date('F'),
            'currentYear'    => date('Y'),
            'selectedDay'    => 'Select a day',
            'upcomingEvents' => Serializer::events($upcoming),
            'dayEvents'      => [],
        ]);
    }

    #[Route('/clubs.html', name: 'clubs', methods: ['GET'])]
    public function clubsPage(): Response
    {
        return $this->render('clubs.html.twig', [
            'clubs' => Serializer::clubs($this->clubs->findAllOrdered()),
        ]);
    }

    #[Route('/event.html', name: 'event', methods: ['GET'])]
    public function event(Request $request): Response
    {
        $id = $request->query->get('id');
        $entity = $id ? $this->events->find((int) $id) : null;
        // Fall back to the most recent event so the page always renders.
        $entity ??= $this->events->findAllOrdered()[0] ?? null;

        $event = $entity ? Serializer::event($entity) : [
            'title' => 'Event not found', 'club' => '', 'image' => '',
            'date' => '', 'time' => '', 'location' => '', 'description' => '',
            'participants' => 0, 'maxParticipants' => 0,
        ];

        return $this->render('event.html.twig', ['event' => $event]);
    }

    #[Route('/club-dashboard.html', name: 'club_dashboard', methods: ['GET'])]
    public function clubDashboard(Request $request): Response
    {
        $clubId = $request->query->get('club');
        $club = $clubId ? $this->clubs->find($clubId) : null;
        $club ??= $this->clubs->findAllOrdered()[0] ?? null;

        if (!$club) {
            return $this->render('club-dashboard.html.twig', [
                'club' => ['name' => 'No club', 'initials' => '?', 'banner' => '', 'description' => '', 'tags' => [], 'participants' => 0, 'venue' => '—'],
                'pendingEvents' => [], 'historyEvents' => [], 'doneEvents' => [],
                'pendingStatus' => '0 awaiting review', 'doneStatus' => '0 reviewed',
            ]);
        }

        $clubEvents = $this->events->findByClubName($club->getName());
        $pending  = array_values(array_filter($clubEvents, fn ($e) => !$e->isApproved()));
        $finished = array_values(array_filter($clubEvents, fn ($e) => $e->isApproved() && $e->getStatus() === 'finished'));
        $participants = array_sum(array_map(fn ($e) => $e->getParticipants(), $clubEvents));

        return $this->render('club-dashboard.html.twig', [
            'club' => [
                'name'         => $club->getName(),
                'initials'     => strtoupper(substr((string) $club->getName(), 0, 2)),
                'banner'       => $club->getBanner(),
                'description'  => $club->getDescription(),
                'tags'         => array_filter([$club->getCategory(), strtoupper((string) $club->getId()), 'INSAT']),
                'participants' => $participants,
                'venue'        => ($clubEvents[0] ?? null)?->getLocation() ?? 'Various locations',
            ],
            'pendingEvents' => Serializer::events($pending),
            'historyEvents' => Serializer::events($finished),
            'doneEvents'    => Serializer::events($finished),
            'pendingStatus' => count($pending) . ' awaiting review',
            'doneStatus'    => count($finished) . ' reviewed',
        ]);
    }

    #[Route('/login.html', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authUtils): Response
    {
        // POST is intercepted by the form_login firewall; this only renders the
        // page (GET) and surfaces the last authentication error, if any.
        return $this->render('login.html.twig', [
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/register.html', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('GET')) {
            return $this->render('register.html.twig', ['error' => null]);
        }

        $fullName = trim((string) $request->request->get('fullName'));
        $username = trim((string) $request->request->get('username'));
        $email    = strtolower(trim((string) $request->request->get('email')));
        $major    = trim((string) $request->request->get('major'));
        $password = (string) $request->request->get('password');
        $confirm  = (string) $request->request->get('confirmPassword');
        $accept   = $request->request->get('acceptTerms');

        $fail = fn (string $msg) => $this->render('register.html.twig', ['error' => $msg]);

        if (!$fullName || !$username || !$email || !$major || !$password) {
            return $fail('All fields are required.');
        }
        if ($password !== $confirm) {
            return $fail('Passwords do not match.');
        }
        if (!$accept) {
            return $fail('You must accept the terms.');
        }
        if (!str_contains($email, '@')) {
            $email .= '@insat.ucar.tn';
        }
        if ($users->findByEmailOrUsername($username) || $users->findByEmailOrUsername($email)) {
            return $fail('User with this email or username already exists.');
        }

        $user = (new User())
            ->setUsername($username)
            ->setEmail($email)
            ->setFullName($fullName)
            ->setMajor($major)
            ->setRole('student');
        $user->setPassword($hasher->hashPassword($user, $password));
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_login');
    }

    /** Intercepted by the logout firewall (see security.yaml); never executed. */
    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This is handled by the logout key on the firewall.');
    }
}
