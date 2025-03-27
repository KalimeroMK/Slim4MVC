<?php

namespace Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Migrations
{
    public function up(): void
    {
        Capsule::schema()->create('migrations', function (Blueprint $table) {
            $table->string('migration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('migrations');
    }
}
