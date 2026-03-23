<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Commands\MakeRequestCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MakeRequestCommandTest extends TestCase
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
        $testDir = $this->projectRoot.('/app/Http/Requests/'.$this->testNamespace);
        if (is_dir($testDir)) {
            $files = glob($testDir.'/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            if (count(glob($testDir.'/*')) === 0) {
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

        $requestPath = sprintf('%s/%s', $this->testNamespace, $this->testClassName);
        $commandTester->execute([
            'name' => $requestPath,
        ]);

        $expectedFile = $this->projectRoot.sprintf('/app/Http/Requests/%s/%s.php', $this->testNamespace, $this->testClassName);

        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('CreateTestRequest', (string) file_get_contents($expectedFile));
        $this->assertStringContainsString('FormRequest', (string) file_get_contents($expectedFile));
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_command_creates_request_with_model(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = $this->testNamespace.'/CreateUserTestRequest';
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'User',
            '--type' => 'create',
        ]);

        $expectedFile = $this->projectRoot.sprintf('/app/Http/Requests/%s/CreateUserTestRequest.php', $this->testNamespace);

        $this->assertFileExists($expectedFile);
        $content = file_get_contents($expectedFile);

        // Check that rules were generated from model
        $this->assertStringContainsString('name', (string) $content);
        $this->assertStringContainsString('email', (string) $content);
        $this->assertStringContainsString('required', (string) $content);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_command_creates_update_request_with_sometimes_rules(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = $this->testNamespace.'/UpdateUserTestRequest';
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'User',
            '--type' => 'update',
        ]);

        $expectedFile = $this->projectRoot.sprintf('/app/Http/Requests/%s/UpdateUserTestRequest.php', $this->testNamespace);

        $this->assertFileExists($expectedFile);
        $content = file_get_contents($expectedFile);

        // Update requests should use 'sometimes' instead of 'required'
        $this->assertStringContainsString('sometimes', (string) $content);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function test_command_fails_when_request_already_exists(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = $this->testNamespace.'/DuplicateRequest';

        // Create file first time
        $commandTester->execute(['name' => $requestPath]);
        $this->assertSame(0, $commandTester->getStatusCode());

        // Try to create again
        $commandTester->execute(['name' => $requestPath]);
        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
    }

    public function test_command_handles_invalid_model_gracefully(): void
    {
        $application = new Application();
        $application->add(new MakeRequestCommand());

        $command = $application->find('make:request');
        $commandTester = new CommandTester($command);

        $requestPath = $this->testNamespace.'/InvalidModelRequest';
        $commandTester->execute([
            'name' => $requestPath,
            '--model' => 'NonExistentModel',
        ]);

        $expectedFile = $this->projectRoot.sprintf('/app/Http/Requests/%s/InvalidModelRequest.php', $this->testNamespace);

        // Should still create the file even if model doesn't exist
        $this->assertFileExists($expectedFile);
        $this->assertStringContainsString('Warning', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
