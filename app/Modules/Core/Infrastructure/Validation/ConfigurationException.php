<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Validation;

use RuntimeException;

/**
 * Exception thrown when environment configuration validation fails.
 */
class ConfigurationException extends RuntimeException
{
    /** @var array<int, string> */
    private array $errors;

    /**
     * @param array<int, string> $errors
     */
    public function __construct(array $errors, int $code = 0, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        $message = "Configuration validation failed:\n" . implode("\n", array_map(
            fn ($error) => "  - {$error}",
            $errors
        ));

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get detailed message for CLI output.
     */
    public function getDetailedMessage(): string
    {
        $lines = [
            '╔══════════════════════════════════════════════════════════════╗',
            '║              CONFIGURATION VALIDATION FAILED                   ║',
            '╚══════════════════════════════════════════════════════════════╝',
            '',
            'The following configuration errors were found:',
            '',
        ];

        foreach ($this->errors as $error) {
            $lines[] = "  ❌ {$error}";
        }

        $lines[] = '';
        $lines[] = 'Please check your .env file and ensure all required variables are set.';

        return implode("\n", $lines);
    }

    /**
     * Get summary for HTTP responses.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'error' => 'Configuration Error',
            'message' => 'Service configuration is invalid',
            'errors' => $this->errors,
        ];
    }
}
