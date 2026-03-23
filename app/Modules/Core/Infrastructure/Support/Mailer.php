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
        $phpMailer = new PHPMailer(true);

        try {
            // SMTP settings
            $phpMailer->isSMTP();
            $phpMailer->Host = $_ENV['MAIL_HOST'];
            $phpMailer->SMTPAuth = true;
            $phpMailer->Username = $_ENV['MAIL_USERNAME'];
            $phpMailer->Password = $_ENV['MAIL_PASSWORD'];
            $phpMailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $phpMailer->Port = (int) $_ENV['MAIL_PORT'];

            $phpMailer->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $phpMailer->addAddress($to);

            $phpMailer->isHTML(true);
            $phpMailer->Subject = $subject;

            $html = $this->blade->make($template, $data);
            $phpMailer->Body = $html;

            return $phpMailer->send();
        } catch (Exception) {
            error_log('MailService error: '.$phpMailer->ErrorInfo);

            return false;
        }
    }
}
