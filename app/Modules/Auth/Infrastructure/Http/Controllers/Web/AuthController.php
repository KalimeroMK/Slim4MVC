<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers\Web;

use App\Modules\Auth\Application\Actions\Auth\ResetPasswordAction;
use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;
use App\Modules\Auth\Application\DTOs\Auth\PasswordRecoveryDTO;
use App\Modules\Auth\Application\DTOs\Auth\RegisterDTO;
use App\Modules\Auth\Application\DTOs\Auth\ResetPasswordDTO;
use App\Modules\Auth\Application\Interfaces\Auth\PasswordRecoveryActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\RegisterActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\WebLoginActionInterface;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\LoginRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\PasswordRecoveryRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\RegisterRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\Auth\ResetPasswordRequest;
use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Support\AuthHelper;
use App\Modules\Core\Infrastructure\Support\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;

class AuthController extends Controller
{
    public function __construct(
        ContainerInterface $container,
        private readonly RegisterActionInterface $registerAction,
        private readonly WebLoginActionInterface $webLoginAction,
        private readonly PasswordRecoveryActionInterface $passwordRecoveryAction,
        private readonly ResetPasswordAction $resetPasswordAction
    ) {
        parent::__construct($container);
    }

    public function showRegisterForm(Request $request, Response $response): Response
    {
        return view('auth.register', $response);
    }

    public function showLoginForm(Request $request, Response $response): Response
    {
        return view('auth.login', $response);
    }

    public function showPasswordResetForm(Request $request, Response $response): Response
    {
        return view('auth.send-reset-password-link', $response);
    }

    /**
     * @param  array<string, mixed>  $token
     */
    public function showPasswordUpdateForm(Request $request, Response $response, array $token): Response
    {
        return view('auth.reset-password', $response, $token);
    }

    public function register(RegisterRequest $registerRequest, Response $response): Response
    {
        $this->registerAction->execute(
            RegisterDTO::fromRequest($registerRequest->validated())
        );

        return $this->redirect(Route::url('login'));
    }

    public function login(LoginRequest $loginRequest, Response $response): Response
    {
        try {
            $this->webLoginAction->execute(
                LoginDTO::fromRequest($loginRequest->validated())
            );

            return $this->redirect(Route::url('dashboard'));
        } catch (RuntimeException) {
            return $this->redirect(Route::url('login') . '?error=invalid_credentials');
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        AuthHelper::logout();

        return $this->redirect(Route::url('home'));
    }

    public function sendPasswordResetLink(PasswordRecoveryRequest $passwordRecoveryRequest, Response $response): Response
    {
        $this->passwordRecoveryAction->execute(
            PasswordRecoveryDTO::fromRequest($passwordRecoveryRequest->validated())
        );

        return $this->redirect(Route::url('login') . '?message=password_reset_sent');
    }

    public function updatePassword(ResetPasswordRequest $resetPasswordRequest, Response $response): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($resetPasswordRequest->validated())
        );

        return $this->redirect(Route::url('login') . '?message=password_reset_success');
    }
}
