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

    public function findByName(string $name): ?User
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.lastActivityAt IS NOT NULL')
            ->andWhere('u.lastActivityAt > :fiveMinutesAgo')
            ->setParameter('fiveMinutesAgo', new \DateTimeImmutable('-5 minutes'))
            ->orderBy('u.lastActivityAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
