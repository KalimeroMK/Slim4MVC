<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\Route;
use App\Modules\Role\Infrastructure\Models\Role;
use App\Modules\User\Application\Actions\CreateUserAction;
use App\Modules\User\Application\Actions\DeleteUserAction;
use App\Modules\User\Application\Actions\GetUserAction;
use App\Modules\User\Application\Actions\ListUsersAction;
use App\Modules\User\Application\Actions\UpdateUserAction;
use App\Modules\User\Application\DTOs\CreateUserDTO;
use App\Modules\User\Application\DTOs\UpdateUserDTO;
use App\Modules\User\Infrastructure\Http\Requests\Web\CreateUserRequest;
use App\Modules\User\Infrastructure\Http\Requests\Web\UpdatePasswordRequest;
use App\Modules\User\Infrastructure\Http\Requests\Web\UpdateUserRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class UserController extends Controller
{
    public function __construct(
        ContainerInterface $container,
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly DeleteUserAction $deleteUserAction,
        private readonly GetUserAction $getUserAction,
        private readonly ListUsersAction $listUsersAction
    ) {
        parent::__construct($container);
    }

    /**
     * Display all users.
     */
    public function index(Request $request, Response $response): Response
    {
        $users = $this->listUsersAction->execute(1, 100)['items'];
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
    public function store(CreateUserRequest $request, Response $response): Response
    {
        $this->createUserAction->execute(
            CreateUserDTO::fromRequest($request->validated())
        );

        return $this->redirect(Route::url('admin.users.index'));
    }

    /**
     * Show edit user form.
     */
    public function edit(Request $request, Response $response, int $id): Response
    {
        $user = $this->getUserAction->execute($id);
        $roles = Role::all();

        return view('admin.users.edit', $response, [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update user.
     */
    public function update(UpdateUserRequest $request, Response $response, int $id): Response
    {
        $this->updateUserAction->execute(
            UpdateUserDTO::fromRequest($id, $request->validated())
        );

        return $this->redirect(Route::url('admin.users.index'));
    }

    /**
     * Update user password.
     */
    public function updatePassword(UpdatePasswordRequest $request, Response $response, int $id): Response
    {
        $this->updateUserAction->execute(
            UpdateUserDTO::fromRequest($id, ['password' => $request->validated()['password']])
        );

        return $this->redirect(Route::url('admin.users.index'));
    }

    /**
     * Delete user.
     */
    public function delete(Request $request, Response $response, int $id): Response
    {
        $currentUser = $this->getCurrentUser();

        if ($currentUser && $currentUser['id'] === $id) {
            throw new RuntimeException('Cannot delete your own account');
        }

        $this->deleteUserAction->execute($id);

        return $this->redirect(Route::url('admin.users.index'));
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
