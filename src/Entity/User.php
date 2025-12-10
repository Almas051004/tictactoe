<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV6;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidV6 $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\OneToMany(mappedBy: 'creatorUser', targetEntity: Game::class)]
    private Collection $createdGames;

    #[ORM\OneToMany(mappedBy: 'opponentUser', targetEntity: Game::class)]
    private Collection $joinedGames;

    public function __construct()
    {
        $this->id = new UuidV6();
        $this->createdAt = new \DateTimeImmutable();
        $this->createdGames = new ArrayCollection();
        $this->joinedGames = new ArrayCollection();
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    public function updateActivity(): self
    {
        $this->lastActivityAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getCreatedGames(): Collection
    {
        return $this->createdGames;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getJoinedGames(): Collection
    {
        return $this->joinedGames;
    }

    public function isActive(): bool
    {
        if ($this->lastActivityAt === null) {
            return false;
        }
        
        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->lastActivityAt);
        
        return $interval->i < 5;
    }
}
