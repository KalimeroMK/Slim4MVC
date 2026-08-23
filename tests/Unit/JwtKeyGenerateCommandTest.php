<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\JwtKeyGenerateCommand;
use PHPUnit\Framework\TestCase;

/**
 * .env.example ships `JWT_SECRET=` with no value, so that is the shape the command
 * meets on every fresh clone. Whatever it does, the file has to come out with exactly
 * one JWT_SECRET line carrying the key - a second, empty one wins with the loader and
 * makes EnvironmentValidator refuse to boot.
 */
final class JwtKeyGenerateCommandTest extends TestCase
{
    private const KEY = 'REPLACEMENT-KEY';

    public function test_it_replaces_the_empty_placeholder_from_env_example(): void
    {
        $env = "APP_ENV=local\n\n# JWT Configuration\n# Must be at least 32 characters.\nJWT_SECRET=\nJWT_TTL=3600\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        $this->assertSame(['JWT_SECRET='.self::KEY], $this->secretLines($out));
    }

    public function test_it_leaves_the_surrounding_comments_alone(): void
    {
        $env = "# JWT Configuration\n# Must be at least 32 characters.\nJWT_SECRET=\nJWT_TTL=3600\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        $this->assertStringContainsString('# Must be at least 32 characters.', $out);
        $this->assertStringContainsString('JWT_TTL=3600', $out);
    }

    public function test_it_replaces_an_existing_value(): void
    {
        $env = "APP_ENV=local\nJWT_SECRET=old-value\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        $this->assertSame(['JWT_SECRET='.self::KEY], $this->secretLines($out));
    }

    public function test_it_collapses_a_file_that_already_has_duplicates(): void
    {
        $env = "JWT_SECRET=first\nOTHER=1\nJWT_SECRET=\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        $this->assertSame(['JWT_SECRET='.self::KEY], $this->secretLines($out));
        $this->assertStringContainsString('OTHER=1', $out);
    }

    public function test_it_adds_the_key_when_the_file_has_none(): void
    {
        $env = "APP_ENV=local\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        $this->assertSame(['JWT_SECRET='.self::KEY], $this->secretLines($out));
        $this->assertStringContainsString('APP_ENV=local', $out);
    }

    public function test_it_does_not_disturb_other_variables(): void
    {
        $env = "APP_ENV=local\nDB_HOST=db\nJWT_SECRET=\nMAIL_HOST=smtp\n";

        $out = JwtKeyGenerateCommand::withSecret($env, self::KEY);

        foreach (['APP_ENV=local', 'DB_HOST=db', 'MAIL_HOST=smtp'] as $line) {
            $this->assertStringContainsString($line, $out);
        }
    }

    /** @return list<string> */
    private function secretLines(string $content): array
    {
        preg_match_all('/^JWT_SECRET=.*$/m', $content, $m);

        return $m[0];
    }
}
