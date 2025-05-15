<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class LecturerTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $lecturers = [
            [
                'id' => 1,
                'name' => 'Prof. Wong',
                'email' => 'wong@utm.com',
                'department' => 'SEAT',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Dr. Zainab',
                'email' => 'zainab@utm.com',
                'department' => 'II',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($lecturers as $lecturer) {
            $exists = DB::table('lecturers')->where('id', $lecturer['id'])->exists();
            if (!$exists) {
                DB::table('lecturers')->insert($lecturer);
            }
        }
    }
}