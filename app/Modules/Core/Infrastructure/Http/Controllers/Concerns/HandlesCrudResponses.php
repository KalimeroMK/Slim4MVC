<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Concerns;

use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Trait for handling standard CRUD API responses.
 *
 * Provides standardized methods for paginated lists, single resources,
 * created resources, updated resources, and deletion responses.
 *
 * Controllers using this trait should define:
 * protected ?string $resourceClass = YourResource::class;
 */
trait HandlesCrudResponses
{
    /**
     * Return a paginated response.
     *
     * @param  array{items: array<int, mixed>, total: int, page: int, perPage: int}  $result
     * @param  class-string|null  $resourceClass
     */
    protected function respondPaginated(array $result, ?string $resourceClass = null): Response
    {
        $resourceClass ??= $this->resourceClass ?? null;

        $items = $resourceClass !== null
            ? $resourceClass::collection($result['items'])
            : $result['items'];

        return ApiResponse::paginated(
            $items,
            $result['total'],
            $result['page'],
            $result['perPage'],
            HttpStatusCode::OK,
            $this->getPaginationBaseUrl()
        );
    }

    /**
     * Return a single resource response.
     *
     * @param  class-string|null  $resourceClass
     */
    protected function respondResource(mixed $model, ?string $resourceClass = null): Response
    {
        $resourceClass ??= $this->resourceClass ?? null;

        $data = $resourceClass !== null
            ? $resourceClass::make($model)
            : $model;

        return ApiResponse::success($data);
    }

    /**
     * Return a created resource response.
     *
     * @param  class-string|null  $resourceClass
     */
    protected function respondCreated(mixed $model, ?string $resourceClass = null): Response
    {
        $resourceClass ??= $this->resourceClass ?? null;

        $data = $resourceClass !== null
            ? $resourceClass::make($model)
            : $model;

        return ApiResponse::success($data, HttpStatusCode::CREATED);
    }

    /**
     * Return an updated resource response.
     *
     * @param  class-string|null  $resourceClass
     */
    protected function respondUpdated(mixed $model, ?string $resourceClass = null): Response
    {
        return $this->respondResource($model, $resourceClass);
    }

    /**
     * Return a deleted resource response (204 No Content).
     */
    protected function respondDeleted(): Response
    {
        return ApiResponse::success(null, HttpStatusCode::NO_CONTENT);
    }

    /**
     * Get the base URL for pagination links.
     */
    abstract protected function getPaginationBaseUrl(): string;
}
