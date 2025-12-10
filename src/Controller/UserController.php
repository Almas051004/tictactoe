<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\UuidV6;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private TranslatorInterface $translator
    ) {}

    #[Route('/user/login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['name']) || empty(trim($data['name']))) {
                return $this->jsonError(
                    $this->translator->trans('api.error.name_required'), 
                    Response::HTTP_BAD_REQUEST
                );
            }

            $name = trim($data['name']);

            if (strlen($name) < 2 || strlen($name) > 50) {
                return $this->jsonError(
                    $this->translator->trans('api.error.name_length', ['%min%' => 2, '%max%' => 50]), 
                    Response::HTTP_BAD_REQUEST
                );
            }
            $user = $userRepository->findByName($name);
            
            if (!$user) {
                $user = new User();
                $user->setName($name);
            }

            $user->updateActivity();
            $em->persist($user);
            $em->flush();

            return $this->jsonSuccess([
                'id' => (string) $user->getId(),
                'name' => $user->getName(),
                'createdAt' => $user->getCreatedAt()->format('c'),
            ]);
        } catch (\Exception $e) {
            $logger->error('Login error: ' . $e->getMessage(), ['exception' => $e]);
            return $this->jsonError(
                $this->translator->trans('api.error.internal') . ': ' . $e->getMessage(), 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/user/check-name', methods: ['POST'])]
    public function checkName(
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['name']) || empty(trim($data['name']))) {
                return $this->jsonError(
                    $this->translator->trans('api.error.name_required'), 
                    Response::HTTP_BAD_REQUEST
                );
            }

            $name = trim($data['name']);

            if (strlen($name) < 2) {
                return $this->jsonSuccess([
                    'available' => false,
                    'message' => $this->translator->trans('api.error.name_min_len', ['%min%' => 2]),
                ]);
            }

            if (strlen($name) > 50) {
                return $this->jsonSuccess([
                    'available' => false,
                    'message' => $this->translator->trans('api.error.name_max_len', ['%max%' => 50]),
                ]);
            }

            $user = $userRepository->findByName($name);
            
            return $this->jsonSuccess([
                'available' => $user === null,
                'message' => $user 
                    ? $this->translator->trans('api.error.name_taken') 
                    : $this->translator->trans('api.success.name_available'),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $this->translator->trans('api.error.internal'), 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/user/me', methods: ['GET'])]
    public function getCurrentUser(Request $request, UserRepository $userRepository, \Doctrine\ORM\EntityManagerInterface $em): JsonResponse
    {
        try {
            $userId = $request->headers->get('X-User-Id');
            if (!$userId) {
                return $this->jsonError(
                    $this->translator->trans('api.error.unauthorized'), 
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user = $userRepository->find(UuidV6::fromString($userId));
            if (!$user) {
                return $this->jsonError(
                    $this->translator->trans('api.error.user_not_found'), 
                    Response::HTTP_NOT_FOUND
                );
            }

            $user->updateActivity();
            $em->flush();

            return $this->jsonSuccess([
                'id' => (string) $user->getId(),
                'name' => $user->getName(),
                'createdAt' => $user->getCreatedAt()->format('c'),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $this->translator->trans('api.error.internal'), 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/user/active', methods: ['GET'])]
    public function getActiveUsers(UserRepository $userRepository): JsonResponse
    {
        try {
            $users = $userRepository->findActiveUsers();
            return $this->jsonSuccess([
                'count' => count($users),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(
                $this->translator->trans('api.error.internal'), 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}