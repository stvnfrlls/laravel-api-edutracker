<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'user', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
