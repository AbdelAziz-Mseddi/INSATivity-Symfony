<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Remplace la méthode findByEmailOrUsername de ton PHP natif
     */
    public function findByEmailOrUsername(string $login): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :login')
            ->orWhere('u.email = :login')
            ->setParameter('login', $login)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
