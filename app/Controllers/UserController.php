<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function update(Request $request, Response $response, array $args)
    {
        $user = User::find($args['id']);
        if (! $user) {
            return $response->withJson(['error' => 'User not found'], 404);
        }

        $data = $request->getParsedBody();
        $user->update($data);

        return $response->withJson(['message' => 'User updated', 'user' => $user]);
    }
}
