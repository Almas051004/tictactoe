<?php



namespace App\Controller;



use App\Entity\Game;

use App\Entity\User;

use App\Repository\GameRepository;

use App\Repository\UserRepository;

use App\Service\GameService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Uid\UuidV6;
use Symfony\Contracts\Translation\TranslatorInterface;



#[Route('/api/game')]

class GameController extends AbstractController

{

    use ApiResponseTrait;

    public function __construct(
        private TranslatorInterface $translator
    ) {}

    #[Route('/waiting', methods: ['GET'])]

    public function getWaitingGames(

        GameRepository $gameRepository,

        UserRepository $userRepository,

        Request $request

    ): JsonResponse {

        try {

            $userId = $request->headers->get('X-User-Id');

            if (!$userId) {

                return $this->jsonError($this->translator->trans('api.error.unauthorized'), Response::HTTP_UNAUTHORIZED);

            }



            $user = $userRepository->find(UuidV6::fromString($userId));

            if (!$user) {

                return $this->jsonError($this->translator->trans('api.error.user_not_found'), Response::HTTP_NOT_FOUND);

            }



            $user->updateActivity();

            $gameRepository->getEntityManager()->flush();



            $games = $gameRepository->findWaitingGames();

            $games = array_filter($games, fn(Game $g) => $g->getCreatorUser()->getId() !== $user->getId());



            $result = array_map(

                fn(Game $g) => $this->formatGameForList($g),

                $games

            );



            return $this->jsonSuccess($result);

        } catch (\Exception $e) {

            return $this->jsonError($this->translator->trans('api.error.internal'), Response::HTTP_INTERNAL_SERVER_ERROR);

        }

    }



    #[Route('/active', methods: ['GET'])]

    public function getActiveGames(

        GameRepository $gameRepository,

        UserRepository $userRepository,

        Request $request

    ): JsonResponse {

        try {

            $userId = $request->headers->get('X-User-Id');

            if (!$userId) {

                return $this->jsonError($this->translator->trans('api.error.unauthorized'), Response::HTTP_UNAUTHORIZED);

            }



            $user = $userRepository->find(UuidV6::fromString($userId));

            if (!$user) {

                return $this->jsonError($this->translator->trans('api.error.user_not_found'), Response::HTTP_NOT_FOUND);

            }

            $games = $gameRepository->findGamesByUser($user);

            $result = array_map(

                fn(Game $g) => $this->formatGameForList($g, $user),

                $games

            );



            return $this->jsonSuccess($result);

        } catch (\Exception $e) {

            return $this->jsonError($this->translator->trans('api.error.internal').': '. $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);

        }

    }



    #[Route('/create', methods: ['POST'])]

    public function createGame(

        UserRepository $userRepository,

        GameRepository $gameRepository,

        Request $request

    ): JsonResponse {

        try {

            $userId = $request->headers->get('X-User-Id');

            if (!$userId) {

                return $this->jsonError($this->translator->trans('api.error.unauthorized'), Response::HTTP_UNAUTHORIZED);

            }



            $user = $userRepository->find(UuidV6::fromString($userId));

            if (!$user) {

                return $this->jsonError($this->translator->trans('api.error.user_not_found'), Response::HTTP_NOT_FOUND);

            }



            $game = new Game($user);

           

            $em = $gameRepository->getEntityManager();

            $em->persist($game);

            $em->flush();



            return $this->jsonSuccess(

                $this->formatGameForList($game),

                Response::HTTP_CREATED

            );

        } catch (\Exception $e) {

            return $this->jsonError($this->translator->trans('api.error.internal'), Response::HTTP_INTERNAL_SERVER_ERROR);

        }

    }



    #[Route('/{gameId}/join', methods: ['POST'])]

    public function joinGame(

        string $gameId,

        UserRepository $userRepository,

        GameRepository $gameRepository,

        Request $request

    ): JsonResponse {

        $userId = $request->headers->get('X-User-Id');

        if (!$userId) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.unauthorized')], Response::HTTP_UNAUTHORIZED);

        }



        $user = $userRepository->find(UuidV6::fromString($userId));

