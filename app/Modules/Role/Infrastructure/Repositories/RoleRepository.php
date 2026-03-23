<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Repositories;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use App\Modules\Role\Infrastructure\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Role repository for data access operations.
 *
 * @extends EloquentRepository<Role>
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
     * @return array{items: list<Role>, total: int, page: int, perPage: int}
     */
    public function paginateWithPermissions(int $page = 1, int $perPage = 15): array
    {
        $lengthAwarePaginator = Role::with('permissions')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        /** @var list<Role> $items */
        $items = $lengthAwarePaginator->items();

        return [
            'items' => $items,
            'total' => $lengthAwarePaginator->total(),
            'page' => $lengthAwarePaginator->currentPage(),
            'perPage' => $lengthAwarePaginator->perPage(),
        ];
    }

    /**
     * Find role by name.
     */
    public function findByName(string $name): ?Role
    {
        /** @var Role|null $role */
        $role = Role::where('name', $name)->first();

        return $role;
    }

    /**
     * Find role by name with permissions.
     */
    public function findByNameWithPermissions(string $name): ?Role
    {
        /** @var Role|null $role */
        $role = Role::with('permissions')->where('name', $name)->first();

        return $role;
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
