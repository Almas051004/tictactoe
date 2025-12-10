<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;

class GameService
{
    /**
     * Check if a position is valid (0-8) and empty
     */
    public function isValidMove(Game $game, int $position, User $player): bool
    {
        if ($position < 0 || $position > 8) {
            error_log("Move Error: Position $position out of bounds");
            return false;
        }
        $playerSymbol = $game->getUserSymbol($player);
        $currentTurn = $game->getCurrentTurn();

        error_log(sprintf(
            "Move Debug: PlayerID=%s, Symbol=%s, CurrentTurn=%s", 
            $player->getId(), 
            $playerSymbol ?? 'NULL', 
            $currentTurn ?? 'NULL'
        ));

        if ($playerSymbol !== $currentTurn) {
            return false;
        }

        $board = $game->getBoardState();
        if ($board[$position] !== null) {
            return false;
        }
        return $board[$position] === null;
    }

    /**
     * Apply a move to the board
     */
    public function applyMove(Game $game, int $position, User $player): Move
    {
        $symbol = $game->getUserSymbol($player);
        $board = $game->getBoardState();
        $moveNumber = count($game->getMoves()) + 1;

        $board[$position] = $symbol;
        $game->setBoardState($board);

        $move = new Move($game, $player, $position, $symbol, $moveNumber);
        $game->addMove($move);

        $game->toggleTurn();

        return $move;
    }

    /**
     * Check for win condition
     */
    public function checkWin(array $board): ?string
    {
        $winPatterns = [
            // Rows
            [0, 1, 2],
            [3, 4, 5],
            [6, 7, 8],
            // Columns
            [0, 3, 6],
            [1, 4, 7],
            [2, 5, 8],
            // Diagonals
            [0, 4, 8],
            [2, 4, 6],
        ];

        foreach ($winPatterns as $pattern) {
            $values = array_map(fn($pos) => $board[$pos], $pattern);
            
            if ($values[0] !== null && $values[0] === $values[1] && $values[1] === $values[2]) {
                return $values[0];
            }
        }

        return null;
    }

    /**
     * Check if board is full (draw)
     */
    public function isBoardFull(array $board): bool
    {
        return !in_array(null, $board, true);
    }

    /**
     * Get game result
     */
    public function getGameResult(Game $game): ?string
    {
        $board = $game->getBoardState();
        
        $winner = $this->checkWin($board);
        if ($winner) {
            return $winner;
        }

        if ($this->isBoardFull($board)) {
            return 'draw';
        }

        return null;
    }

    /**
     * Format move data for response
     */
    public function formatMoveData(Move $move): array
    {
        return [
            'id' => (string) $move->getId(),
            'position' => $move->getPosition(),
            'symbol' => $move->getSymbol(),
            'playerName' => $move->getPlayer()->getName(),
            'moveNumber' => $move->getMoveNumber(),
            'createdAt' => $move->getCreatedAt()->format('c'),
        ];
    }

    /**
     * Format game data for response
     */
    public function formatGameData(Game $game, ?User $currentUser = null): array
    {
        $data = [
            'id' => (string) $game->getId(),
            'status' => $game->getStatus(),
            'creator' => [
                'id' => (string) $game->getCreatorUser()->getId(),
                'name' => $game->getCreatorUser()->getName(),
            ],
            'opponent' => $game->getOpponentUser() ? [
                'id' => (string) $game->getOpponentUser()->getId(),
                'name' => $game->getOpponentUser()->getName(),
            ] : null,
            'boardState' => $game->getBoardState(),
            'currentTurn' => $game->getCurrentTurn(),
            'winner' => $game->getWinner(),
            'createdAt' => $game->getCreatedAt()->format('c'),
            'startedAt' => $game->getStartedAt()?->format('c'),
            'finishedAt' => $game->getFinishedAt()?->format('c'),
            'updatedAt' => $game->getUpdatedAt()->format('c'),
        ];

        if ($currentUser) {
            $data['mySymbol'] = $game->getUserSymbol($currentUser);
            $data['isMyTurn'] = $game->getCurrentTurn() === $game->getUserSymbol($currentUser);
        }

        return $data;
    }
}
