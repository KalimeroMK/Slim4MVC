<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use App\Modules\Core\Infrastructure\View\Blade;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public function __construct(
        protected Blade $blade
    ) {}

    public function send(
        string $to,
        string $subject,
        string $template,
        array $data = []
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // SMTP settings
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = (int) $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;

            $html = $this->blade->make($template, $data);
            $mail->Body = $html;

            return $mail->send();
        } catch (Exception $e) {
            error_log('MailService error: '.$mail->ErrorInfo);

            return false;
        }
    }
}
