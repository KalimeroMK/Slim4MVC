<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Repositories;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use App\Modules\Permission\Infrastructure\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Permission repository for data access operations.
 *
 * @extends EloquentRepository<Permission>
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
     * @return array{items: list<Permission>, total: int, page: int, perPage: int}
     */
    public function paginateWithRoles(int $page = 1, int $perPage = 15): array
    {
        $lengthAwarePaginator = Permission::with('roles')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        /** @var list<Permission> $items */
        $items = $lengthAwarePaginator->items();

        return [
            'items' => $items,
            'total' => $lengthAwarePaginator->total(),
            'page' => $lengthAwarePaginator->currentPage(),
            'perPage' => $lengthAwarePaginator->perPage(),
        ];
    }

    /**
     * Find permission by name.
     */
    public function findByName(string $name): ?Permission
    {
        /** @var Permission|null $permission */
        $permission = Permission::where('name', $name)->first();

        return $permission;
    }

    /**
     * Find permission by name with roles.
     */
    public function findByNameWithRoles(string $name): ?Permission
    {
        /** @var Permission|null $permission */
        $permission = Permission::with('roles')->where('name', $name)->first();

        return $permission;
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
