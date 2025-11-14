<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\Actions\Auth\PasswordRecoveryAction;
use App\Modules\Auth\Application\Actions\Auth\RegisterAction;
use App\Modules\Auth\Application\Actions\Auth\ResetPasswordAction;
use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\LoginRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\PasswordRecoveryRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\RegisterRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\ResetPasswordRequest;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

class AuthController extends Controller
{
    public function __construct(
        ContainerInterface $container,
        private readonly RegisterAction $registerAction,
        private readonly LoginAction $loginAction,
        private readonly PasswordRecoveryAction $passwordRecoveryAction,
        private readonly ResetPasswordAction $resetPasswordAction
    ) {
        parent::__construct($container);
    }

    public function register(RegisterRequest $request, Response $response): Response
    {
        $user = $this->registerAction->execute(
            RegisterDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'user' => $user,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function login(LoginRequest $request, Response $response): Response
    {
        $result = $this->loginAction->execute(
            LoginDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode($result));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function passwordRecovery(PasswordRecoveryRequest $request, Response $response): Response
    {
        $this->passwordRecoveryAction->execute(
            PasswordRecoveryDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Password reset link has been sent to your email',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updatePassword(ResetPasswordRequest $request, Response $response): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
