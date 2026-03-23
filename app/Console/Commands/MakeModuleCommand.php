<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModuleCommand extends Command
{
    protected static $defaultName = 'make:module';

    protected function configure(): void
    {
        $this->setDescription('Create a new module with complete structure')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the module (e.g., Product, Blog)')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'Model name (defaults to module name)')
            ->addOption('migration', null, InputOption::VALUE_NONE, 'Create migration file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = ucfirst((string) $input->getArgument('name'));
        $modelName = $input->getOption('model') ? ucfirst((string) $input->getOption('model')) : $moduleName;
        $createMigration = $input->getOption('migration');

        // Get project root (3 levels up from app/Console/Commands)
        $projectRoot = dirname(__DIR__, 3);
        $modulePath = sprintf('%s/app/Modules/%s', $projectRoot, $moduleName);
        $stubPath = $projectRoot.'/stubs/Module';

        // Check if module already exists
        if (is_dir($modulePath)) {
            $output->writeln(sprintf("<error>Module '%s' already exists!</error>", $moduleName));

            return Command::FAILURE;
        }

        $output->writeln(sprintf("<info>Creating module '%s'...</info>", $moduleName));

        // Create directory structure
        $this->createDirectories($modulePath, $output);

        // Create files from stubs
        $this->createFiles($moduleName, $modelName, $modulePath, $stubPath, $output);

        // Create migration if requested
        if ($createMigration) {
            $this->createMigration($modelName, $output, $projectRoot);
        }

        // Create service provider registration
        $this->registerModule($moduleName, $output, $projectRoot);

        // Register dependencies (Action Interfaces and Repositories)
        $this->registerDependencies($moduleName, $modelName, $output, $projectRoot);

        $output->writeln(sprintf("<info>Module '%s' created successfully!</info>", $moduleName));
        $output->writeln('<comment>Module has been automatically registered in bootstrap/modules-register.php</comment>');
        $output->writeln('<comment>Dependencies have been automatically registered in bootstrap/dependencies.php</comment>');

        return Command::SUCCESS;
    }

    private function createDirectories(string $modulePath, OutputInterface $output): void
    {
        $directories = [
            $modulePath.'/Application/Actions',
            $modulePath.'/Application/DTOs',
            $modulePath.'/Application/Services',
            $modulePath.'/Application/Interfaces',
            $modulePath.'/Infrastructure/Models',
            $modulePath.'/Infrastructure/Repositories',
            $modulePath.'/Infrastructure/Http/Controllers',
            $modulePath.'/Infrastructure/Http/Requests',
            $modulePath.'/Infrastructure/Http/Resources',
            $modulePath.'/Infrastructure/Providers',
            $modulePath.'/Infrastructure/Routes',
            $modulePath.'/Exceptions',
            $modulePath.'/Observers',
            $modulePath.'/Policies',
            $modulePath.'/database/migrations',
            $modulePath.'/database/factories',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
                $output->writeln(sprintf('<comment>Created directory: %s</comment>', $directory));
            }
        }
    }

    private function createFiles(
        string $moduleName,
        string $modelName,
        string $modulePath,
        string $stubPath,
        OutputInterface $output
    ): void {
        $namespace = 'App\Modules\\'.$moduleName;
        $lowerModuleName = mb_strtolower($moduleName);
        $lowerModelName = mb_strtolower($modelName);

        $files = [
            // Application Layer
            [
                'stub' => $stubPath.'/Application/Actions/CreateAction.stub',
                'dest' => sprintf('%s/Application/Actions/Create%sAction.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => $stubPath.'/Application/Actions/UpdateAction.stub',
                'dest' => sprintf('%s/Application/Actions/Update%sAction.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => $stubPath.'/Application/Actions/DeleteAction.stub',
                'dest' => sprintf('%s/Application/Actions/Delete%sAction.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => $stubPath.'/Application/Actions/GetAction.stub',
                'dest' => sprintf('%s/Application/Actions/Get%sAction.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => $stubPath.'/Application/Actions/ListAction.stub',
                'dest' => sprintf('%s/Application/Actions/List%sAction.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => $stubPath.'/Application/DTOs/CreateDTO.stub',
                'dest' => sprintf('%s/Application/DTOs/Create%sDTO.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => $stubPath.'/Application/DTOs/UpdateDTO.stub',
                'dest' => sprintf('%s/Application/DTOs/Update%sDTO.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            // Interfaces
            [
                'stub' => $stubPath.'/Application/Interfaces/CreateActionInterface.stub',
                'dest' => sprintf('%s/Application/Interfaces/Create%sActionInterface.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => $stubPath.'/Application/Interfaces/UpdateActionInterface.stub',
                'dest' => sprintf('%s/Application/Interfaces/Update%sActionInterface.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            // Infrastructure Layer
            [
                'stub' => $stubPath.'/Infrastructure/Models/Model.stub',
                'dest' => sprintf('%s/Infrastructure/Models/%s.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{tableName}}' => $lowerModelName.'s'],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Repositories/Repository.stub',
                'dest' => sprintf('%s/Infrastructure/Repositories/%sRepository.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Http/Controllers/Controller.stub',
                'dest' => sprintf('%s/Infrastructure/Http/Controllers/%sController.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName, '{{lowerModelName}}' => $lowerModelName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Http/Requests/CreateRequest.stub',
                'dest' => sprintf('%s/Infrastructure/Http/Requests/Create%sRequest.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Http/Requests/UpdateRequest.stub',
                'dest' => sprintf('%s/Infrastructure/Http/Requests/Update%sRequest.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Http/Resources/Resource.stub',
                'dest' => sprintf('%s/Infrastructure/Http/Resources/%sResource.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{lowerModelName}}' => $lowerModelName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Providers/ServiceProvider.stub',
                'dest' => sprintf('%s/Infrastructure/Providers/%sServiceProvider.php', $modulePath, $moduleName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName, '{{lowerModuleName}}' => $lowerModuleName],
            ],
            [
                'stub' => $stubPath.'/Infrastructure/Routes/api.stub',
                'dest' => $modulePath.'/Infrastructure/Routes/api.php',
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName, '{{lowerModuleName}}' => $lowerModuleName, '{{lowerModelName}}' => $lowerModelName],
            ],
            [
                'stub' => $stubPath.'/Policies/Policy.stub',
                'dest' => sprintf('%s/Policies/%sPolicy.php', $modulePath, $modelName),
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{lowerModelName}}' => $lowerModelName],
            ],
        ];

        foreach ($files as $file) {
            if (! file_exists($file['stub'])) {
                $output->writeln(sprintf('<error>Stub not found: %s</error>', $file['stub']));

                continue;
            }

            $content = file_get_contents($file['stub']);
            foreach ($file['vars'] as $key => $value) {
                $content = str_replace($key, $value, $content);
            }

            file_put_contents($file['dest'], $content);
            $output->writeln(sprintf('<info>Created: %s</info>', $file['dest']));
        }
    }

    private function createMigration(string $modelName, OutputInterface $output, string $projectRoot): void
    {
        $migrationDir = $projectRoot.'/database/migrations/';
        $className = 'Create'.ucfirst($modelName).'Table';
        $tableName = mb_strtolower($modelName).'s';
        $migrationPath = $migrationDir.$className.'.php';

        if (! is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }

        $stubPath = $projectRoot.'/stubs/migration.stub';
        if (file_exists($stubPath)) {
            $content = file_get_contents($stubPath);
            $content = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$className, $tableName],
                $content
            );
            file_put_contents($migrationPath, $content);
            $output->writeln(sprintf('<info>Created migration: %s</info>', $migrationPath));
        }
    }

    private function registerModule(string $moduleName, OutputInterface $output, string $projectRoot): void
    {
        $modulesFile = $projectRoot.'/bootstrap/modules-register.php';

        if (! file_exists($modulesFile)) {
            $content = "<?php\n\ndeclare(strict_types=1);\n\n// Register modules\n// This file is auto-generated by make:module command\n// You can manually add or remove modules from this array\n\nreturn [\n    // Add your module service providers here\n];\n";
            file_put_contents($modulesFile, $content);
        }

        $content = file_get_contents($modulesFile);
        $providerClass = sprintf('App\Modules\%s\Infrastructure\Providers\%sServiceProvider', $moduleName, $moduleName);

        if (mb_strpos($content, $providerClass) === false) {
            // Find the return array and add the provider
            if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
                $arrayContent = mb_trim($matches[1]);

                // Check if array has content (excluding comments)
                $hasContent = $arrayContent !== '' && $arrayContent !== '0' && ! preg_match('/^\s*\/\/.*$/', $arrayContent);

                if (! $hasContent) {
                    // Empty array or only comments
                    $newContent = preg_replace(
                        '/return\s*\[(.*?)\];/s',
                        "return [\n    {$providerClass}::class,\n];",
                        $content
                    );
                } else {
                    // Array has content, add after last item
                    $newContent = preg_replace(
                        '/return\s*\[(.*?)\];/s',
                        "return [\n    {$providerClass}::class,\n$1];",
                        $content
                    );
                }

                file_put_contents($modulesFile, $newContent);
                $output->writeln('<info>Module registered in bootstrap/modules-register.php</info>');
            }
        } else {
            $output->writeln('<comment>Module already registered in bootstrap/modules-register.php</comment>');
        }
    }

    /**
     * Register module dependencies (Action Interfaces) in bootstrap/dependencies.php.
     */
    private function registerDependencies(
        string $moduleName,
        string $modelName,
        OutputInterface $output,
        string $projectRoot
    ): void {
        $depsFile = $projectRoot.'/bootstrap/dependencies.php';

        if (! file_exists($depsFile)) {
            $output->writeln('<error>dependencies.php file not found!</error>');

            return;
        }

        $namespace = 'App\Modules\\'.$moduleName;
        $dependencies = [];

        // Check for CreateAction and CreateActionInterface
        $createActionInterface = sprintf('%s\Application\Interfaces\Create%sActionInterface', $namespace, $modelName);
        $createAction = sprintf('%s\Application\Actions\Create%sAction', $namespace, $modelName);

        // Check if files exist (they should, as we just created them)
        $createActionPath = sprintf('%s/app/Modules/%s/Application/Actions/Create%sAction.php', $projectRoot, $moduleName, $modelName);
        $createInterfacePath = sprintf('%s/app/Modules/%s/Application/Interfaces/Create%sActionInterface.php', $projectRoot, $moduleName, $modelName);

        if (file_exists($createActionPath) && file_exists($createInterfacePath)) {
            $dependencies[] = [
                'interface' => $createActionInterface,
                'implementation' => $createAction,
            ];
        }

        // Check for UpdateAction and UpdateActionInterface
        $updateActionInterface = sprintf('%s\Application\Interfaces\Update%sActionInterface', $namespace, $modelName);
        $updateAction = sprintf('%s\Application\Actions\Update%sAction', $namespace, $modelName);

        $updateActionPath = sprintf('%s/app/Modules/%s/Application/Actions/Update%sAction.php', $projectRoot, $moduleName, $modelName);
        $updateInterfacePath = sprintf('%s/app/Modules/%s/Application/Interfaces/Update%sActionInterface.php', $projectRoot, $moduleName, $modelName);

        if (file_exists($updateActionPath) && file_exists($updateInterfacePath)) {
            $dependencies[] = [
                'interface' => $updateActionInterface,
                'implementation' => $updateAction,
            ];
        }

        if ($dependencies === []) {
            $output->writeln('<comment>No Action Interfaces found to register</comment>');

            return;
        }

        $content = file_get_contents($depsFile);

        // Collect all use statements to add
        $useStatementsToAdd = [];
        foreach ($dependencies as $dep) {
            if (mb_strpos($content, sprintf('use %s;', $dep['interface'])) === false) {
                $useStatementsToAdd[] = sprintf('use %s;', $dep['interface']);
            }

            if (mb_strpos($content, sprintf('use %s;', $dep['implementation'])) === false) {
                $useStatementsToAdd[] = sprintf('use %s;', $dep['implementation']);
            }
        }

        // Add use statements if needed
        if ($useStatementsToAdd !== []) {
            // Find the last use statement
            if (preg_match_all('/^use\s+[^;]+;/m', $content, $matches)) {
                $lastUse = end($matches[0]);
                $lastUsePos = mb_strrpos($content, $lastUse);
                $insertPos = $lastUsePos + mb_strlen($lastUse);
                // Insert new use statements after the last one
                $newUseStatements = "\n".implode("\n", $useStatementsToAdd);
                $content = mb_substr($content, 0, $insertPos).$newUseStatements.mb_substr($content, $insertPos);
            } elseif (preg_match('/(namespace\s+[^;]+;)/', $content, $matches)) {
                // No use statements found, add after namespace declaration
                $namespaceLine = $matches[0];
                $newUseStatements = "\n\n".implode("\n", $useStatementsToAdd);
                $content = str_replace($namespaceLine, $namespaceLine.$newUseStatements, $content);
            }
        }

        // Add to return array
        $newEntries = [];
        foreach ($dependencies as $dependency) {
            $interfaceShort = $this->getShortClassName($dependency['interface']);
            $implementationShort = $this->getShortClassName($dependency['implementation']);

            // Check if already registered
            if (mb_strpos($content, $interfaceShort.'::class') === false) {
                $newEntries[] = sprintf('    %s::class => \DI\autowire(%s::class),', $interfaceShort, $implementationShort);
            }
        }

        if ($newEntries !== []) {
            // Find the return array and add entries before the closing bracket
            if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
                $arrayContent = $matches[1];
                $indent = '    ';

                // Add new entries before closing bracket
                $newContent = preg_replace(
                    '/return\s*\[(.*?)\];/s',
                    'return ['.$arrayContent."\n".implode("\n", $newEntries)."\n];",
                    $content
                );

                file_put_contents($depsFile, $newContent);
                $output->writeln('<info>Action Interfaces registered in bootstrap/dependencies.php</info>');
            }
        } else {
            $output->writeln('<comment>Action Interfaces already registered in bootstrap/dependencies.php</comment>');
        }
    }

    /**
     * Get short class name from full namespace.
     */
    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);

        return end($parts);
    }
}
