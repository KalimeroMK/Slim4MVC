<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddPasswordResetExpiresAtToUsersTable
{
    public function up(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->timestamp('password_reset_token_expires_at')->nullable()->after('password_reset_token');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('users', function (Blueprint $table) {
            $table->dropColumn('password_reset_token_expires_at');
        });
    }
}
