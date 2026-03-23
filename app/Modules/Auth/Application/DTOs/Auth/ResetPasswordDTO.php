<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\DTOs\Auth;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $password
    ) {}

    /**
     * Create DTO from request data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            token: (string) ($data['token'] ?? ''),
            password: (string) ($data['password'] ?? '')
        );
    }
}
