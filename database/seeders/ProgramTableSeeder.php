<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\ProgramName;

class ProgramTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('programs')->insert([
            [
                'program_name' => ProgramName::PHD,
                'program_code' => ProgramName::PHD,
                'department' => 'SEAT',
                'total_semesters' => 16,
                'evaluation_semester' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'program_name' => ProgramName::MPHIL,
                'program_code' => ProgramName::MPHIL,
                'department' => 'II',
                'total_semesters' => 8,
                'evaluation_semester' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'program_name' => ProgramName::DSE,
                'program_code' => ProgramName::DSE,
                'department' => 'CAI',
                'total_semesters' => 16,
                'evaluation_semester' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
