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
use App\Modules\Core\Application\Enums\HttpStatusCode;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
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
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 255),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123', minLength: 8),
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

        return ApiResponse::success($user, HttpStatusCode::CREATED);
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
                    new OA\Property(property: 'password', type: 'string', example: 'password123', minLength: 8),
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

        return ApiResponse::success($result);
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

        return ApiResponse::success(null, HttpStatusCode::OK, 'Password reset link has been sent to your email');
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
                    new OA\Property(property: 'password', type: 'string', example: 'newpassword123', minLength: 8),
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

        return ApiResponse::success(null, HttpStatusCode::OK, 'Password has been reset successfully');
    }
}
