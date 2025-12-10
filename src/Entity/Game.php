<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV6;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
#[ORM\Index(columns: ['status'], name: 'idx_game_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_game_created_at')]
class Game
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidV6 $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdGames')]
    #[ORM\JoinColumn(nullable: false)]
    private User $creatorUser;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'joinedGames')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $opponentUser = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = self::STATUS_WAITING;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $winner = null;

    #[ORM\Column(type: 'string', length: 1, nullable: true)]
    private ?string $currentTurn = null;

    #[ORM\Column(type: 'text')]
    private string $boardState = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Move::class, cascade: ['persist', 'remove'])]
    private Collection $moves;

    public function __construct(User $creator)
    {
        $this->id = new UuidV6();
        $this->creatorUser = $creator;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->boardState = json_encode(array_fill(0, 9, null));
        $this->moves = new ArrayCollection();
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getCreatorUser(): User
    {
        return $this->creatorUser;
    }

    public function getOpponentUser(): ?User
    {
        return $this->opponentUser;
    }

    public function setOpponentUser(User $opponent): self
    {
        $this->opponentUser = $opponent;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        
        if ($status === self::STATUS_ACTIVE && $this->startedAt === null) {
            $this->startedAt = new \DateTimeImmutable();
            $this->currentTurn = 'X';
        }
        
        if ($status === self::STATUS_FINISHED && $this->finishedAt === null) {
            $this->finishedAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getWinner(): ?string
    {
        return $this->winner;
    }

    public function setWinner(?string $winner): self
    {
        $this->winner = $winner;
        return $this;
    }

    public function getCurrentTurn(): ?string
    {
        return $this->currentTurn;
    }

    public function setCurrentTurn(string $turn): self
    {
        $this->currentTurn = $turn;
        return $this;
    }

    public function toggleTurn(): self
    {
        $this->currentTurn = $this->currentTurn === 'X' ? 'O' : 'X';
        return $this;
    }

    public function getBoardState(): array
    {
        return json_decode($this->boardState, true) ?? array_fill(0, 9, null);
    }

    public function setBoardState(array $board): self
    {
        $this->boardState = json_encode($board);
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Move>
     */
    public function getMoves(): Collection
    {
        return $this->moves;
    }

    public function addMove(Move $move): self
    {
        if (!$this->moves->contains($move)) {
            $this->moves->add($move);
            $move->setGame($this);
        }
        return $this;
    }

    public function getUserSymbol(User $user): ?string
    {
        if ($this->creatorUser->getId() === $user->getId()) {
            return 'X';
        }
        
        if ($this->opponentUser && $this->opponentUser->getId() === $user->getId()) {
            return 'O';
        }
        
        return null;
    }

    public function getOpponentOf(User $user): ?User
    {
        if ($this->creatorUser->getId() === $user->getId()) {
            return $this->opponentUser;
        }
        
        if ($this->opponentUser && $this->opponentUser->getId() === $user->getId()) {
            return $this->creatorUser;
        }
        
        return null;
    }
}
