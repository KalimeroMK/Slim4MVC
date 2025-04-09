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
use App\Traits\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;

class AuthController extends Controller
{
    use ValidatesRequests;

    public function __construct(
        ContainerInterface $container,
        private readonly RegisterAction $registerAction,
        private readonly LoginAction $loginAction,
        private readonly PasswordRecoveryAction $passwordRecoveryAction,
        private readonly ResetPasswordAction $resetPasswordAction
    ) {
        parent::__construct($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, RegisterRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, RegisterRequest::class);
        $dto = new RegisterDTO($validated['name'], $validated['email'], $validated['password']);

        $user = $this->registerAction->execute($dto);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'user' => $user,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function login(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, LoginRequest::class, true)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, LoginRequest::class);

        $dto = new LoginDTO($validated['email'], $validated['password']);

        $result = $this->loginAction->execute($dto);

        return $response->withJson($result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RandomException
     */
    public function passwordRecovery(Request $request, Response $response): Response
    {
        $validationResponse = $this->validateRequest($request, PasswordRecoveryRequest::class, true);
        if ($validationResponse instanceof Response) {
            return $validationResponse;
        }

        $validated = $this->validatedData($request, PasswordRecoveryRequest::class);

        // Create and process DTO
        $dto = new PasswordRecoveryDTO($validated['email']);
        $this->passwordRecoveryAction->execute($dto);

        return $response->withJson(['message' => 'Password recovery email sent']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function updatePassword(Request $request, Response $response): Response
    {

        $validationResponse = $this->validateRequest($request, ResetPasswordRequest::class, true);
        if ($validationResponse instanceof Response) {
            return $validationResponse;
        }

        $validated = $this->validatedData($request, ResetPasswordRequest::class);
        $dto = new ResetPasswordDTO($validated['token'], $validated['password']);

        $this->resetPasswordAction->execute($dto);

        return $response->withJson(['message' => 'Password successfully reset']);
    }
}
