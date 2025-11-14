<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Repositories;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use App\Modules\Role\Infrastructure\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Role repository for data access operations.
 */
class RoleRepository extends EloquentRepository
{
    /**
     * Get all roles with permissions.
     *
     * @return Collection<int, Role>
     */
    public function allWithPermissions(): Collection
    {
        return Role::with('permissions')->get();
    }

    /**
     * Get paginated roles with permissions.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function paginateWithPermissions(int $page = 1, int $perPage = 15): array
    {
        $paginator = Role::with('permissions')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'perPage' => $paginator->perPage(),
        ];
    }

    /**
     * Find role by name.
     */
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    /**
     * Find role by name with permissions.
     */
    public function findByNameWithPermissions(string $name): ?Role
    {
        return Role::with('permissions')->where('name', $name)->first();
    }

    /**
     * Get the model class name.
     *
     * @return class-string<Role>
     */
    protected function model(): string
    {
        return Role::class;
    }
}
