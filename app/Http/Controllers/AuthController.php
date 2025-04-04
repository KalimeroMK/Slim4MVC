<?php

// app/Http/Controllers/AuthController.php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\Auth;
use App\Trait\ValidatesRequests;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends Controller
{
    use ValidatesRequests;

    /**
     * @var Auth|mixed
     */
    private mixed $auth;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->auth = $container->get(Auth::class);
    }

    public function showRegisterForm(Request $request, Response $response): Response
    {
        return view('auth.register', $response);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function register(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, RegisterRequest::class)) instanceof \Psr\Http\Message\ResponseInterface) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, RegisterRequest::class);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
        ]);

        $this->auth->attempt($user->email, $validated['password']);

        return $this->redirect('/dashboard');
    }

    public function showLoginForm(Request $request, Response $response): Response
    {
        return view('auth.login', $response);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function login(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, LoginRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $credentials = $this->validatedData($request, LoginRequest::class);

        if (! Auth::attempt($credentials['email'], $credentials['password'])) {
            $_SESSION['error'] = 'Invalid credentials';

            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        Auth::logout();

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }
}
