<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Remplace fetchEvents() pour avoir tous les événements triés
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC')
            ->addOrderBy('e.eventTime', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Remplace getEventsByClub()
     */
    public function findByClubName(string $clubName): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.club', 'c')
            ->where('LOWER(c.name) = LOWER(:clubName)')
            ->setParameter('clubName', $clubName)
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Next event id (MAX(id) + 1), mirroring the legacy backend since the
     * events.id column has no database sequence.
     */
    public function nextId(): int
    {
        $max = (int) $this->createQueryBuilder('e')
            ->select('MAX(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $max + 1;
    }
}
