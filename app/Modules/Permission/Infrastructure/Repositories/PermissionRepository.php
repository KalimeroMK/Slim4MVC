<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Repositories;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use App\Modules\Permission\Infrastructure\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission repository for data access operations.
 */
class PermissionRepository extends EloquentRepository
{
    /**
     * Get all permissions with roles.
     *
     * @return Collection<int, Permission>
     */
    public function allWithRoles(): Collection
    {
        return Permission::with('roles')->get();
    }

    /**
     * Get paginated permissions with roles.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function paginateWithRoles(int $page = 1, int $perPage = 15): array
    {
        $paginator = Permission::with('roles')
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
     * Find permission by name.
     */
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    /**
     * Find permission by name with roles.
     */
    public function findByNameWithRoles(string $name): ?Permission
    {
        return Permission::with('roles')->where('name', $name)->first();
    }

    /**
     * Get the model class name.
     *
     * @return class-string<Permission>
     */
    protected function model(): string
    {
        return Permission::class;
    }
}
