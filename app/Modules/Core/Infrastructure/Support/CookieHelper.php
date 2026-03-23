<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

use Slim\Psr7\Cookies;
use Throwable;

/**
 * Helper class for managing cookies with encryption support.
 */
final class CookieHelper
{
    private static ?self $instance = null;

    private readonly string $secret;

    private readonly string $defaultPath;

    private readonly string $defaultDomain;

    private readonly bool $defaultSecure;

    private readonly bool $defaultHttpOnly;

    private readonly string $sameSite;

    private bool $encryptionEnabled;

    public function __construct(
        ?string $secret = null,
        ?int $defaultTtl = null,
        ?string $defaultPath = null,
        ?string $defaultDomain = null,
        ?bool $defaultSecure = null,
        ?bool $defaultHttpOnly = null,
        ?string $sameSite = null,
        ?bool $encryptionEnabled = null
    ) {
        $this->secret = $secret ?? $_ENV['APP_KEY'] ?? $_ENV['JWT_SECRET'] ?? '';
        $this->defaultPath = $defaultPath ?? $_ENV['COOKIE_PATH'] ?? '/';
        $this->defaultDomain = $defaultDomain ?? $_ENV['COOKIE_DOMAIN'] ?? '';
        $this->defaultSecure = $defaultSecure ?? ($_ENV['COOKIE_SECURE'] ?? 'true') === 'true';
        $this->defaultHttpOnly = $defaultHttpOnly ?? ($_ENV['COOKIE_HTTP_ONLY'] ?? 'true') === 'true';
        $this->sameSite = $sameSite ?? $_ENV['COOKIE_SAME_SITE'] ?? 'Lax';
        $this->encryptionEnabled = $encryptionEnabled ?? ($_ENV['COOKIE_ENCRYPT'] ?? 'true') === 'true';
    }

    /**
     * Get singleton instance.
     */
    public static function getInstance(): self
    {
        if (! self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set a cookie.
     *
     * @param  string  $name  Cookie name
     * @param  mixed  $value  Cookie value
     * @param  int|null  $ttl  Time to live in seconds (null = session cookie)
     * @param  array<string, mixed>  $options  Additional options
     */
    public function set(string $name, mixed $value, ?int $ttl = null, array $options = []): void
    {
        $serialized = serialize($value);

        if ($this->encryptionEnabled) {
            $serialized = $this->encrypt($serialized);
        }

        $encoded = base64_encode($serialized);
        $expires = $ttl === null ? 0 : time() + $ttl;

        $cookieOptions = [
            'expires' => $expires,
            'path' => $options['path'] ?? $this->defaultPath,
            'domain' => $options['domain'] ?? $this->defaultDomain,
            'secure' => $options['secure'] ?? $this->defaultSecure,
            'httponly' => $options['httponly'] ?? $this->defaultHttpOnly,
            'samesite' => $options['samesite'] ?? $this->sameSite,
        ];

        setcookie($name, $encoded, $cookieOptions);
        $_COOKIE[$name] = $encoded;
    }

    /**
     * Set a forever cookie (10 years).
     *
     * @param  string  $name  Cookie name
     * @param  mixed  $value  Cookie value
     * @param  array<string, mixed>  $options  Additional options
     */
    public function forever(string $name, mixed $value, array $options = []): void
    {
        $this->set($name, $value, 315360000, $options); // 10 years
    }

    /**
     * Get a cookie value.
     *
     * @template T
     *
     * @param  string  $name  Cookie name
     * @param  T|null  $default  Default value if cookie not found
     * @return T|null
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if (! isset($_COOKIE[$name])) {
            return $default;
        }

        $encoded = $_COOKIE[$name];
        $serialized = base64_decode((string) $encoded, true);

        if ($serialized === false) {
            return $default;
        }

        if ($this->encryptionEnabled && $this->isEncrypted($serialized)) {
            $serialized = $this->decrypt($serialized);

            if ($serialized === null) {
                return $default;
            }
        }

        try {
            $value = @unserialize($serialized);

            return $value !== false || $serialized === serialize(false) ? $value : $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * Check if a cookie exists.
     */
    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Delete a cookie.
     */
    public function delete(string $name): void
    {
        $options = [
            'expires' => time() - 3600,
            'path' => $this->defaultPath,
            'domain' => $this->defaultDomain,
            'secure' => $this->defaultSecure,
            'httponly' => $this->defaultHttpOnly,
            'samesite' => $this->sameSite,
        ];

        setcookie($name, '', $options);
        unset($_COOKIE[$name]);
    }

    /**
     * Delete multiple cookies.
     *
     * @param  array<int, string>  $names
     */
    public function deleteMultiple(array $names): void
    {
        foreach ($names as $name) {
            $this->delete($name);
        }
    }

    /**
     * Get all cookies.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $cookies = [];

        foreach (array_keys($_COOKIE) as $name) {
            $cookies[$name] = $this->get($name);
        }

        return $cookies;
    }

    /**
     * Queue a cookie for response (for use with Slim Response).
     *
     * @param  string  $name  Cookie name
     * @param  mixed  $value  Cookie value
     * @param  int|null  $ttl  Time to live in seconds
     * @param  array<string, mixed>  $options  Additional options
     * @return array<string, mixed> Cookie data for Slim Cookies::set()
     */
    public function make(string $name, mixed $value, ?int $ttl = null, array $options = []): array
    {
        $serialized = serialize($value);

        if ($this->encryptionEnabled) {
            $serialized = $this->encrypt($serialized);
        }

        $encoded = base64_encode($serialized);
        $expires = $ttl === null ? 0 : time() + $ttl;

        return [
            'value' => $encoded,
            'expires' => $expires,
            'path' => $options['path'] ?? $this->defaultPath,
            'domain' => $options['domain'] ?? $this->defaultDomain,
            'secure' => $options['secure'] ?? $this->defaultSecure,
            'httponly' => $options['httponly'] ?? $this->defaultHttpOnly,
            'samesite' => $options['samesite'] ?? $this->sameSite,
        ];
    }

    /**
     * Enable encryption.
     */
    public function enableEncryption(): void
    {
        $this->encryptionEnabled = true;
    }

    /**
     * Disable encryption.
     */
    public function disableEncryption(): void
    {
        $this->encryptionEnabled = false;
    }

    /**
     * Check if encryption is enabled.
     */
    public function isEncryptionEnabled(): bool
    {
        return $this->encryptionEnabled;
    }

    /**
     * Encrypt data.
     */
    private function encrypt(string $data): string
    {
        if ($this->secret === '') {
            return $data;
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->secret, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return $data;
        }

        return 'enc:'.base64_encode($iv.$encrypted);
    }

    /**
     * Decrypt data.
     */
    private function decrypt(string $data): ?string
    {
        if ($this->secret === '') {
            return $data;
        }

        if (! str_starts_with($data, 'enc:')) {
            return $data;
        }

        $data = substr($data, 4);
        $decoded = base64_decode($data, true);

        if ($decoded === false || strlen($decoded) < 16) {
            return null;
        }

        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->secret, OPENSSL_RAW_DATA, $iv);

        return $decrypted !== false ? $decrypted : null;
    }

    /**
     * Check if data is encrypted.
     */
    private function isEncrypted(string $data): bool
    {
        return str_starts_with($data, 'enc:');
    }
}
