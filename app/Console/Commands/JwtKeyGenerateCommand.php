<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Infrastructure\Support\Paths;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to generate a secure JWT secret key and write it to .env.
 */
final class JwtKeyGenerateCommand extends Command
{
    /**
     * Returns $content with JWT_SECRET set to $key.
     *
     * Pure string work, kept separate from the command so it can be tested without
     * writing to a real .env.
     */
    public static function withSecret(string $content, string $key): string
    {
        $line = 'JWT_SECRET='.$key;
        $lines = explode("\n", $content);
        $seen = false;

        foreach ($lines as $i => $text) {
            // Presence of the key decides this, not whether it carries a value. Testing for
            // `=.+` treated .env.example's empty `JWT_SECRET=` placeholder as absent, so the
            // key was appended and the file ended up with two JWT_SECRET lines - the empty
            // one winning with the loader, and every fresh clone failing config validation.
            if (! str_starts_with($text, 'JWT_SECRET=')) {
                continue;
            }

            if ($seen) {
                unset($lines[$i]); // repair a file that already went wrong

                continue;
            }

            $lines[$i] = $line;
            $seen = true;
        }

        if ($seen) {
            return implode("\n", $lines);
        }

        // No line at all: put one under the JWT header when there is one. The previous
        // pattern also consumed the line after the header, which silently deleted the
        // explanatory comment .env.example keeps there.
        foreach ($lines as $i => $text) {
            if (str_starts_with($text, '# JWT Configuration')) {
                array_splice($lines, $i + 1, 0, [$line]);

                return implode("\n", $lines);
            }
        }

        return rtrim($content)."\n\n# JWT Configuration\n".$line."\n";
    }

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

        // Only a secret that already carries a value is worth confirming; overwriting
        // .env.example's empty placeholder loses nothing. withSecret() decides
        // replace-vs-append separately, on whether the line is there at all.
        $hasValue = (bool) preg_match('/^JWT_SECRET=.+$/m', $content);

        if ($hasValue && ! $force) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                '<question>JWT_SECRET already exists. Overwrite? [y/N]</question> ',
                false
            );

            if (! $helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Aborted. JWT_SECRET was not changed.</comment>');

                return Command::SUCCESS;
            }
        }

        $updated = self::withSecret($content, $key);

        if (file_put_contents($envPath, $updated) === false) {
            $output->writeln('<error>Failed to write to .env file. Check file permissions.</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>JWT_SECRET set successfully in %s</info>', basename($envPath)));
        $output->writeln(sprintf('<comment>Key (%d-byte / %d-bit): %s</comment>', $length, $length * 8, $key));

        return Command::SUCCESS;
    }

    private function findEnvFile(): ?string
    {
        // Only ever the project's own .env. A second candidate one level higher
        // used to be tried, which could write this secret into a sibling project.
        $path = Paths::root().'/.env';

        return file_exists($path) ? $path : null;
    }
}
