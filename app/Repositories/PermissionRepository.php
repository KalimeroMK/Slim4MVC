<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission repository for data access operations.
 */
class PermissionRepository extends EloquentRepository
{
    /**
     * Get the model class name.
     *
     * @return class-string<Permission>
     */
    protected function model(): string
    {
        return Permission::class;
    }

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
     * @param int $page
     * @param int $perPage
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
     *
     * @return Permission|null
     */
    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    /**
     * Find permission by name with roles.
     *
     * @return Permission|null
     */
    public function findByNameWithRoles(string $name): ?Permission
    {
        return Permission::with('roles')->where('name', $name)->first();
    }
}

