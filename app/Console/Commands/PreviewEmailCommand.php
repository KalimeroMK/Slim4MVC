<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Mail\Mailable;
use App\Modules\Core\Infrastructure\Mail\PasswordResetEmail;
use App\Modules\Core\Infrastructure\Mail\WelcomeEmail;
use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use App\Modules\User\Infrastructure\Database\Factories\UserFactory;
use App\Modules\User\Infrastructure\Models\User;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to preview email templates.
 */
class PreviewEmailCommand extends Command
{
    protected static $defaultName = 'email:preview';

    protected function configure(): void
    {
        $this->setDescription('Preview email templates')
            ->addArgument('type', InputArgument::REQUIRED, 'Email type (welcome, password-reset)')
            ->addArgument('output', InputArgument::OPTIONAL, 'Output file path (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        $outputPath = $input->getArgument('output');

        // Setup Blade
        $viewsPath = dirname(__DIR__, 3).'/resources/views';
        $cachePath = dirname(__DIR__, 3).'/storage/cache/view';
        $blade = new Blade($viewsPath, $cachePath);

        // Setup Mailer (not used for preview, but required for Mailable)
        $mailer = new Mailer($blade);

        // Create test user
        $userFactory = new UserFactory();
        $user = $userFactory->make([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create mailable based on type
        $mailable = match ($type) {
            'welcome' => new WelcomeEmail($mailer, $blade, $user),
            'password-reset' => new PasswordResetEmail($mailer, $blade, $user, 'test-reset-token-123'),
            default => throw new InvalidArgumentException("Unknown email type: {$type}"),
        };

        // Generate preview
        $html = $mailable->preview();

        if ($outputPath) {
            file_put_contents($outputPath, $html);
            $output->writeln("<info>Email preview saved to: {$outputPath}</info>");
        } else {
            $output->writeln($html);
        }

        return Command::SUCCESS;
    }
}
