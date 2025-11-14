<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * User repository for data access operations.
 */
class UserRepository extends EloquentRepository
{
    /**
     * Get the model class name.
     *
     * @return class-string<User>
     */
    protected function model(): string
    {
        return User::class;
    }

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
     * @param int $page
     * @param int $perPage
     * @return array{items: array, total: int, page: int, perPage: int}
     */
    public function paginateWithRoles(int $page = 1, int $perPage = 15): array
    {
        $paginator = User::with('roles')
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
     * Find user by email.
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find user by email with roles.
     *
     * @return User|null
     */
    public function findByEmailWithRoles(string $email): ?User
    {
        return User::with('roles')->where('email', $email)->first();
    }

    /**
     * Find user by password reset token.
     *
     * @return User|null
     */
    public function findByPasswordResetToken(string $token): ?User
    {
        return User::where('password_reset_token', $token)->first();
    }
}

