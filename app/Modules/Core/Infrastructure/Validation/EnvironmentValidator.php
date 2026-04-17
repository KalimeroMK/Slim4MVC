<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Validation;

/**
 * Environment Configuration Validator
 *
 * Validates critical environment variables at application startup.
 * Implements fail-fast pattern to catch configuration errors early.
 */
final class EnvironmentValidator
{
    private const int MIN_JWT_SECRET_LENGTH = 32;

    private const array VALID_ENVIRONMENTS = ['local', 'development', 'testing', 'staging', 'production'];

    /**
     * Validation rules configuration.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $config = [
        'critical' => [
            'JWT_SECRET' => ['required' => true, 'min_length' => self::MIN_JWT_SECRET_LENGTH],
            'DB_HOST' => ['required' => true],
            'DB_DATABASE' => ['required' => true],
            'DB_USERNAME' => ['required' => true],
            'DB_PASSWORD' => ['required' => true],
            'APP_ENV' => ['required' => true, 'allowed_values' => self::VALID_ENVIRONMENTS],
        ],
        'production_extra' => [
            'REDIS_HOST' => ['required' => true],
            'CACHE_DRIVER' => ['required' => true],
            'SESSION_DRIVER' => ['required' => true],
            'MAIL_HOST' => ['required' => true],
            'MAIL_USERNAME' => ['required' => true],
            'MAIL_PASSWORD' => ['required' => true],
        ],
        'warnings' => [
            'JWT_SECRET_LENGTH' => self::MIN_JWT_SECRET_LENGTH,
            'APP_DEBUG' => ['production' => false],
        ],
    ];

    /**
     * Validate environment configuration.
     *
     * @param  array<string, mixed>|null  $customConfig  Custom validation config
     *
     * @throws ConfigurationException If validation fails
     */
    public static function validate(?array $customConfig = null): void
    {
        $config = $customConfig ?? self::$config;
        $errors = [];
        $warnings = [];

        // Validate critical settings
        foreach ($config['critical'] ?? [] as $key => $rules) {
            $value = $_ENV[$key] ?? null;

            if (($rules['required'] ?? false) && self::isEmpty($value)) {
                $errors[] = $key.' is required but not set';

                continue;
            }

            if (! self::isEmpty($value)) {
                // Check minimum length
                if (isset($rules['min_length']) && strlen((string) $value) < $rules['min_length']) {
                    $errors[] = sprintf('%s must be at least %s characters (current: ', $key, $rules['min_length']).strlen((string) $value).')';
                }

                // Check allowed values
                if (isset($rules['allowed_values']) && ! in_array($value, $rules['allowed_values'], true)) {
                    $errors[] = $key.' must be one of: '.implode(', ', $rules['allowed_values']).sprintf(' (got: %s)', $value);
                }
            }
        }

        // Production extra checks
        $isProduction = in_array($_ENV['APP_ENV'] ?? '', ['production', 'staging'], true);
        if ($isProduction) {
            foreach ($config['production_extra'] ?? [] as $key => $rules) {
                $value = $_ENV[$key] ?? null;

                if (($rules['required'] ?? false) && self::isEmpty($value)) {
                    $errors[] = $key.' is required in production environment';
                }
            }

            // Check APP_DEBUG in production
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                $warnings[] = 'APP_DEBUG should be set to false in production';
            }
        }

        // JWT Secret warnings
        $jwtSecret = $_ENV['JWT_SECRET'] ?? '';
        if (! self::isEmpty($jwtSecret)) {
            $length = strlen((string) $jwtSecret);
            $minLength = $config['warnings']['JWT_SECRET_LENGTH'] ?? self::MIN_JWT_SECRET_LENGTH;

            if ($length < $minLength) {
                $warnings[] = sprintf('JWT_SECRET is only %d characters. Recommended: at least %s characters', $length, $minLength);
            }

            if (preg_match('/^[a-zA-Z]+$/', (string) $jwtSecret)) {
                $warnings[] = 'JWT_SECRET should contain numbers and special characters for better security';
            }

            if (in_array($jwtSecret, ['secret', 'password', '123456', 'jwt_secret', 'your-secret-key'], true)) {
                $errors[] = 'JWT_SECRET appears to be a default/weak value. Please generate a strong secret.';
            }
        }

        // Store warnings for later retrieval
        if ($warnings !== []) {
            $_ENV['_VALIDATION_WARNINGS'] = json_encode($warnings);
        }

        // Throw if critical errors found
        if ($errors !== []) {
            throw new ConfigurationException($errors);
        }
    }

    /**
     * Get validation warnings from last validation.
     *
     * @return array<int, string>
     */
    public static function getWarnings(): array
    {
        $warnings = $_ENV['_VALIDATION_WARNINGS'] ?? '[]';

        return json_decode($warnings, true) ?: [];
    }

    /**
     * Get validation summary for CLI output.
     *
     * @return array<string, mixed>
     */
    public static function getSummary(): array
    {
        $isProduction = in_array($_ENV['APP_ENV'] ?? '', ['production', 'staging'], true);
        $jwtSecret = $_ENV['JWT_SECRET'] ?? '';

        return [
            'environment' => $_ENV['APP_ENV'] ?? 'not set',
            'is_production' => $isProduction,
            'jwt_configured' => ! self::isEmpty($jwtSecret),
            'jwt_secret_length' => strlen((string) $jwtSecret),
            'db_connection' => $_ENV['DB_CONNECTION'] ?? 'not set',
            'db_configured' => ! self::isEmpty($_ENV['DB_HOST'] ?? null),
            'cache_driver' => $_ENV['CACHE_DRIVER'] ?? 'not set',
            'session_driver' => $_ENV['SESSION_DRIVER'] ?? 'not set',
            'warnings' => self::getWarnings(),
        ];
    }

    /**
     * Assert JWT secret meets minimum requirements.
     *
     * @throws ConfigurationException
     */
    public static function assertJwtSecret(string $secret): void
    {
        if (self::isEmpty($secret)) {
            throw new ConfigurationException(['JWT_SECRET cannot be empty']);
        }

        if (strlen($secret) < self::MIN_JWT_SECRET_LENGTH) {
            throw new ConfigurationException([
                sprintf('JWT_SECRET must be at least %d characters (got %d)', self::MIN_JWT_SECRET_LENGTH, strlen($secret)),
            ]);
        }
    }

    /**
     * Check if running in production.
     */
    public static function isProduction(): bool
    {
        return in_array($_ENV['APP_ENV'] ?? '', ['production', 'staging'], true);
    }

    /**
     * Validate specific key exists and is not empty.
     */
    public static function required(string $key): void
    {
        if (self::isEmpty($_ENV[$key] ?? null)) {
            throw new ConfigurationException([$key.' is required but not set']);
        }
    }

    /**
     * Check if value is considered empty.
     */
    private static function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '' || in_array(strtolower($value), ['null', 'none', 'undefined'], true);
        }

        return false;
    }
}
