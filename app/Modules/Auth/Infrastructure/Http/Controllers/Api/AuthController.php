<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\Actions\Auth\LogoutAction;
use App\Modules\Auth\Application\Actions\Auth\PasswordRecoveryAction;
use App\Modules\Auth\Application\Actions\Auth\RefreshTokenAction;
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
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

#[OA\Tag(name: 'Authentication', description: 'Public authentication endpoints')]
class AuthController extends Controller
{
    public function __construct(
        ContainerInterface $container,
        private readonly RegisterAction $registerAction,
        private readonly LoginAction $loginAction,
        private readonly PasswordRecoveryAction $passwordRecoveryAction,
        private readonly ResetPasswordAction $resetPasswordAction,
        private readonly LogoutAction $logoutAction,
        private readonly RefreshTokenAction $refreshTokenAction
    ) {
        parent::__construct($container);
    }

    #[OA\Post(
        path: '/api/v1/register',
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
    public function register(RegisterRequest $registerRequest): Response
    {
        $user = $this->registerAction->execute(
            RegisterDTO::fromRequest($registerRequest->validated())
        );

        return ApiResponse::success($user, HttpStatusCode::CREATED);
    }

    #[OA\Post(
        path: '/api/v1/login',
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
    public function login(LoginRequest $loginRequest): Response
    {
        $result = $this->loginAction->execute(
            LoginDTO::fromRequest($loginRequest->validated())
        );

        return ApiResponse::success([
            'user'  => $result['user'],
            ...$result['token_pair']->toArray(),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/password-recovery',
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
    public function passwordRecovery(PasswordRecoveryRequest $passwordRecoveryRequest): Response
    {
        $this->passwordRecoveryAction->execute(
            PasswordRecoveryDTO::fromRequest($passwordRecoveryRequest->validated())
        );

        return ApiResponse::success(null, HttpStatusCode::OK, 'Password reset link has been sent to your email');
    }

    #[OA\Post(
        path: '/api/v1/reset-password',
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
    public function updatePassword(ResetPasswordRequest $resetPasswordRequest): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($resetPasswordRequest->validated())
        );

        return ApiResponse::success(null, HttpStatusCode::OK, 'Password has been reset successfully');
    }

    #[OA\Post(
        path: '/api/v1/refresh-token',
        operationId: 'refreshToken',
        description: 'Rotate refresh token and issue a new token pair',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string', example: 'eyJhbGci...'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'New token pair issued'),
            new OA\Response(response: 401, description: 'Invalid or revoked refresh token'),
            new OA\Response(response: 429, description: 'Too many requests'),
        ]
    )]
    public function refresh(Request $request): Response
    {
        $body = $request->getParsedBody();
        $refreshToken = is_array($body) ? (string) ($body['refresh_token'] ?? '') : '';

        if ($refreshToken === '') {
            return ApiResponse::badRequest('refresh_token is required');
        }

        try {
            $tokenPair = $this->refreshTokenAction->execute($refreshToken);
        } catch (RuntimeException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }

        return ApiResponse::success($tokenPair->toArray());
    }

    #[OA\Post(
        path: '/api/v1/logout',
        operationId: 'logout',
        description: 'Revoke the current refresh token',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'jti', type: 'string', description: 'Refresh token JTI to revoke'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
        ]
    )]
    public function logout(Request $request): Response
    {
        $body = $request->getParsedBody();
        $jti = is_array($body) ? ($body['jti'] ?? null) : null;

        $this->logoutAction->execute(is_string($jti) ? $jti : null);

        return ApiResponse::success(null, HttpStatusCode::OK, 'Logged out successfully');
    }
}
