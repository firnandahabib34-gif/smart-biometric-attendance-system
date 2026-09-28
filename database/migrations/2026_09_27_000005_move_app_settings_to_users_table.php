<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $newColumns = array_values(array_filter(
            ['campus_name', 'attendance_start', 'attendance_end'],
            fn (string $column) => ! Schema::hasColumn('users', $column),
        ));

        if ($newColumns !== []) {
            Schema::table('users', function (Blueprint $table) use ($newColumns) {
                foreach ($newColumns as $column) {
                    $table->string($column, $column === 'campus_name' ? 120 : 5)->nullable();
                }
            });
        }

        if (Schema::hasTable('app_settings')) {
            $settings = DB::table('app_settings')->pluck('value', 'key');
            $values = [];

            foreach (['campus_name', 'attendance_start', 'attendance_end'] as $key) {
                if ($settings->has($key)) {
                    $values[$key] = $settings->get($key);
                }
            }

            if ($values !== []) {
                DB::table('users')->update($values);
            }

            Schema::drop('app_settings');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('value');
                $table->timestamps();
            });
        }

        $admin = DB::table('users')->first(['campus_name', 'attendance_start', 'attendance_end']);
        if ($admin) {
            foreach (['campus_name', 'attendance_start', 'attendance_end'] as $key) {
                if ($admin->{$key}) {
                    DB::table('app_settings')->insert([
                        'key' => $key,
                        'value' => $admin->{$key},
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $columns = array_values(array_filter(
            ['campus_name', 'attendance_start', 'attendance_end'],
            fn (string $column) => Schema::hasColumn('users', $column),
        ));

        if ($columns !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
