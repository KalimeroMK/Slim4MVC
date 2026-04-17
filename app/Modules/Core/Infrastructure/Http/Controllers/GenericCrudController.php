<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers;

use App\Modules\Core\Application\Actions\Generic\CrudActionFactory;
use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Http\Resources\Resource;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Support\Auth;
use App\Modules\Core\Infrastructure\Traits\RouteParamsTrait;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Throwable;

/**
 * Generic CRUD Controller using composition pattern.
 *
 * Provides complete CRUD functionality with minimal configuration.
 * Uses composition over inheritance for better flexibility.
 *
 * Example usage:
 * ```php
 * class ProductController extends GenericCrudController
 * {
 *     protected string $repositoryClass = ProductRepository::class;
 *     protected ?string $resourceClass = ProductResource::class;
 *     protected array $defaultRelations = ['category'];
 * }
 * ```
 *
 * @template TModel of Model
 */
abstract class GenericCrudController extends Controller
{
    use RouteParamsTrait;

    /**
     * Repository class FQCN.
     *
     * @var class-string<Repository<TModel>>
     */
    protected string $repositoryClass;

    /**
     * Resource class FQCN (optional).
     *
     * @var class-string<resource<TModel>>|null
     */
    protected ?string $resourceClass = null;

    /**
     * Relations to eager load by default.
     *
     * @var array<int, string>
     */
    protected array $defaultRelations = [];

    /**
     * Fields that can be filled (for create/update).
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Default items per page.
     */
    protected int $defaultPerPage = 15;

    /**
     * Permission required for listing resources (e.g. 'users.viewAny').
     * If null, no permission check is performed (only auth required).
     */
    protected ?string $listPermission = null;

    /**
     * Permission required for viewing a single resource.
     */
    protected ?string $viewPermission = null;

    /**
     * Permission required for creating resources.
     */
    protected ?string $createPermission = null;

    /**
     * Permission required for updating resources.
     */
    protected ?string $updatePermission = null;

    /**
     * Permission required for deleting resources.
     */
    protected ?string $deletePermission = null;

    /** @var CrudActionFactory<TModel> */
    private CrudActionFactory $crudActionFactory;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // Initialize action factory
        $this->crudActionFactory = CrudActionFactory::for($this->repositoryClass);
    }

    /**
     * List all resources with pagination.
     */
    final public function index(Request $request, Response $response): Response
    {
        $authResponse = $this->guard($this->listPermission);
        if ($authResponse instanceof Response) {
            return $authResponse;
        }

        $params = $this->getPaginationParams();

        if ($this->defaultRelations !== []) {
            $result = $this->crudActionFactory->list()->executeWith(
                $this->defaultRelations,
                $params['page'],
                $params['perPage']
            );
        } else {
            $result = $this->crudActionFactory->list()->execute(
                $params['page'],
                $params['perPage']
            );
        }

        $items = $this->resourceClass
            ? ($this->resourceClass)::collection($result['items'])
            : $result['items']->toArray();

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
     * Show a single resource.
     *
     * @param  array<string, mixed>  $args
     */
    final public function show(Request $request, Response $response, array $args): Response
    {
        $authResponse = $this->guard($this->viewPermission);
        if ($authResponse instanceof Response) {
            return $authResponse;
        }

        $id = $args['id'] ?? null;

        if ($id === null) {
            return ApiResponse::error('ID is required', 400);
        }

        if ($this->defaultRelations !== []) {
            $model = $this->crudActionFactory->get()->executeWith($id, $this->defaultRelations);
        } else {
            $model = $this->crudActionFactory->get()->execute($id);
        }

        $data = $this->resourceClass
            ? ($this->resourceClass)::make($model)
            : $model;

        return ApiResponse::success($data);
    }

    /**
     * Create a new resource.
     */
    final public function store(Request $request, Response $response): Response
    {
        $authResponse = $this->guard($this->createPermission);
        if ($authResponse instanceof Response) {
            return $authResponse;
        }

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];

        // Filter to fillable fields — secure by default (empty fillable = no data allowed)
        try {
            $data = $this->filterFillable($data);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }

        if ($data === []) {
            return ApiResponse::error('No valid data provided for creation', 422);
        }

        $model = $this->crudActionFactory->create()->execute($data);

        // Load default relations if specified
        if ($this->defaultRelations !== []) {
            $model->load($this->defaultRelations);
        }

        $data = $this->resourceClass
            ? ($this->resourceClass)::make($model)
            : $model;

        return ApiResponse::success($data, 201);
    }

    /**
     * Update an existing resource.
     *
     * @param  array<string, mixed>  $args
     */
    final public function update(Request $request, Response $response, array $args): Response
    {
        $authResponse = $this->guard($this->updatePermission);
        if ($authResponse instanceof Response) {
            return $authResponse;
        }

        $id = $args['id'] ?? null;

        if ($id === null) {
            return ApiResponse::error('ID is required', 400);
        }

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];

        // Filter to fillable fields — secure by default (empty fillable = no data allowed)
        try {
            $data = $this->filterFillable($data);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }

        if ($data === []) {
            return ApiResponse::error('No valid data provided for update', 422);
        }

        $model = $this->crudActionFactory->update()->execute($id, $data);

        // Load default relations if specified
        if ($this->defaultRelations !== []) {
            $model->load($this->defaultRelations);
        }

        $data = $this->resourceClass
            ? ($this->resourceClass)::make($model)
            : $model;

        return ApiResponse::success($data);
    }

    /**
     * Delete a resource.
     *
     * @param  array<string, mixed>  $args
     */
    final public function destroy(Request $request, Response $response, array $args): Response
    {
        $authResponse = $this->guard($this->deletePermission);
        if ($authResponse instanceof Response) {
            return $authResponse;
        }

        $id = $args['id'] ?? null;

        if ($id === null) {
            return ApiResponse::error('ID is required', 400);
        }

        $this->crudActionFactory->delete()->execute($id);

        return ApiResponse::success(null, 204);
    }

    /**
     * Get the action factory (for custom methods).
     *
     * @return CrudActionFactory<TModel>
     */
    protected function actions(): CrudActionFactory
    {
        return $this->crudActionFactory;
    }

    /**
     * Get the repository (for custom queries).
     *
     * @return Repository<TModel>
     */
    protected function repository(): Repository
    {
        return $this->crudActionFactory->getRepository();
    }

    /**
     * Check if the current user is authenticated.
     */
    private function isAuthenticated(): bool
    {
        try {
            $auth = $this->container->get(Auth::class);

            return $auth->check();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if user has the required permission.
     */
    private function checkPermission(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        return $this->can($permission);
    }

    /**
     * Guard a controller action with authentication and optional permission.
     */
    private function guard(?string $permission = null): ?Response
    {
        if (! $this->isAuthenticated()) {
            return ApiResponse::unauthorized('Authentication required');
        }

        if (! $this->checkPermission($permission)) {
            return ApiResponse::forbidden('You do not have permission to perform this action');
        }

        return null;
    }

    /**
     * Filter request data against fillable fields.
     * If no fillable fields are defined, returns empty array (secure by default).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws RuntimeException if fillable is empty
     */
    private function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            throw new RuntimeException(
                sprintf('No fillable fields defined for %s. Mass assignment is disabled by default.', static::class)
            );
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }
}
