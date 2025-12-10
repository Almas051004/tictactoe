<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findWaitingGames(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->setParameter('status', Game::STATUS_WAITING)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveGames(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.status = :status')
            ->setParameter('status', Game::STATUS_ACTIVE)
            ->orderBy('g.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findGamesByUser(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('(g.creatorUser = :userId OR g.opponentUser = :userId)')
            ->setParameter('userId', $user->getId(), 'uuid') 
            
            ->andWhere('g.status IN (:statuses)')
            ->setParameter('statuses', [
                Game::STATUS_ACTIVE, 
                Game::STATUS_WAITING, 
                Game::STATUS_FINISHED
            ])
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFinishedGames(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('g')
            ->where('(g.creatorUser = :user OR g.opponentUser = :user)')
            ->setParameter('user', $user)
            ->andWhere('g.status = :status')
            ->setParameter('status', Game::STATUS_FINISHED)
            ->orderBy('g.finishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentGames(int $limit = 5): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}