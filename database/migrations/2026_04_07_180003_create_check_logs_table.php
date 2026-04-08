<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_check_id')->constrained()->cascadeOnDelete();
            $table->boolean('ok');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['domain_check_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_logs');
    }
};
