<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class AddSoftDeletesToRolesTable
{
    public function up(): void
    {
        Capsule::schema()->table('roles', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
