<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Database\Factories;

use App\Modules\Core\Infrastructure\Database\Factories\Factory;
use App\Modules\User\Infrastructure\Models\User;
use Carbon\Carbon;

/**
 * User Factory for generating fake user data.
 */
class UserFactory extends Factory
{
    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has a specific password.
     */
    public function withPassword(string $password): self
    {
        return $this->state(fn (array $attributes): array => [
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    /**
     * Create a user with a specific role.
     *
     * @param  string|int  $role  Role name or ID
     */
    public function withRole($role): User
    {
        $user = $this->create();

        if (is_string($role)) {
            $roleModel = \App\Modules\Role\Infrastructure\Models\Role::where('name', $role)->first();
            if ($roleModel) {
                $user->roles()->attach($roleModel->id);
            }
        } else {
            $user->roles()->attach($role);
        }

        return $user->fresh();
    }

    protected function model(): string
    {
        return User::class;
    }

    protected function definition(): array
    {
        return [
            'name' => $this->faker()->name(),
            'email' => $this->faker()->unique()->safeEmail(),
            'password' => password_hash('password', PASSWORD_BCRYPT), // Default password: 'password'
            'email_verified_at' => $this->faker()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
