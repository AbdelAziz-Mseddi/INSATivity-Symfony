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

    public function findAllEventsOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC')
            ->addOrderBy('e.eventTime', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getEventsByClubName(string $clubName): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.club', 'c')
            ->where('LOWER(c.name) = LOWER(:clubName)')
            ->setParameter('clubName', trim($clubName))
            ->orderBy('e.eventDate', 'DESC')
            ->addOrderBy('e.eventTime', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getNextEventId(): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('MAX(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
        return ((int)$result) + 1;
    }
}
