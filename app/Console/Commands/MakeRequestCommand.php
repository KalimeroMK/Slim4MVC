<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeRequestCommand extends Command
{
    protected static $defaultName = 'make:request';

    protected function configure(): void
    {
        $this->setDescription('Create a new form request class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the request class (e.g., User/CreateUserRequest)')
            ->addOption('model', 'm', InputOption::VALUE_REQUIRED, 'The model class to extract fields from (e.g., User)')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Request type: create or update', 'create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $modelName = $input->getOption('model');
        $type = $input->getOption('type');

        $projectRoot = dirname(__DIR__, 2);
        $stubPath = $projectRoot.'/stubs/Request/'.ucfirst($type).'Request';
        
        // Check if stub exists, if not try alternative path
        if (!file_exists($stubPath)) {
            $stubPath = __DIR__.'/../../../stubs/Request/'.ucfirst($type).'Request';
        }

        if (!file_exists($stubPath)) {
            $output->writeln("<error>Stub file not found: $stubPath</error>");

            return Command::FAILURE;
        }

        // Parse name (e.g., "User/CreateUserRequest" or "CreateUserRequest")
        $parts = explode('/', $name);
        if (count($parts) === 2) {
            $namespace = $parts[0];
            $className = $parts[1];
        } else {
            // Try to extract namespace from class name
            if (preg_match('/^Create(.+)Request$/', $name, $matches)) {
                $namespace = $matches[1];
                $className = $name;
            } elseif (preg_match('/^Update(.+)Request$/', $name, $matches)) {
                $namespace = $matches[1];
                $className = $name;
            } else {
                $namespace = 'User';
                $className = $name;
            }
        }

        // Fix path - projectRoot already points to root, not app directory
        $destination = "$projectRoot/app/Http/Requests/$namespace/$className.php";
        
        // If projectRoot already contains app, adjust
        if (str_ends_with($projectRoot, '/app')) {
            $destination = dirname($projectRoot)."/app/Http/Requests/$namespace/$className.php";
        }

        if (file_exists($destination)) {
            $output->writeln("<error>Request already exists: $destination</error>");

            return Command::FAILURE;
        }

        // Create directory if needed
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Get stub content
        $content = file_get_contents($stubPath);

        // Replace placeholders
        $content = str_replace('{{controllerName}}', $namespace, $content);
        $content = str_replace('{{className}}', $className, $content);

        // If model is provided, generate rules from model
        if ($modelName) {
            $rules = $this->generateRulesFromModel($modelName, $output, $type === 'update');
            if ($rules !== null) {
                $content = $this->injectRules($content, $rules);
            }
        }

        file_put_contents($destination, $content);
        $output->writeln("<info>Request created successfully: $destination</info>");

        return Command::SUCCESS;
    }

    /**
     * Generate validation rules from model's fillable fields.
     */
    private function generateRulesFromModel(string $modelName, OutputInterface $output, bool $isUpdate = false): ?array
    {
        // Load database configuration for Eloquent
        $projectRoot = dirname(__DIR__, 2);
        $dbPath = $projectRoot.'/bootstrap/database.php';
        if (file_exists($dbPath)) {
            require $dbPath;
        }

        $modelClass = "App\\Models\\$modelName";

        if (!class_exists($modelClass)) {
            $output->writeln("<comment>Warning: Model class $modelClass not found. Skipping auto-generation.</comment>");

            return null;
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            $model = $reflection->newInstanceWithoutConstructor();

            if (!($model instanceof Model)) {
                $output->writeln("<comment>Warning: $modelClass is not an Eloquent Model. Skipping auto-generation.</comment>");

                return null;
            }

            $fillable = $model->getFillable();
            $casts = $model->getCasts();
            $rules = [];

            foreach ($fillable as $field) {
                // Skip timestamps and special fields
                if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'password_reset_token'])) {
                    continue;
                }

                $fieldRules = [];

                // Determine if field is required
                if ($isUpdate) {
                    $fieldRules[] = 'sometimes';
                } else {
                    $fieldRules[] = 'required';
                }

                // Determine type based on cast or field name
                $cast = $casts[$field] ?? null;
                if ($cast) {
                    if (str_contains($cast, 'int')) {
                        $fieldRules[] = 'integer';
                    } elseif (str_contains($cast, 'bool')) {
                        $fieldRules[] = 'boolean';
                    } elseif (str_contains($cast, 'date') || str_contains($cast, 'datetime')) {
                        $fieldRules[] = 'date';
                    } else {
                        $fieldRules[] = 'string';
                    }
                } else {
                    // Guess type from field name
                    if (str_contains($field, 'email')) {
                        $fieldRules[] = 'email';
                        if (!$isUpdate) {
                            $fieldRules[] = "unique:".strtolower($modelName)."s,$field";
                        }
                    } elseif (str_contains($field, 'password')) {
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'min:8';
                        if (!$isUpdate) {
                            $fieldRules[] = 'confirmed';
                        }
                    } elseif (str_contains($field, 'url')) {
                        $fieldRules[] = 'url';
                    } else {
                        $fieldRules[] = 'string';
                    }
                }

                // Add max length for string fields
                if (in_array('string', $fieldRules) && !in_array('email', $fieldRules) && !in_array('password', $fieldRules)) {
                    $fieldRules[] = 'max:255';
                }

                $rules[$field] = implode('|', $fieldRules);
            }

            // Add password_confirmation if password exists
            if (in_array('password', $fillable) && !$isUpdate) {
                $rules['password_confirmation'] = 'required';
            }

            return $rules;
        } catch (\Exception $e) {
            $output->writeln("<comment>Warning: Could not generate rules from model: {$e->getMessage()}</comment>");

            return null;
        }
    }

    /**
     * Inject generated rules into stub content.
     */
    private function injectRules(string $content, array $rules): string
    {
        $rulesString = "        return [\n";
        foreach ($rules as $field => $rule) {
            $rulesString .= "            '$field' => '$rule',\n";
        }
        $rulesString .= "        ];";

        // Replace the rules section
        $content = preg_replace(
            '/return\s*\[[\s\S]*?\];/',
            $rulesString,
            $content,
            1
        );

        return $content;
    }
}

