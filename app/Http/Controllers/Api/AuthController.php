<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\PasswordRecoveryAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\PasswordRecoveryDTO;
use App\DTO\Auth\RegisterDTO;
use App\DTO\Auth\ResetPasswordDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordRecoveryRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
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
            'message' => 'Password recovery email sent',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function updatePassword(ResetPasswordRequest $request, Response $response): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($request->validated())
        );

        $response->getBody()->write(json_encode([
            'message' => 'Password successfully reset',
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
