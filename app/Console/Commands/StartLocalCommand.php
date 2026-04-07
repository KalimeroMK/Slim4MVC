<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to start the local development environment.
 * Sets up everything needed: .env, dependencies, Docker containers, migrations, and seeders.
 */
final class StartLocalCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('start-local')
            ->setDescription('Start the local development environment (setup everything)')
            ->setHelp('Copies .env, generates JWT key, installs dependencies, starts Docker containers, runs migrations and seeders')
            ->addOption(
                'force-env',
                'f',
                InputOption::VALUE_NONE,
                'Force regenerate .env file from .env.example (WARNING: will overwrite existing .env)'
            )
            ->addOption(
                'no-seed',
                null,
                InputOption::VALUE_NONE,
                'Skip database seeding'
            )
            ->addOption(
                'rebuild',
                'r',
                InputOption::VALUE_NONE,
                'Rebuild Docker containers (docker-compose up --build)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $forceEnv = (bool) $input->getOption('force-env');
        $noSeed = (bool) $input->getOption('no-seed');
        $rebuild = (bool) $input->getOption('rebuild');

        $io->title('🚀 Starting Slim4MVC Local Development Environment');

        // 1. Setup .env file
        if (!$this->setupEnvFile($io, $forceEnv)) {
            return Command::FAILURE;
        }

        // 2. Generate JWT Secret
        if (!$this->generateJwtSecret($io)) {
            return Command::FAILURE;
        }

        // 3. Composer Update
        if (!$this->runComposerUpdate($io)) {
            return Command::FAILURE;
        }

        // 4. Start Docker Containers
        if (!$this->startDockerContainers($io, $rebuild)) {
            return Command::FAILURE;
        }

        // 5. Run Migrations
        if (!$this->runMigrations($io)) {
            return Command::FAILURE;
        }

        // 6. Run Seeders (unless --no-seed)
        if (!$noSeed) {
            if (!$this->runSeeders($io)) {
                return Command::FAILURE;
            }
        } else {
            $io->note('Skipping database seeding (--no-seed option used)');
        }

        // 7. Display success information
        $this->displaySuccessMessage($io);

        return Command::SUCCESS;
    }

    private function setupEnvFile(SymfonyStyle $io, bool $force): bool
    {
        $io->section('📋 Step 1: Setting up .env file');

        $envPath = $this->getProjectRoot() . '/.env';
        $envExamplePath = $this->getProjectRoot() . '/.env.example';

        if (!file_exists($envExamplePath)) {
            $io->error('.env.example file not found!');
            return false;
        }

        if (file_exists($envPath)) {
            if ($force) {
                $io->warning('Overwriting existing .env file (--force-env option used)');
                if (!copy($envExamplePath, $envPath)) {
                    $io->error('Failed to copy .env.example to .env');
                    return false;
                }
                $io->success('.env file regenerated from .env.example');
            } else {
                $io->info('.env file already exists (use --force-env to overwrite)');
            }
        } else {
            if (!copy($envExamplePath, $envPath)) {
                $io->error('Failed to copy .env.example to .env');
                return false;
            }
            $io->success('Created .env file from .env.example');
        }

        return true;
    }

    private function generateJwtSecret(SymfonyStyle $io): bool
    {
        $io->section('🔐 Step 2: Generating JWT Secret');

        $envPath = $this->getProjectRoot() . '/.env';
        $content = file_get_contents($envPath);

        if ($content === false) {
            $io->error('Unable to read .env file');
            return false;
        }

        // Check if JWT_SECRET already exists and has a valid value
        if (preg_match('/^JWT_SECRET=(.+)$/m', $content, $matches)) {
            $existingSecret = trim($matches[1]);
            if ($existingSecret !== '' && $existingSecret !== 'supersecretkey123') {
                $io->info('JWT_SECRET already configured in .env');
                return true;
            }
        }

        // Generate a new secure key
        $key = base64_encode(random_bytes(64));

        // Update or add JWT_SECRET
        if (preg_match('/^JWT_SECRET=.*$/m', $content)) {
            $updated = preg_replace('/^JWT_SECRET=.*$/m', 'JWT_SECRET=' . $key, $content);
        } else {
            $updated = $content . "\n# JWT Configuration\nJWT_SECRET=" . $key . "\n";
        }

        if ($updated === null || file_put_contents($envPath, $updated) === false) {
            $io->error('Failed to write JWT_SECRET to .env file');
            return false;
        }

        $io->success('JWT_SECRET generated and saved to .env');
        return true;
    }

    private function runComposerUpdate(SymfonyStyle $io): bool
    {
        $io->section('📦 Step 3: Installing/Updating Composer dependencies');

        $projectRoot = $this->getProjectRoot();

        // Check if composer is available
        $composerCommand = $this->findComposer();
        if ($composerCommand === null) {
            $io->error('Composer not found! Please install Composer: https://getcomposer.org/download/');
            return false;
        }

        $io->info('Running: composer update');

        $process = proc_open(
            $composerCommand . ' update --no-interaction --working-dir=' . escapeshellarg($projectRoot),
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            $io->error('Failed to start composer update process');
            return false;
        }

        // Stream output
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            $stdout = fread($pipes[1], 1024);
            $stderr = fread($pipes[2], 1024);

            if ($stdout) {
                $io->write($stdout);
            }
            if ($stderr) {
                $io->write($stderr);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $io->error('Composer update failed with exit code: ' . $exitCode);
            return false;
        }

        $io->success('Composer dependencies installed/updated successfully');
        return true;
    }

    private function startDockerContainers(SymfonyStyle $io, bool $rebuild): bool
    {
        $io->section('🐳 Step 4: Starting Docker containers');

        $projectRoot = $this->getProjectRoot();

        // Check if docker-compose is available
        if (!$this->commandExists('docker-compose') && !$this->commandExists('docker')) {
            $io->error('Docker Compose not found! Please install Docker: https://docs.docker.com/get-docker/');
            return false;
        }

        // Determine docker compose command (new 'docker compose' or legacy 'docker-compose')
        $dockerComposeCmd = $this->getDockerComposeCommand();

        // Build command
        $cmd = $dockerComposeCmd . ' -f ' . escapeshellarg($projectRoot . '/docker-compose.yml') . ' up -d';
        if ($rebuild) {
            $cmd .= ' --build';
            $io->info('Rebuilding and starting containers...');
        } else {
            $io->info('Starting containers...');
        }

        $process = proc_open(
            $cmd,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            $io->error('Failed to start docker-compose process');
            return false;
        }

        // Stream output
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            $stdout = fread($pipes[1], 1024);
            $stderr = fread($pipes[2], 1024);

            if ($stdout) {
                $io->write($stdout);
            }
            if ($stderr) {
                $io->write($stderr);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $io->error('Docker Compose failed with exit code: ' . $exitCode);
            return false;
        }

        // Wait for containers to be healthy
        $io->info('Waiting for containers to be healthy...');
        sleep(5);

        $io->success('Docker containers started successfully');
        return true;
    }

    private function runMigrations(SymfonyStyle $io): bool
    {
        $io->section('🗄️  Step 5: Running database migrations');

        $projectRoot = $this->getProjectRoot();
        $migrateScript = $projectRoot . '/run_migrations.php';

        if (!file_exists($migrateScript)) {
            $io->error('Migration script not found: ' . $migrateScript);
            return false;
        }

        $io->info('Running migrations...');

        $process = proc_open(
            'php ' . escapeshellarg($migrateScript) . ' migrate',
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            $io->error('Failed to start migration process');
            return false;
        }

        // Stream output
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            $stdout = fread($pipes[1], 1024);
            $stderr = fread($pipes[2], 1024);

            if ($stdout) {
                $io->write($stdout);
            }
            if ($stderr) {
                $io->write($stderr);
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $io->error('Migration failed with exit code: ' . $exitCode);
            return false;
        }

        $io->success('Database migrations completed successfully');
        return true;
    }

    private function runSeeders(SymfonyStyle $io): bool
    {
        $io->section('🌱 Step 6: Running database seeders');

        $projectRoot = $this->getProjectRoot();
        $seedScript = $projectRoot . '/database/seed/seed.php';

        if (!file_exists($seedScript)) {
            $io->warning('Seed script not found, skipping...');
            return true;
        }

        $io->info('Running seeders...');

        // First load the database bootstrap
        $bootstrapScript = $projectRoot . '/bootstrap/database.php';
        if (!file_exists($bootstrapScript)) {
            $io->error('Database bootstrap not found: ' . $bootstrapScript);
            return false;
        }

        // Change to project root for proper path resolution
        $originalCwd = getcwd();
        chdir($projectRoot);

        // Capture output
        ob_start();
        try {
            require $bootstrapScript;
            require $seedScript;
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            $output = ob_get_clean();
            $io->error('Seeding failed: ' . $e->getMessage());
            chdir($originalCwd);
            return false;
        }

        chdir($originalCwd);

        if ($output) {
            $io->write($output);
        }

        $io->success('Database seeding completed successfully');
        return true;
    }

    private function displaySuccessMessage(SymfonyStyle $io): void
    {
        $io->newLine(2);
        $io->success('✅ Slim4MVC Local Development Environment is ready!');

        $io->section('🌐 Application URLs');
        $io->listing([
            'Web Application: http://localhost',
            'API Base URL: http://localhost/api',
        ]);

        $io->section('📚 Available Commands');
        $io->listing([
            './slim start-local      - Start/restart the entire environment',
            './slim start-local -r   - Rebuild Docker containers',
            './slim start-local -f   - Force regenerate .env file',
            './slim jwt:key:generate - Generate new JWT secret',
            './slim db:seed          - Run database seeders',
            'php run_migrations.php migrate   - Run migrations',
            'php run_migrations.php rollback  - Rollback migrations',
        ]);

        $io->section('🐳 Docker Commands');
        $io->listing([
            'docker-compose ps       - Check container status',
            'docker-compose logs -f  - View container logs',
            'docker-compose down     - Stop all containers',
        ]);

        $io->note('Happy coding! 🚀');
    }

    private function getProjectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private function findComposer(): ?string
    {
        // Check for composer in common locations
        $possiblePaths = [
            'composer',
            'composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            $this->getProjectRoot() . '/composer.phar',
        ];

        foreach ($possiblePaths as $path) {
            if ($path === 'composer' || $path === 'composer.phar') {
                exec('which ' . $path . ' 2>/dev/null', $output, $returnCode);
                if ($returnCode === 0) {
                    return $path;
                }
            } elseif (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function commandExists(string $command): bool
    {
        exec('which ' . $command . ' 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    private function getDockerComposeCommand(): string
    {
        // Check for new 'docker compose' plugin
        exec('docker compose version 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0) {
            return 'docker compose';
        }

        // Fall back to legacy 'docker-compose'
        return 'docker-compose';
    }
}
