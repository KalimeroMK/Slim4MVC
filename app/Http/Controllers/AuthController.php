<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\ResetPasswordAction;
use App\DTO\Auth\LoginDTO;
use App\DTO\Auth\PasswordRecoveryDTO;
use App\DTO\Auth\RegisterDTO;
use App\DTO\Auth\ResetPasswordDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordRecoveryRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Interface\Auth\RegisterActionInterface;
use App\Interface\Auth\WebLoginActionInterface;
use App\Support\Auth;
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

    public function showPasswordUpdateForm(Request $request, Response $response, array $token): Response
    {
        return view('auth.reset-password', $response, $token);
    }

    public function register(RegisterRequest $request, Response $response): Response
    {
        $this->registerAction->execute(
            RegisterDTO::fromRequest($request->validated())
        );

        return $this->redirect('/login');
    }

    public function login(LoginRequest $request, Response $response): Response
    {
        try {
            $this->webLoginAction->execute(
                LoginDTO::fromRequest($request->validated())
            );

            return $this->redirect('/dashboard');
        } catch (RuntimeException $e) {
            return $this->redirect('/login?error=invalid_credentials');
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        Auth::logout();

        return $this->redirect('/');
    }

    public function sendPasswordResetLink(PasswordRecoveryRequest $request, Response $response): Response
    {
        $this->passwordRecoveryAction->execute(
            PasswordRecoveryDTO::fromRequest($request->validated())
        );

        return view('auth.send-reset-password-link-success', $response);
    }

    public function updatePassword(ResetPasswordRequest $request, Response $response): Response
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($request->validated())
        );

        return $this->redirect('/login');
    }
}