        if (!$user) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.user_not_found')], Response::HTTP_NOT_FOUND);

        }



        $game = $gameRepository->find(UuidV6::fromString($gameId));

        if (!$game) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.game_not_found')], Response::HTTP_NOT_FOUND);

        }



        if ($game->getStatus() !== Game::STATUS_WAITING) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.game_is_not_available')],

                Response::HTTP_CONFLICT

            );

        }



        if ($game->getCreatorUser()->getId() === $user->getId()) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.join_owned_game')],

                Response::HTTP_CONFLICT

            );

        }



        $game->setOpponentUser($user);

        $game->setStatus(Game::STATUS_ACTIVE);



        $em = $gameRepository->getEntityManager();

        $em->flush();



        return new JsonResponse($this->formatGameForList($game));

    }



    #[Route('/{gameId}', methods: ['GET'])]

    public function getGame(

        string $gameId,

        GameRepository $gameRepository,

        UserRepository $userRepository,

        GameService $gameService,

        Request $request

    ): JsonResponse {

        $userId = $request->headers->get('X-User-Id');

        if (!$userId) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.unauthorized')], Response::HTTP_UNAUTHORIZED);

        }



        $user = $userRepository->find(UuidV6::fromString($userId));

        if (!$user) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.user_not_found')], Response::HTTP_NOT_FOUND);

        }



        $game = $gameRepository->find(UuidV6::fromString($gameId));

        if (!$game) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.game_not_found')], Response::HTTP_NOT_FOUND);

        }

        if ($game->getCreatorUser()->getId() !== $user->getId() &&

            (!$game->getOpponentUser() || $game->getOpponentUser()->getId() !== $user->getId())) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.forbidden')], Response::HTTP_FORBIDDEN);

        }



        return new JsonResponse($gameService->formatGameData($game, $user));

    }



    #[Route('/{gameId}/move', methods: ['POST'])]

    public function makeMove(

        string $gameId,

        GameRepository $gameRepository,

        UserRepository $userRepository,

        GameService $gameService,

        Request $request

    ): JsonResponse {

        $userId = $request->headers->get('X-User-Id');

        if (!$userId) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.unauthorized')], Response::HTTP_UNAUTHORIZED);

        }



        $user = $userRepository->find(UuidV6::fromString($userId));

        if (!$user) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.user_not_found')], Response::HTTP_NOT_FOUND);

        }



        $game = $gameRepository->find(UuidV6::fromString($gameId));

        if (!$game) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.game_not_found')], Response::HTTP_NOT_FOUND);

        }

        if ($game->getCreatorUser()->getId() !== $user->getId() &&

            (!$game->getOpponentUser() || $game->getOpponentUser()->getId() !== $user->getId())) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.forbidden')], Response::HTTP_FORBIDDEN);

        }



        if ($game->getStatus() !== Game::STATUS_ACTIVE) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.game_is_not_active')],

                Response::HTTP_CONFLICT

            );

        }



        $data = json_decode($request->getContent(), true);

        if (!isset($data['position']) || !is_int($data['position'])) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.invalid_move')],

                Response::HTTP_BAD_REQUEST

            );

        }



        if (!$gameService->isValidMove($game, $data['position'], $user)) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.invalid_move')],

                Response::HTTP_BAD_REQUEST

            );

        }



        $move = $gameService->applyMove($game, $data['position'], $user);

        $result = $gameService->getGameResult($game);

        if ($result) {

            $game->setStatus(Game::STATUS_FINISHED);

            if ($result === 'draw') {

                $game->setWinner('draw');

            } else {

                $game->setWinner($result);

            }

        }



        $em = $gameRepository->getEntityManager();

        $em->flush();



        return new JsonResponse([

            'move' => $gameService->formatMoveData($move),

            'game' => $gameService->formatGameData($game, $user),

        ]);

    }



    #[Route('/{gameId}/quit', methods: ['POST'])]

    public function quitGame(

        string $gameId,

        GameRepository $gameRepository,

        UserRepository $userRepository,

        Request $request

    ): JsonResponse {

        $userId = $request->headers->get('X-User-Id');

        if (!$userId) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.unauthorized')], Response::HTTP_UNAUTHORIZED);

        }



        $user = $userRepository->find(UuidV6::fromString($userId));

        if (!$user) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.user_not_found')], Response::HTTP_NOT_FOUND);

        }



        $game = $gameRepository->find(UuidV6::fromString($gameId));

        if (!$game) {

            return new JsonResponse(['error' => $this->translator->trans('api.error.game_not_found')], Response::HTTP_NOT_FOUND);

        }



        if ($game->getStatus() === Game::STATUS_FINISHED) {

            return new JsonResponse(

                ['error' => $this->translator->trans('api.error.game_finished')],

                Response::HTTP_CONFLICT

            );

        }

        if ($game->getStatus() === Game::STATUS_ACTIVE) {

            $opponent = $game->getOpponentOf($user);

            $winnerSymbol = $game->getUserSymbol($opponent);

            $game->setWinner($winnerSymbol);

        }



        $game->setStatus(Game::STATUS_FINISHED);



        $em = $gameRepository->getEntityManager();

        $em->flush();



        return new JsonResponse(['success' => true]);

    }



    private function formatGameForList(Game $game, ?User $user = null): array

    {

        $data = [

            'id' => (string) $game->getId(),

            'status' => $game->getStatus(),

            'creator' => $game->getCreatorUser()->getName(),

            'opponent' => $game->getOpponentUser()?->getName(),

            'createdAt' => $game->getCreatedAt()->format('c'),

            'startedAt' => $game->getStartedAt()?->format('c'),

        ];

        if ($user && $game->getStatus() === Game::STATUS_ACTIVE) {

            $mySymbol = $game->getUserSymbol($user);

            $currentTurn = $game->getCurrentTurn();

            $data['isMyTurn'] = ($mySymbol === $currentTurn);

        } else {

            $data['isMyTurn'] = false;

        }



        return $data;

    }

}