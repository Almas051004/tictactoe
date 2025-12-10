<?php

namespace App\Entity;

use App\Repository\MoveRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV6;

#[ORM\Entity(repositoryClass: MoveRepository::class)]
#[ORM\Table(name: 'move')]
#[ORM\Index(columns: ['game_id', 'created_at'], name: 'idx_move_game_time')]
class Move
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private UuidV6 $id;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'moves')]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $player;

    #[ORM\Column(type: 'integer')]
    private int $position;

    #[ORM\Column(type: 'string', length: 1)]
    private string $symbol;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer')]
    private int $moveNumber;

    public function __construct(Game $game, User $player, int $position, string $symbol, int $moveNumber)
    {
        $this->id = new UuidV6();
        $this->game = $game;
        $this->player = $player;
        $this->position = $position;
        $this->symbol = $symbol;
        $this->moveNumber = $moveNumber;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getPlayer(): User
    {
        return $this->player;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getMoveNumber(): int
    {
        return $this->moveNumber;
    }
}
