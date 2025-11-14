<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Commands\MakeModuleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MakeModuleCommandTest extends TestCase
{
    private string $projectRoot;

    private string $testModulePath;

    protected function setUp(): void
    {
        parent::setUp();
        // Get project root (3 levels up from tests/Unit/Console)
        $this->projectRoot = dirname(__DIR__, 3);
        $this->testModulePath = "$this->projectRoot/app/Modules/TestModule";
    }

    protected function tearDown(): void
    {
        // Clean up test module if it exists
        if (is_dir($this->testModulePath)) {
            $this->removeDirectory($this->testModulePath);
        }

        // Clean up modules-register.php if it was created
        $registerFile = "$this->projectRoot/bootstrap/modules-register.php";
        if (file_exists($registerFile)) {
            $content = file_get_contents($registerFile);
            $content = preg_replace('/\s*App\\\\Modules\\\\TestModule.*,\n/', '', $content);
            file_put_contents($registerFile, $content);
        }

        // Clean up dependencies.php if it was modified
        $depsFile = "$this->projectRoot/bootstrap/dependencies.php";
        if (file_exists($depsFile)) {
            $content = file_get_contents($depsFile);
            // Remove TestModule use statements
            $content = preg_replace('/use\s+App\\\\Modules\\\\TestModule\\\\[^;]+;\n/', '', $content);
            // Remove TestModule entries from return array
            $content = preg_replace('/\s*CreateTestModuleActionInterface::class[^,]*,\n/', '', $content);
            $content = preg_replace('/\s*UpdateTestModuleActionInterface::class[^,]*,\n/', '', $content);
            file_put_contents($depsFile, $content);
        }

        parent::tearDown();
    }

    public function test_make_module_creates_directory_structure(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        $this->assertDirectoryExists("$this->testModulePath/Application/Actions");
        $this->assertDirectoryExists("$this->testModulePath/Application/DTOs");
        $this->assertDirectoryExists("$this->testModulePath/Infrastructure/Models");
        $this->assertDirectoryExists("$this->testModulePath/Infrastructure/Repositories");
        $this->assertDirectoryExists("$this->testModulePath/Infrastructure/Http/Controllers");
        $this->assertDirectoryExists("$this->testModulePath/Infrastructure/Routes");
        $this->assertDirectoryExists("$this->testModulePath/Policies");
    }

    public function test_make_module_creates_all_required_files(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        // Check Application layer files
        $this->assertFileExists("$this->testModulePath/Application/Actions/CreateTestModuleAction.php");
        $this->assertFileExists("$this->testModulePath/Application/Actions/UpdateTestModuleAction.php");
        $this->assertFileExists("$this->testModulePath/Application/Actions/DeleteTestModuleAction.php");
        $this->assertFileExists("$this->testModulePath/Application/Actions/GetTestModuleAction.php");
        $this->assertFileExists("$this->testModulePath/Application/Actions/ListTestModuleAction.php");
        $this->assertFileExists("$this->testModulePath/Application/DTOs/CreateTestModuleDTO.php");
        $this->assertFileExists("$this->testModulePath/Application/DTOs/UpdateTestModuleDTO.php");

        // Check Infrastructure layer files
        $this->assertFileExists("$this->testModulePath/Infrastructure/Models/TestModule.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Repositories/TestModuleRepository.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Http/Controllers/TestModuleController.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Http/Requests/CreateTestModuleRequest.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Http/Requests/UpdateTestModuleRequest.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Http/Resources/TestModuleResource.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Providers/TestModuleServiceProvider.php");
        $this->assertFileExists("$this->testModulePath/Infrastructure/Routes/api.php");
        $this->assertFileExists("$this->testModulePath/Policies/TestModulePolicy.php");
    }

    public function test_make_module_registers_service_provider(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        $registerFile = "$this->projectRoot/bootstrap/modules-register.php";
        $this->assertFileExists($registerFile);

        $content = file_get_contents($registerFile);
        $this->assertStringContainsString('App\\Modules\\TestModule\\Infrastructure\\Providers\\TestModuleServiceProvider', $content);
    }

    public function test_make_module_with_custom_model_name(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => 'Product',
            '--model' => 'Item',
        ]);

        $modulePath = "$this->projectRoot/app/Modules/Product";
        $this->assertFileExists("$modulePath/Infrastructure/Models/Item.php");
        $this->assertFileExists("$modulePath/Infrastructure/Repositories/ItemRepository.php");
        $this->assertFileExists("$modulePath/Application/Actions/CreateItemAction.php");

        // Cleanup
        if (is_dir($modulePath)) {
            $this->removeDirectory($modulePath);
        }
    }

    public function test_make_module_fails_if_module_exists(): void
    {
        // Create module first
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        // Try to create again
        $commandTester->execute(['name' => 'TestModule']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
    }

    public function test_make_module_creates_interface_files(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        // Check Interface files
        $this->assertFileExists("$this->testModulePath/Application/Interfaces/CreateTestModuleActionInterface.php");
        $this->assertFileExists("$this->testModulePath/Application/Interfaces/UpdateTestModuleActionInterface.php");

        // Check that interfaces contain correct namespace
        $createInterfaceContent = file_get_contents("$this->testModulePath/Application/Interfaces/CreateTestModuleActionInterface.php");
        $this->assertStringContainsString('namespace App\\Modules\\TestModule\\Application\\Interfaces', $createInterfaceContent);
        $this->assertStringContainsString('interface CreateTestModuleActionInterface', $createInterfaceContent);

        $updateInterfaceContent = file_get_contents("$this->testModulePath/Application/Interfaces/UpdateTestModuleActionInterface.php");
        $this->assertStringContainsString('namespace App\\Modules\\TestModule\\Application\\Interfaces', $updateInterfaceContent);
        $this->assertStringContainsString('interface UpdateTestModuleActionInterface', $updateInterfaceContent);
    }

    public function test_make_module_actions_implement_interfaces(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        // Check that CreateAction implements interface
        $createActionContent = file_get_contents("$this->testModulePath/Application/Actions/CreateTestModuleAction.php");
        $this->assertStringContainsString('implements CreateTestModuleActionInterface', $createActionContent);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Interfaces\\CreateTestModuleActionInterface', $createActionContent);

        // Check that UpdateAction implements interface
        $updateActionContent = file_get_contents("$this->testModulePath/Application/Actions/UpdateTestModuleAction.php");
        $this->assertStringContainsString('implements UpdateTestModuleActionInterface', $updateActionContent);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Interfaces\\UpdateTestModuleActionInterface', $updateActionContent);
    }

    public function test_make_module_registers_action_interfaces_in_dependencies(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        $depsFile = "$this->projectRoot/bootstrap/dependencies.php";
        $this->assertFileExists($depsFile);

        $content = file_get_contents($depsFile);

        // Check use statements
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Interfaces\\CreateTestModuleActionInterface', $content);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Interfaces\\UpdateTestModuleActionInterface', $content);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Actions\\CreateTestModuleAction', $content);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Application\\Actions\\UpdateTestModuleAction', $content);

        // Check registration in return array
        $this->assertStringContainsString('CreateTestModuleActionInterface::class', $content);
        $this->assertStringContainsString('UpdateTestModuleActionInterface::class', $content);
        $this->assertStringContainsString('\\DI\\autowire(CreateTestModuleAction::class)', $content);
        $this->assertStringContainsString('\\DI\\autowire(UpdateTestModuleAction::class)', $content);
    }

    public function test_make_module_service_provider_registers_repository(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'TestModule']);

        $serviceProviderPath = "$this->testModulePath/Infrastructure/Providers/TestModuleServiceProvider.php";
        $this->assertFileExists($serviceProviderPath);

        $content = file_get_contents($serviceProviderPath);

        // Check that Service Provider registers Repository
        $this->assertStringContainsString('TestModuleRepository::class', $content);
        $this->assertStringContainsString('\\DI\\autowire(TestModuleRepository::class)', $content);
        $this->assertStringContainsString('use App\\Modules\\TestModule\\Infrastructure\\Repositories\\TestModuleRepository', $content);
    }

    public function test_make_module_with_custom_model_registers_correct_interfaces(): void
    {
        $application = new Application();
        $application->add(new MakeModuleCommand());
        $command = $application->find('make:module');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => 'Product',
            '--model' => 'Item',
        ]);

        $modulePath = "$this->projectRoot/app/Modules/Product";
        $depsFile = "$this->projectRoot/bootstrap/dependencies.php";

        // Check that correct interfaces are registered
        $content = file_get_contents($depsFile);
        $this->assertStringContainsString('CreateItemActionInterface', $content);
        $this->assertStringContainsString('UpdateItemActionInterface', $content);
        $this->assertStringContainsString('CreateItemAction', $content);
        $this->assertStringContainsString('UpdateItemAction', $content);

        // Cleanup
        if (is_dir($modulePath)) {
            $this->removeDirectory($modulePath);
        }

        // Clean up dependencies.php
        if (file_exists($depsFile)) {
            $content = file_get_contents($depsFile);
            $content = preg_replace('/use\s+App\\\\Modules\\\\Product\\\\[^;]+;\n/', '', $content);
            $content = preg_replace('/\s*CreateItemActionInterface::class[^,]*,\n/', '', $content);
            $content = preg_replace('/\s*UpdateItemActionInterface::class[^,]*,\n/', '', $content);
            file_put_contents($depsFile, $content);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
