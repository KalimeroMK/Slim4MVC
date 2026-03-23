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
use OpenApi\Attributes as OA;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Tag(name: 'Authentication', description: 'Public authentication endpoints')]
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

    #[OA\Post(
        path: '/register',
        operationId: 'register',
        description: 'Register a new user account',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function register(RegisterRequest $registerRequest, Response $response): Response
    {
        $user = $this->registerAction->execute(
            RegisterDTO::fromRequest($registerRequest->validated())
        );

        $json = json_encode([
            'status' => 'success',
            'user' => $user,
        ]);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: '/login',
        operationId: 'login',
        description: 'Authenticate user and get access token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJhbGciOiJIUzI1NiIs...'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function login(LoginRequest $loginRequest, Response $response): Response
    {
        $result = $this->loginAction->execute(
            LoginDTO::fromRequest($loginRequest->validated())
        );

        $json = json_encode($result);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: '/password-recovery',
        operationId: 'passwordRecovery',
        description: "Send password reset link to user's email",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset link sent'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function passwordRecovery(PasswordRecoveryRequest $passwordRecoveryRequest, Response $response): Response
    {
        $this->passwordRecoveryAction->execute(
            PasswordRecoveryDTO::fromRequest($passwordRecoveryRequest->validated())
        );

        $json = json_encode([
            'status' => 'success',
            'message' => 'Password reset link has been sent to your email',
        ]);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response->withHeader('Content-Type', 'application/json');
    }

    #[OA\Post(
        path: '/reset-password',
        operationId: 'resetPassword',
        description: 'Reset password using token from email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'reset-token-from-email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'newpassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'newpassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function updatePassword(ResetPasswordRequest $resetPasswordRequest, Response $response): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($resetPasswordRequest->validated())
        );

        $json = json_encode([
            'status' => 'success',
            'message' => 'Password has been reset successfully',
        ]);
        $response->getBody()->write($json !== false ? $json : '{}');

        return $response->withHeader('Content-Type', 'application/json');
    }
}
