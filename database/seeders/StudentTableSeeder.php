<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $students = [
            [
                'matric_number' => 'U2100123',
                'name' => 'Alice Smith',
                'email' => 'alice@example.com',
                'program_id' => 1,
                'current_semester' => 'Y1S1',
                'department' => 'SEAT',
                'main_supervisor_id' => 1,
                'evaluation_type' => 'FirstEvaluation',
                'research_title' => 'AI in Education',
                'is_postponed' => false,
                'postponement_reason' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'matric_number' => 'U2100456',
                'name' => 'Bob Lee',
                'email' => 'bob@example.com',
                'program_id' => 2,
                'current_semester' => 'Y2S2',
                'department' => 'II',
                'main_supervisor_id' => 2,
                'evaluation_type' => 'ReEvaluation',
                'research_title' => 'Decentralized Storage',
                'is_postponed' => true,
                'postponement_reason' => 'Fieldwork delays',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($students as $student) {
            $exists = DB::table('students')->where('matric_number', $student['matric_number'])->exists();
            if (!$exists) {
                DB::table('students')->insert($student);
            }
        }
    }
}
