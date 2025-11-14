<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Commands\MakeRequestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MakeRequestCommandTest extends TestCase
{
    private string $projectRoot;
    private string $testNamespace = 'TestRequest';
    private string $testClassName = 'CreateTestRequest';

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $testDir = $this->projectRoot."/app/Http/Requests/{$this->testNamespace}";
        if (is_dir($testDir)) {
            $files = glob("$testDir/*.php");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            if (count(glob("$testDir/*")) === 0) {
                rmdir($testDir);
            }
        }
        parent::tearDown();
    }

    public function test_command_creates_request_without_model(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = "{$this->testNamespace}/{$this->testClassName}";
        $commandTester->execute([
            'name' => $requestPath,
        ]);

        $expectedFile = $this->projectRoot."/app/Http/Requests/{$this->testNamespace}/{$this->testClassName}.php";

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('CreateTestRequest', file_get_contents($expectedFile));
        $this->assertStringContainsString('FormRequest', file_get_contents($expectedFile));
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function test_command_creates_request_with_model(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = "{$this->testNamespace}/CreateUserTestRequest";
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'User',
            '--type' => 'create',
        ]);

        $expectedFile = $this->projectRoot."/app/Http/Requests/{$this->testNamespace}/CreateUserTestRequest.php";

        $this->assertFileExists($expectedFile);
        $content = file_get_contents($expectedFile);

        // Check that rules were generated from model
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringContainsString('required', $content);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function test_command_creates_update_request_with_sometimes_rules(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = "{$this->testNamespace}/UpdateUserTestRequest";
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'User',
            '--type' => 'update',
        ]);

        $expectedFile = $this->projectRoot."/app/Http/Requests/{$this->testNamespace}/UpdateUserTestRequest.php";

        $this->assertFileExists($expectedFile);
        $content = file_get_contents($expectedFile);

        // Update requests should use 'sometimes' instead of 'required'
        $this->assertStringContainsString('sometimes', $content);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function test_command_fails_when_request_already_exists(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = "{$this->testNamespace}/DuplicateRequest";
        
        // Create file first time
        $commandTester->execute(['name' => $requestPath]);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Try to create again
        $commandTester->execute(['name' => $requestPath]);
        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
    }

    public function test_command_handles_invalid_model_gracefully(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = "{$this->testNamespace}/InvalidModelRequest";
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'NonExistentModel',
        ]);

        $expectedFile = $this->projectRoot."/app/Http/Requests/{$this->testNamespace}/InvalidModelRequest.php";

        // Should still create the file even if model doesn't exist
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('Warning', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }
}

