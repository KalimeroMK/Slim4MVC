<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers;

use App\Modules\Core\Application\Actions\Generic\CrudActionFactory;
use App\Modules\Core\Infrastructure\Http\Resources\Resource;
use App\Modules\Core\Infrastructure\Repositories\Repository;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use App\Modules\Core\Infrastructure\Traits\RouteParamsTrait;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
     * @var class-string<Resource<TModel>>|null
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
    public function index(Request $request, Response $response): Response
    {
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
            : $result['items'];

        return ApiResponse::paginated(
            total: $result['total'],
            page: $result['page'],
            perPage: $result['perPage'],
            baseUrl: $this->getPaginationBaseUrl(),
            items: $items
        );
    }

    /**
     * Show a single resource.
     */
    public function show(Request $request, Response $response, array $args): Response
    {
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
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];

        // Filter to fillable fields if specified
        if ($this->fillable !== []) {
            $data = array_intersect_key($data, array_flip($this->fillable));
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
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;

        if ($id === null) {
            return ApiResponse::error('ID is required', 400);
        }

        $data = $request->getParsedBody() ?? [];

        // Filter to fillable fields if specified
        if ($this->fillable !== []) {
            $data = array_intersect_key($data, array_flip($this->fillable));
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
     */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;

        if ($id === null) {
            return ApiResponse::error('ID is required', 400);
        }

        $this->crudActionFactory->delete()->execute($id);

        return ApiResponse::success(null, 204);
    }

    /**
     * Get the action factory (for custom methods).
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
}
