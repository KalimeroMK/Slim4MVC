<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Random\RandomException;

class AuthController
{
    protected mixed $validator;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->validator = $container->get('validator');
    }

    /**
     * @return mixed
     */
    public function register(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $rules = [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ];

        $validation = $this->validator->make($data, $rules);

        if ($validation->fails()) {
            return $response->withJson(['errors' => $validation->errors()->all()], 400);
        }

        $user = new User();
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->save();

        return $response->withJson(['status' => 'success']);
    }

    /**
     * @return mixed
     */
    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! password_verify($data['password'], $user->password)) {
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

    /**
     * @return mixed
     *
     * @throws RandomException
     */
    public function passwordRecovery(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $email = $data['email'];

        // Validate email format
        if (! v::email()->validate($email)) {
            return $response->withJson(['error' => 'Invalid email address'], 400);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return $response->withJson(['error' => 'Email not found'], 404);
        }

        // Generate password reset token
        $resetToken = bin2hex(random_bytes(16)); // Secure random token

        // Save the token in the database (you should also add an expiration date for the token)
        $user->password_reset_token = $resetToken;
        $user->save();

        // Send the reset link via email
        $this->sendPasswordResetEmail($user->email, $resetToken);

        return $response->withJson(['message' => 'Password recovery email sent'], 200);
    }

    /**
     * @return mixed
     */
    public function resetPassword(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $resetToken = $data['token'];
        $newPassword = $data['password'];

        // Validate the reset token
        $user = User::where('password_reset_token', $resetToken)->first();

        if (! $user) {
            return $response->withJson(['error' => 'Invalid or expired reset token'], 400);
        }

        // Update password
        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->password_reset_token = null; // Clear the reset token
        $user->save();

        return $response->withJson(['message' => 'Password successfully reset'], 200);
    }

    private function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST'); // SMTP server from .env
            $mail->SMTPAuth = true;
            $mail->Username = getenv('MAIL_USERNAME'); // SMTP username from .env
            $mail->Password = getenv('MAIL_PASSWORD'); // SMTP password from .env
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('MAIL_PORT'); // SMTP port from .env

            // Recipients
            $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME'));
            $mail->addAddress($email); // User email to send the reset link

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $resetLink = getenv('MAIL_PORT').'/reset-password?token='.$resetToken;
            $mail->Body = 'To reset your password, please click the following link: <a href="'.$resetLink.'">Reset Password</a>';

            // Send the email
            $mail->send();
        } catch (Exception $e) {
            // Log error if mail fails
            error_log('Message could not be sent. Mailer Error: '.$mail->ErrorInfo);
        }
    }
}
