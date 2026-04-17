<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use OpenApi\Annotations\Info;
use OpenApi\Annotations\SecurityScheme;
use OpenApi\Annotations\Server;
use OpenApi\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate OpenAPI/Swagger documentation from annotations.
 */
class GenerateSwaggerCommand extends Command
{
    protected static $defaultName = 'swagger:generate';

    protected function configure(): void
    {
        $this->setDescription('Generate OpenAPI/Swagger documentation from controller annotations')
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output file path',
                __DIR__.'/../../../../public/swagger.json'
            )
            ->addOption(
                'source',
                's',
                InputOption::VALUE_OPTIONAL,
                'Source directory to scan',
                __DIR__.'/../../../../app'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourceDir = realpath($input->getOption('source'));
        $outputFile = $input->getOption('output');

        if ($sourceDir === false) {
            $output->writeln('<error>Source directory not found!</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Generating OpenAPI documentation...</info>');
        $output->writeln(sprintf('<comment>Scanning: %s</comment>', $sourceDir));

        try {
            // Create generator instance
            $generator = new Generator();

            // Generate OpenAPI spec from annotations
            $openapi = $generator->generate([$sourceDir]);

            if (! $openapi instanceof \OpenApi\Annotations\OpenApi) {
                $output->writeln('<error>No OpenAPI annotations found!</error>');

                return Command::FAILURE;
            }

            // Add base info if not present
            // @phpstan-ignore-next-line
            if ($openapi->info === null || empty($openapi->info->title)) {
                $openapi->info = new Info([
                    'title' => 'Slim4MVC API',
                    'version' => '1.0.0',
                    'description' => 'A modern, production-ready starter kit for building web applications with Slim Framework 4',
                ]);
            }

            // Add servers if not present
            if (empty($openapi->servers)) {
                $openapi->servers = [
                    new Server(['url' => '/api/v1', 'description' => 'Local server']),
                ];
            }

            // Add security scheme if not present
            // @phpstan-ignore-next-line
            if (empty($openapi->components->securitySchemes)) {
                /** @var list<SecurityScheme> $schemes */
                $schemes = [
                    new SecurityScheme([
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ]),
                ];
                $openapi->components->securitySchemes = $schemes;
            }

            // Convert to JSON
            $json = $openapi->toJson();

            // Ensure directory exists
            $dir = dirname((string) $outputFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Write to file
            file_put_contents($outputFile, $json);

            $output->writeln(sprintf('<info>✓ Documentation generated: %s</info>', $outputFile));

            // Output statistics
            /** @var array<array-key, mixed> $paths */
            $paths = (array) $openapi->paths;
            /** @var array<array-key, mixed> $schemas */
            $schemas = (array) $openapi->components->schemas;

            $output->writeln('');
            $output->writeln('<comment>Statistics:</comment>');
            $output->writeln('  - Endpoints: '.count($paths));
            $output->writeln('  - Schemas: '.count($schemas));

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $output->writeln(sprintf('<error>Error: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
