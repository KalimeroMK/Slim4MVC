<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Validation;

use RuntimeException;

/**
 * Exception thrown when environment configuration validation fails.
 */
class ConfigurationException extends RuntimeException
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(private readonly array $errors, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Configuration validation failed:\n" . implode("\n", array_map(
            fn (string $error): string => '  - ' . $error,
            $this->errors
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
            $lines[] = '  ❌ ' . $error;
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
            'status_code' => 500,
            'timestamp' => date('c'),
            'errors' => array_map(
                fn (string $error): array => [
                    'field' => $this->extractFieldName($error),
                    'message' => $error,
                ],
                $this->errors
            ),
            'total_errors' => count($this->errors),
        ];
    }

    /**
     * Extract field name from error message.
     */
    private function extractFieldName(string $error): string
    {
        // Match patterns like "JWT_SECRET is required..." or "DB_HOST is required..."
        if (preg_match('/^([A-Z_]+)/', $error, $matches)) {
            return $matches[1];
        }

        return 'general';
    }
}
