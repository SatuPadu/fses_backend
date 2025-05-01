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
        // Run seeders in the correct order to maintain dependencies
        // 1. First create roles
        $this->call(RoleTableSeeder::class);
        
        // 2. Then create users
        $this->call(UserTableSeeder::class);
        
        // 3. Finally, establish role relationships
        $this->call(UserRoleTableSeeder::class);
        
        // Add any other seeders below this line
        // $this->call(OtherSeeder::class);
    }
}