<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('path', 2048)->default('/');
            $table->string('method', 4)->default('GET');
            $table->unsignedInteger('interval_seconds')->default(300);
            $table->unsignedSmallInteger('timeout_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable()->index();
            $table->boolean('last_ok')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_checks');
    }
};
