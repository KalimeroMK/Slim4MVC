<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends BaseController
{

    public function index(Request $request, Response $response): Response
    {
        $users = User::all();

        return $response->withJson($users, 200);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $user = User::findOrFail($args['id']);

            $data = $request->getParsedBody();

            $rules = [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email,'.$user->id,
                'password' => 'nullable|min:8', // Password is optional when updating
            ];

            $validation = $this->validator->make($data, $rules);

            if ($validation->fails()) {
                return $response->withJson(['errors' => $validation->errors()->all()], 400);
            }

            $user->name = $data['name'];
            $user->email = $data['email'];

            // If the password is provided, update it
            if (! empty($data['password'])) {
                $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
            }

            $user->save();

            return $response->withJson(['message' => 'User updated successfully', 'user' => $user]);

        } catch (ModelNotFoundException $e) {
            return $response->withJson(['error' => 'User not found'], 404);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $user = User::findOrFail($args['id']);

            // Delete the user from the database
            $user->delete();

            // Return a success response
            return $response->withJson(['message' => 'User deleted successfully'], 200);

        } catch (ModelNotFoundException $e) {
            return $response->withJson(['error' => 'User not found'], 404);
        }
    }
}
