<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate a secure JWT secret key and write it to .env.
 */
final class JwtKeyGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('jwt:key:generate')
            ->setDescription('Generate a secure JWT secret key and update .env')
            ->setHelp('Generates a cryptographically secure random key and sets JWT_SECRET in your .env file')
            ->addOption(
                'length',
                'l',
                InputOption::VALUE_REQUIRED,
                'Key length in bytes (default: 64 = 512-bit)',
                64
            )
            ->addOption(
                'show',
                's',
                InputOption::VALUE_NONE,
                'Only print the generated key without writing to .env'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing JWT_SECRET without confirmation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $length = (int) $input->getOption('length');
        $showOnly = (bool) $input->getOption('show');
        $force = (bool) $input->getOption('force');

        if ($length < 32) {
            $output->writeln('<error>Key length must be at least 32 bytes (256-bit).</error>');
            return Command::FAILURE;
        }

        $key = base64_encode(random_bytes($length));

        if ($showOnly) {
            $output->writeln($key);
            return Command::SUCCESS;
        }

        $envPath = $this->findEnvFile();

        if ($envPath === null) {
            $output->writeln('<error>.env file not found. Copy .env.example to .env first.</error>');
            return Command::FAILURE;
        }

        $content = file_get_contents($envPath);

        if ($content === false) {
            $output->writeln('<error>Unable to read .env file.</error>');
            return Command::FAILURE;
        }

        $hasExisting = (bool) preg_match('/^JWT_SECRET=.+$/m', $content);

        if ($hasExisting && !$force) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                '<question>JWT_SECRET already exists. Overwrite? [y/N]</question> ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Aborted. JWT_SECRET was not changed.</comment>');
                return Command::SUCCESS;
            }
        }

        if ($hasExisting) {
            $updated = preg_replace('/^JWT_SECRET=.*$/m', 'JWT_SECRET=' . $key, $content);
        } else {
            // Append after [JWT Configuration] section or at end of file
            if (str_contains($content, '# JWT Configuration')) {
                $updated = preg_replace(
                    '/(# JWT Configuration\s*\n)([^\n]*\n)?/',
                    "$1JWT_SECRET={$key}\n",
                    $content
                );
            } else {
                $updated = rtrim($content) . "\n\n# JWT Configuration\nJWT_SECRET={$key}\n";
            }
        }

        if ($updated === null || file_put_contents($envPath, $updated) === false) {
            $output->writeln('<error>Failed to write to .env file. Check file permissions.</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>JWT_SECRET set successfully in %s</info>', basename($envPath)));
        $output->writeln(sprintf('<comment>Key (%d-byte / %d-bit): %s</comment>', $length, $length * 8, $key));

        return Command::SUCCESS;
    }

    private function findEnvFile(): ?string
    {
        $candidates = [
            dirname(__DIR__, 3) . '/.env',
            dirname(__DIR__, 4) . '/.env',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
