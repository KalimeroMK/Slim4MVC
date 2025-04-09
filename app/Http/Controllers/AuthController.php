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
use App\Traits\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;
use RuntimeException;

class AuthController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        ContainerInterface $container,
        private readonly RegisterActionInterface $registerAction,
        private readonly WebLoginActionInterface $webLoginAction,
        private readonly PasswordRecoveryActionInterface $passwordRecoveryAction,
        private readonly ResetPasswordAction $resetPasswordAction
    ) {
        parent::__construct($container);
    }

    // Show Forms
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, RegisterRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, RegisterRequest::class);
        $dto = new RegisterDTO($validated['name'], $validated['email'], $validated['password']);

        $this->registerAction->execute($dto);

        return $this->redirect('/login');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function login(Request $request, Response $response): Response
    {
        $validated = $this->validatedData($request, LoginRequest::class);
        $dto = new LoginDTO(
            $validated['email'],
            $validated['password']
        );

        try {
            $this->webLoginAction->execute($dto);

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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function sendPasswordResetLink(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, PasswordRecoveryRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, PasswordRecoveryRequest::class);

        $dto = new PasswordRecoveryDTO($validated['email']);
        $this->passwordRecoveryAction->execute($dto);

        return view('auth.send-reset-password-link-success', $response);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updatePassword(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, ResetPasswordRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, ResetPasswordRequest::class);

        $dto = new ResetPasswordDTO($validated['token'], $validated['password']);

        $this->resetPasswordAction->execute($dto);

        return $this->redirect('/login');
    }
}
