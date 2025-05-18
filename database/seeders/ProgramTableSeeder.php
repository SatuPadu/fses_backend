<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProgramTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('programs')->insert([
            [
                'program_name' => 'Doctor of Philosophy',
                'program_code' => 'PhD',
                'department' => 'SEAT',
                'total_semesters' => 6,
                'evaluation_semester' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'program_name' => 'Master of Philosophy',
                'program_code' => 'MPhil',
                'department' => 'II',
                'total_semesters' => 4,
                'evaluation_semester' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
