<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ApiResponseService
{
    public function respondWithResource(
        JsonResource $resource,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
        array $additional = [],
    ): JsonResponse {
        return $this->respond(
            success: true,
            message: $message,
            result: array_merge($resource->resolve(), $additional),
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    public function respondWithResourceCollection(
        ResourceCollection $resourceCollection,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
        array $additional = [],
    ): JsonResponse {
        return $this->respond(
            success: true,
            message: $message,
            result: array_merge($resourceCollection->response()->getData(true), $additional),
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    public function respondSuccess(
        string $message = 'Success',
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
    ): JsonResponse {
        return $this->respond(
            success: true,
            message: $message,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    public function respondWithSuccess(
        mixed $data,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
    ): JsonResponse {
        return $this->respond(
            success: true,
            message: $message,
            result: $data,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    public function respondCreated(
        mixed $data,
        string $message = 'Created successfully.',
        array $headers = [],
    ): JsonResponse {
        return $this->respondWithSuccess(
            data: $data,
            message: $message,
            statusCode: Response::HTTP_CREATED,
            headers: $headers,
        );
    }

    public function respondNoContent(array $headers = []): Response
    {
        return response()->noContent(Response::HTTP_NO_CONTENT, $headers);
    }

    public function respondUnAuthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->respondError($message, Response::HTTP_UNAUTHORIZED);
    }

    public function respondForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->respondError($message, Response::HTTP_FORBIDDEN);
    }

    public function respondNotFound(string $message = 'Not Found'): JsonResponse
    {
        return $this->respondError($message, Response::HTTP_NOT_FOUND);
    }

    public function respondInternalError(
        string $message = 'Internal Server Error',
        ?Throwable $exception = null,
    ): JsonResponse {
        return $this->respondError($message, Response::HTTP_INTERNAL_SERVER_ERROR, $exception);
    }

    public function respondError(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        ?Throwable $exception = null,
        array $headers = [],
    ): JsonResponse {
        return $this->respond(
            success: false,
            message: $message,
            statusCode: $statusCode,
            headers: $headers,
            exception: $exception,
        );
    }

    public function respondValidationErrors(ValidationException $exception): JsonResponse
    {
        return $this->respond(
            success: false,
            message: $exception->getMessage(),
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $exception->errors(),
        );
    }

    private function respond(
        bool $success,
        ?string $message,
        mixed $result = null,
        int $statusCode = Response::HTTP_OK,
        array $headers = [],
        ?array $errors = null,
        ?Throwable $exception = null,
    ): JsonResponse {
        $payload = [
            'success' => $success,
            'message' => $message,
            'result' => $result,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($exception !== null && config('app.debug')) {
            $payload['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ];
        }

        return response()->json($payload, $statusCode, $headers);
    }
}
