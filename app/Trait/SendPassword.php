<?php

declare(strict_types=1);

namespace App\Trait;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

trait SendPassword
{
    protected function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = (int) ($_ENV['MAIL_PORT']);

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'],
                $_ENV['MAIL_FROM_NAME']
            );

            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';

            // Safe APP_URL handling with fallback
            $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:81';
            $resetLink = rtrim($appUrl, '/').'/reset-password?token='.$resetToken;

            $mail->Body = sprintf(
                'To reset your password, please click: <a href="%s">Reset Password</a>',
                $resetLink
            );

            $mail->send();
        } catch (Exception $e) {
            error_log('Mail Error: '.$mail->ErrorInfo);
        }
    }
}
