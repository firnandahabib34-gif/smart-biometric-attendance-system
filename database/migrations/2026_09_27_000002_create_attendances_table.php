<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendances')) {
            return;
        }

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default('present');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();
            $table->unique(['student_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
