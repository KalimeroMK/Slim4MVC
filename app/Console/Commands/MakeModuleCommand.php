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
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, 'Model name (defaults to module name)', null)
            ->addOption('migration', null, InputOption::VALUE_NONE, 'Create migration file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = ucfirst($input->getArgument('name'));
        $modelName = $input->getOption('model') ? ucfirst($input->getOption('model')) : $moduleName;
        $createMigration = $input->getOption('migration');

        // Get project root (3 levels up from app/Console/Commands)
        $projectRoot = dirname(__DIR__, 3);
        $modulePath = "$projectRoot/app/Modules/$moduleName";
        $stubPath = "$projectRoot/stubs/Module";

        // Check if module already exists
        if (is_dir($modulePath)) {
            $output->writeln("<error>Module '$moduleName' already exists!</error>");

            return Command::FAILURE;
        }

        $output->writeln("<info>Creating module '$moduleName'...</info>");

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

        $output->writeln("<info>Module '$moduleName' created successfully!</info>");
        $output->writeln('<comment>Module has been automatically registered in bootstrap/modules-register.php</comment>');
        $output->writeln('<comment>Dependencies have been automatically registered in bootstrap/dependencies.php</comment>');

        return Command::SUCCESS;
    }

    private function createDirectories(string $modulePath, OutputInterface $output): void
    {
        $directories = [
            "$modulePath/Application/Actions",
            "$modulePath/Application/DTOs",
            "$modulePath/Application/Services",
            "$modulePath/Application/Interfaces",
            "$modulePath/Infrastructure/Models",
            "$modulePath/Infrastructure/Repositories",
            "$modulePath/Infrastructure/Http/Controllers",
            "$modulePath/Infrastructure/Http/Requests",
            "$modulePath/Infrastructure/Http/Resources",
            "$modulePath/Infrastructure/Providers",
            "$modulePath/Infrastructure/Routes",
            "$modulePath/Exceptions",
            "$modulePath/Observers",
            "$modulePath/Policies",
            "$modulePath/database/migrations",
            "$modulePath/database/factories",
        ];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                $output->writeln("<comment>Created directory: $dir</comment>");
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
        $namespace = "App\\Modules\\$moduleName";
        $lowerModuleName = mb_strtolower($moduleName);
        $lowerModelName = mb_strtolower($modelName);

        $files = [
            // Application Layer
            [
                'stub' => "$stubPath/Application/Actions/CreateAction.stub",
                'dest' => "$modulePath/Application/Actions/Create{$modelName}Action.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Application/Actions/UpdateAction.stub",
                'dest' => "$modulePath/Application/Actions/Update{$modelName}Action.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Application/Actions/DeleteAction.stub",
                'dest' => "$modulePath/Application/Actions/Delete{$modelName}Action.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Application/Actions/GetAction.stub",
                'dest' => "$modulePath/Application/Actions/Get{$modelName}Action.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Application/Actions/ListAction.stub",
                'dest' => "$modulePath/Application/Actions/List{$modelName}Action.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Application/DTOs/CreateDTO.stub",
                'dest' => "$modulePath/Application/DTOs/Create{$modelName}DTO.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Application/DTOs/UpdateDTO.stub",
                'dest' => "$modulePath/Application/DTOs/Update{$modelName}DTO.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            // Interfaces
            [
                'stub' => "$stubPath/Application/Interfaces/CreateActionInterface.stub",
                'dest' => "$modulePath/Application/Interfaces/Create{$modelName}ActionInterface.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Application/Interfaces/UpdateActionInterface.stub",
                'dest' => "$modulePath/Application/Interfaces/Update{$modelName}ActionInterface.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            // Infrastructure Layer
            [
                'stub' => "$stubPath/Infrastructure/Models/Model.stub",
                'dest' => "$modulePath/Infrastructure/Models/{$modelName}.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{tableName}}' => $lowerModelName.'s'],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Repositories/Repository.stub",
                'dest' => "$modulePath/Infrastructure/Repositories/{$modelName}Repository.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Http/Controllers/Controller.stub",
                'dest' => "$modulePath/Infrastructure/Http/Controllers/{$modelName}Controller.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Http/Requests/CreateRequest.stub",
                'dest' => "$modulePath/Infrastructure/Http/Requests/Create{$modelName}Request.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Http/Requests/UpdateRequest.stub",
                'dest' => "$modulePath/Infrastructure/Http/Requests/Update{$modelName}Request.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Http/Resources/Resource.stub",
                'dest' => "$modulePath/Infrastructure/Http/Resources/{$modelName}Resource.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Providers/ServiceProvider.stub",
                'dest' => "$modulePath/Infrastructure/Providers/{$moduleName}ServiceProvider.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName, '{{lowerModuleName}}' => $lowerModuleName],
            ],
            [
                'stub' => "$stubPath/Infrastructure/Routes/api.stub",
                'dest' => "$modulePath/Infrastructure/Routes/api.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{moduleName}}' => $moduleName, '{{lowerModuleName}}' => $lowerModuleName, '{{lowerModelName}}' => $lowerModelName],
            ],
            [
                'stub' => "$stubPath/Policies/Policy.stub",
                'dest' => "$modulePath/Policies/{$modelName}Policy.php",
                'vars' => ['{{namespace}}' => $namespace, '{{modelName}}' => $modelName, '{{lowerModelName}}' => $lowerModelName],
            ],
        ];

        foreach ($files as $file) {
            if (! file_exists($file['stub'])) {
                $output->writeln("<error>Stub not found: {$file['stub']}</error>");

                continue;
            }

            $content = file_get_contents($file['stub']);
            foreach ($file['vars'] as $key => $value) {
                $content = str_replace($key, $value, $content);
            }

            file_put_contents($file['dest'], $content);
            $output->writeln("<info>Created: {$file['dest']}</info>");
        }
    }

    private function createMigration(string $modelName, OutputInterface $output, string $projectRoot): void
    {
        $migrationDir = "$projectRoot/database/migrations/";
        $className = 'Create'.ucfirst($modelName).'Table';
        $tableName = mb_strtolower($modelName).'s';
        $migrationPath = $migrationDir.$className.'.php';

        if (! is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }

        $stubPath = "$projectRoot/stubs/migration.stub";
        if (file_exists($stubPath)) {
            $content = file_get_contents($stubPath);
            $content = str_replace(
                ['{{className}}', '{{tableName}}'],
                [$className, $tableName],
                $content
            );
            file_put_contents($migrationPath, $content);
            $output->writeln("<info>Created migration: $migrationPath</info>");
        }
    }

    private function registerModule(string $moduleName, OutputInterface $output, string $projectRoot): void
    {
        $modulesFile = "$projectRoot/bootstrap/modules-register.php";

        if (! file_exists($modulesFile)) {
            $content = "<?php\n\ndeclare(strict_types=1);\n\n// Register modules\n// This file is auto-generated by make:module command\n// You can manually add or remove modules from this array\n\nreturn [\n    // Add your module service providers here\n];\n";
            file_put_contents($modulesFile, $content);
        }

        $content = file_get_contents($modulesFile);
        $providerClass = "App\\Modules\\$moduleName\\Infrastructure\\Providers\\{$moduleName}ServiceProvider";

        if (mb_strpos($content, $providerClass) === false) {
            // Find the return array and add the provider
            if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
                $arrayContent = mb_trim($matches[1]);

                // Check if array has content (excluding comments)
                $hasContent = ! empty($arrayContent) && ! preg_match('/^\s*\/\/.*$/', $arrayContent);

                if (! $hasContent) {
                    // Empty array or only comments
                    $newContent = preg_replace(
                        '/return\s*\[(.*?)\];/s',
                        "return [\n    $providerClass::class,\n];",
                        $content
                    );
                } else {
                    // Array has content, add after last item
                    $newContent = preg_replace(
                        '/return\s*\[(.*?)\];/s',
                        "return [\n    $providerClass::class,\n$1];",
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
        $depsFile = "$projectRoot/bootstrap/dependencies.php";

        if (! file_exists($depsFile)) {
            $output->writeln('<error>dependencies.php file not found!</error>');

            return;
        }

        $namespace = "App\\Modules\\$moduleName";
        $dependencies = [];

        // Check for CreateAction and CreateActionInterface
        $createActionInterface = "$namespace\\Application\\Interfaces\\Create{$modelName}ActionInterface";
        $createAction = "$namespace\\Application\\Actions\\Create{$modelName}Action";

        // Check if files exist (they should, as we just created them)
        $createActionPath = "$projectRoot/app/Modules/$moduleName/Application/Actions/Create{$modelName}Action.php";
        $createInterfacePath = "$projectRoot/app/Modules/$moduleName/Application/Interfaces/Create{$modelName}ActionInterface.php";

        if (file_exists($createActionPath) && file_exists($createInterfacePath)) {
            $dependencies[] = [
                'interface' => $createActionInterface,
                'implementation' => $createAction,
            ];
        }

        // Check for UpdateAction and UpdateActionInterface
        $updateActionInterface = "$namespace\\Application\\Interfaces\\Update{$modelName}ActionInterface";
        $updateAction = "$namespace\\Application\\Actions\\Update{$modelName}Action";

        $updateActionPath = "$projectRoot/app/Modules/$moduleName/Application/Actions/Update{$modelName}Action.php";
        $updateInterfacePath = "$projectRoot/app/Modules/$moduleName/Application/Interfaces/Update{$modelName}ActionInterface.php";

        if (file_exists($updateActionPath) && file_exists($updateInterfacePath)) {
            $dependencies[] = [
                'interface' => $updateActionInterface,
                'implementation' => $updateAction,
            ];
        }

        if (empty($dependencies)) {
            $output->writeln('<comment>No Action Interfaces found to register</comment>');

            return;
        }

        $content = file_get_contents($depsFile);

        // Collect all use statements to add
        $useStatementsToAdd = [];
        foreach ($dependencies as $dep) {
            if (mb_strpos($content, "use {$dep['interface']};") === false) {
                $useStatementsToAdd[] = "use {$dep['interface']};";
            }
            if (mb_strpos($content, "use {$dep['implementation']};") === false) {
                $useStatementsToAdd[] = "use {$dep['implementation']};";
            }
        }

        // Add use statements if needed
        if (!empty($useStatementsToAdd)) {
            // Find the last use statement
            if (preg_match_all('/^use\s+[^;]+;/m', $content, $matches)) {
                $lastUse = end($matches[0]);
                $lastUsePos = mb_strrpos($content, $lastUse);
                $insertPos = $lastUsePos + mb_strlen($lastUse);
                
                // Insert new use statements after the last one
                $newUseStatements = "\n".implode("\n", $useStatementsToAdd);
                $content = mb_substr($content, 0, $insertPos).$newUseStatements.mb_substr($content, $insertPos);
            } else {
                // No use statements found, add after namespace declaration
                if (preg_match('/(namespace\s+[^;]+;)/', $content, $matches)) {
                    $namespaceLine = $matches[0];
                    $newUseStatements = "\n\n".implode("\n", $useStatementsToAdd);
                    $content = str_replace($namespaceLine, $namespaceLine.$newUseStatements, $content);
                }
            }
        }

        // Add to return array
        $newEntries = [];
        foreach ($dependencies as $dep) {
            $interfaceShort = $this->getShortClassName($dep['interface']);
            $implementationShort = $this->getShortClassName($dep['implementation']);

            // Check if already registered
            if (mb_strpos($content, "{$interfaceShort}::class") === false) {
                $newEntries[] = "    {$interfaceShort}::class => \\DI\\autowire({$implementationShort}::class),";
            }
        }

        if (! empty($newEntries)) {
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
