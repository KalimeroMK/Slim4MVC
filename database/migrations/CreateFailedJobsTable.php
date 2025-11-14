<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateFailedJobsTable
{
    public function up(): void
    {
        Capsule::schema()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_class');
            $table->text('job_data');
            $table->string('exception');
            $table->text('exception_message');
            $table->text('exception_trace');
            $table->integer('failed_at');
            $table->integer('attempts')->default(1);
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('failed_jobs');
    }
}

