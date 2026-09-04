<?php

namespace App\Facades;

use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Http\JsonResponse respondWithResource(\Illuminate\Http\Resources\Json\JsonResource $resource, ?string $message = null, int $statusCode = 200, array $headers = [], array $additional = [])
 * @method static \Illuminate\Http\JsonResponse respondWithResourceCollection(\Illuminate\Http\Resources\Json\ResourceCollection $resourceCollection, ?string $message = null, int $statusCode = 200, array $headers = [], array $additional = [])
 * @method static \Illuminate\Http\JsonResponse respondSuccess(string $message = 'Success', int $statusCode = 200, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse respondWithSuccess(mixed $data, ?string $message = null, int $statusCode = 200, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse respondCreated(mixed $data, string $message = 'Created successfully.', array $headers = [])
 * @method static \Symfony\Component\HttpFoundation\Response respondNoContent(array $headers = [])
 * @method static \Illuminate\Http\JsonResponse respondUnAuthorized(string $message = 'Unauthorized')
 * @method static \Illuminate\Http\JsonResponse respondError(string $message, int $statusCode = 400, ?\Throwable $exception = null, array $headers = [])
 * @method static \Illuminate\Http\JsonResponse respondForbidden(string $message = 'Forbidden')
 * @method static \Illuminate\Http\JsonResponse respondNotFound(string $message = 'Not Found')
 * @method static \Illuminate\Http\JsonResponse respondInternalError(string $message = 'Internal Server Error', ?\Throwable $exception = null)
 * @method static \Illuminate\Http\JsonResponse respondValidationErrors(\Illuminate\Validation\ValidationException $exception)
 *
 * @see ApiResponseService
 */
final class ApiResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ApiResponseService::class;
    }
}
