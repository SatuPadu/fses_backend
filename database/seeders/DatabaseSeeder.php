<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Modules\Auth\Models\User::factory(10)->create();

        \App\Modules\Auth\Models\User::factory()->create([
            'name' => 'Bashar',
            'email' => 'bashar@utm.com',
            'password' => bcrypt('password123'),
        ]);
    }
}