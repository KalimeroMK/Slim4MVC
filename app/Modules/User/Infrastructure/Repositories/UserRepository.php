<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Repositories;

use App\Modules\Core\Infrastructure\Repositories\EloquentRepository;
use App\Modules\User\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * User repository for data access operations.
 *
 * @extends EloquentRepository<User>
 */
class UserRepository extends EloquentRepository
{
    /**
     * Get all users with roles.
     *
     * @return Collection<int, User>
     */
    public function allWithRoles(): Collection
    {
        return User::with('roles')->get();
    }

    /**
     * Get paginated users with roles.
     *
     * @return array{items: list<User>, total: int, page: int, perPage: int}
     */
    public function paginateWithRoles(int $page = 1, int $perPage = 15): array
    {
        $lengthAwarePaginator = User::with('roles')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        /** @var list<User> $items */
        $items = $lengthAwarePaginator->items();

        return [
            'items' => $items,
            'total' => $lengthAwarePaginator->total(),
            'page' => $lengthAwarePaginator->currentPage(),
            'perPage' => $lengthAwarePaginator->perPage(),
        ];
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        return $user;
    }

    /**
     * Find user by email with roles.
     */
    public function findByEmailWithRoles(string $email): ?User
    {
        /** @var User|null $user */
        $user = User::with('roles')->where('email', $email)->first();

        return $user;
    }

    /**
     * Find user by password reset token.
     */
    public function findByPasswordResetToken(string $token): ?User
    {
        /** @var User|null $user */
        $user = User::where('password_reset_token', $token)->first();

        return $user;
    }

    /**
     * Get the model class name.
     *
     * @return class-string<User>
     */
    protected function model(): string
    {
        return User::class;
    }
}
