<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordRecoveryRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Trait\ValidatesRequests;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends Controller
{
    use ValidatesRequests;

    public function register(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, RegisterRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, RegisterRequest::class);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
        ]);

        return $response->withJson([
            'status' => 'success',
            'user' => $user,
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, LoginRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, LoginRequest::class);
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! password_verify($validated['password'], $user->password)) {
            return $response->withJson(['error' => 'Invalid credentials'], 401);
        }

        $payload = [
            'id' => $user->id,
            'email' => $user->email,
            'exp' => time() + 60 * 60 * 24, // 24 hours expiration
        ];

        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        return $response->withJson([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function passwordRecovery(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, PasswordRecoveryRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, PasswordRecoveryRequest::class);
        $user = User::where('email', $validated['email'])->first();

        $resetToken = bin2hex(random_bytes(16));
        $user->password_reset_token = $resetToken;
        $user->save();

        $this->sendPasswordResetEmail($user->email, $resetToken);

        return $response->withJson(['message' => 'Password recovery email sent']);
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        if (($errorResponse = $this->validateRequest($request, ResetPasswordRequest::class)) instanceof Response) {
            return $errorResponse;
        }

        $validated = $this->validatedData($request, ResetPasswordRequest::class);
        $user = User::where('password_reset_token', $validated['token'])->first();

        $user->password = password_hash($validated['password'], PASSWORD_DEFAULT);
        $user->password_reset_token = null;
        $user->save();

        return $response->withJson(['message' => 'Password successfully reset']);
    }

    private function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $resetLink = $_ENV['APP_URL'].'/reset-password?token='.$resetToken;
            $mail->Body = 'To reset your password, please click: <a href="'.$resetLink.'">Reset Password</a>';

            $mail->send();
        } catch (Exception $e) {
            error_log('Mail Error: '.$mail->ErrorInfo);
        }
    }
}
