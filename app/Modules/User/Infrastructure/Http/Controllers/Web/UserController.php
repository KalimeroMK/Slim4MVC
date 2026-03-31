<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Infrastructure\Http\Requests\Web\CreateUserRequest;
use App\Modules\User\Infrastructure\Http\Requests\Web\UpdatePasswordRequest;
use App\Modules\User\Infrastructure\Http\Requests\Web\UpdateUserRequest;
use App\Modules\User\Infrastructure\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class UserController extends Controller
{
    /**
     * Display all users.
     */
    public function index(Request $request, Response $response): Response
    {
        /** @phpstan-ignore-next-line */
        $users = User::with('roles')->get();
        /** @phpstan-ignore-next-line */
        $roles = Role::all();

        return view('admin.users.index', $response, [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    /**
     * Show create user form.
     */
    public function create(Request $request, Response $response): Response
    {
        /** @phpstan-ignore-next-line */
        $roles = Role::all();

        return view('admin.users.create', $response, [
            'roles' => $roles,
        ]);
    }

    /**
     * Store new user.
     */
    public function store(CreateUserRequest $createUserRequest, Response $response): Response
    {
        $data = $createUserRequest->validated();

        /** @phpstan-ignore-next-line */
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        if (! empty($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        return $this->redirect('/admin/users');
    }

    /**
     * Show edit user form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        /** @phpstan-ignore-next-line */
        $user = User::with('roles')->findOrFail($id);
        /** @phpstan-ignore-next-line */
        $roles = Role::all();

        return view('admin.users.edit', $response, [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $updateUserRequest, Response $response, int $id): Response
    {
        $data = $updateUserRequest->validated();

        /** @phpstan-ignore-next-line */
        $user = User::findOrFail($id);

        if (! empty($data['name'])) {
            $user->name = $data['name'];
        }

        if (! empty($data['email'])) {
            $user->email = $data['email'];
        }

        $user->save();

        $user->roles()->sync($data['roles'] ?? []);

        return $this->redirect('/admin/users');
    }

    /**
     * Update user password.
     */
    public function updatePassword(UpdatePasswordRequest $updatePasswordRequest, Response $response, int $id): Response
    {
        /** @phpstan-ignore-next-line */
        $user = User::findOrFail($id);
        $user->password = password_hash($updatePasswordRequest->validated()['password'], PASSWORD_BCRYPT);
        $user->save();

        return $this->redirect('/admin/users');
    }

    /**
     * Delete user.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        /** @phpstan-ignore-next-line */
        $user = User::findOrFail($id);
        $currentUser = $this->getCurrentUser();

        if ($currentUser && $currentUser['id'] === $id) {
            throw new RuntimeException('Cannot delete your own account');
        }

        $user->delete();

        return $this->redirect('/admin/users');
    }

    /**
     * Get current logged in user.
     *
     * @return array<string, mixed>|null
     */
    private function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
