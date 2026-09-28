<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'department')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('department', 120)->nullable();
            });
        }

        foreach (['Teknik Mesin', 'Teknik Elektro', 'Teknik Informatika', 'Manajemen Bisnis'] as $department) {
            DB::table('students')
                ->whereNull('department')
                ->where('program', $department)
                ->update(['department' => $department]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'department')) {
            Schema::table('students', fn (Blueprint $table) => $table->dropColumn('department'));
        }
    }
};
