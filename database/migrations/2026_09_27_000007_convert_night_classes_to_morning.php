<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->where('class_name', 'Malam 3C')->update(['class_name' => 'Pagi 3-C']);
        DB::table('students')->where('class_name', 'Malam 3A')->update(['class_name' => 'Pagi 3-A']);
    }

    public function down(): void
    {
        // Class assignments are intentionally not reverted to avoid moving students back to night classes.
    }
};
