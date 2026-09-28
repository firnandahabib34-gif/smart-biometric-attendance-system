<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingerprint_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint_id')->nullable();
            $table->string('event_type');
            $table->string('result')->default('success');
            $table->string('device_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_logs');
    }
};
