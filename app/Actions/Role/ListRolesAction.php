<?php

declare(strict_types=1);

namespace App\Actions\Role;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRolesAction
{
    /**
     * Execute listing roles with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
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
}
