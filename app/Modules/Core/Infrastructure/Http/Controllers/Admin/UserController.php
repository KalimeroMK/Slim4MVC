<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Admin;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Role\Infrastructure\Models\Role;
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
        $users = User::with('roles')->get();
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
        $roles = Role::all();

        return view('admin.users.create', $response, [
            'roles' => $roles,
        ]);
    }

    /**
     * Store new user.
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validation
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            throw new RuntimeException('Name, email and password are required');
        }

        if ($data['password'] !== ($data['password_confirmation'] ?? '')) {
            throw new RuntimeException('Passwords do not match');
        }

        // Check if email exists
        if (User::where('email', $data['email'])->exists()) {
            throw new RuntimeException('Email already exists');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        // Attach roles if provided
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
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all();

        return view('admin.users.edit', $response, [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user.
     */
    public function update(Request $request, Response $response, int $id): Response
    {
        $data = $request->getParsedBody();
        $user = User::findOrFail($id);

        // Update name
        if (! empty($data['name'])) {
            $user->name = $data['name'];
        }

        // Update email
        if (! empty($data['email']) && $data['email'] !== $user->email) {
            if (User::where('email', $data['email'])->where('id', '!=', $id)->exists()) {
                throw new RuntimeException('Email already taken');
            }
            $user->email = $data['email'];
        }

        $user->save();

        // Sync roles
        $user->roles()->sync($data['roles'] ?? []);

        return $this->redirect('/admin/users');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, Response $response, int $id): Response
    {
        $data = $request->getParsedBody();
        $user = User::findOrFail($id);

        if (empty($data['password']) || empty($data['password_confirmation'])) {
            throw new RuntimeException('Password and confirmation are required');
        }

        if ($data['password'] !== $data['password_confirmation']) {
            throw new RuntimeException('Passwords do not match');
        }

        if (mb_strlen($data['password']) < 6) {
            throw new RuntimeException('Password must be at least 6 characters');
        }

        $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->save();

        return $this->redirect('/admin/users');
    }

    /**
     * Delete user.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $user = User::findOrFail($id);

        // Don't allow deleting yourself
        $currentUser = $this->getCurrentUser();
        if ($currentUser && $currentUser['id'] === $id) {
            throw new RuntimeException('Cannot delete your own account');
        }

        $user->delete();

        return $this->redirect('/admin/users');
    }

    /**
     * Get current logged in user.
     */
    private function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}
