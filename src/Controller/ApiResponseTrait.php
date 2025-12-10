<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    protected function jsonError(string $message, int $statusCode = 400): JsonResponse
    {
        return new JsonResponse(['error' => $message], $statusCode);
    }

    protected function jsonSuccess(mixed $data = null, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse($data, $statusCode);
    }
}
