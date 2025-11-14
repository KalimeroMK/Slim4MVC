<?php

declare(strict_types=1);

namespace App\Actions\Permission;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPermissionAction
{
    /**
     * Execute listing permissions with pagination.
     *
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function execute(int $page = 1, int $perPage = 15): array
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
}
