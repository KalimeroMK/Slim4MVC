<?php

namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function register(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        return $response->withJson(['message' => 'User created', 'user' => $user], 201);
    }

    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = User::where('email', $data['email'])->first();

        if (!$user || !password_verify($data['password'], $user->password)) {
            return $response->withJson(['error' => 'Invalid credentials'], 401);
        }

        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'exp' => time() + 60 * 60 * 24 //
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return $response->withJson([
            'user' => $user,
            'token' => $token
        ]);
    }
}
