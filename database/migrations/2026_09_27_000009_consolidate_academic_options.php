<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->update([
            'department' => 'Teknik Informatika',
            'program' => 'Informatika',
        ]);

        $classes = [
            'A' => 'Pagi Web A',
            'B' => 'Pagi Mobile B',
            'C' => 'Pagi IoT C',
            'D' => 'Pagi AI D',
            'E' => 'Pagi Web E',
        ];

        foreach (range(1, 8) as $level) {
            foreach ($classes as $group => $className) {
                DB::table('students')
                    ->where('class_name', "Pagi {$level}-{$group}")
                    ->update(['class_name' => $className]);
            }
        }
    }

    public function down(): void
    {
        // Previous departments, programs, and class assignments cannot be inferred.
    }
};
