<?php

namespace App\Modules\Student\Repositories;

use App\Modules\Student\Models\Student;

/**
 * Handles data access for Student entity.
 */
class StudentRepository
{
    /**
     * Retrieve all students with optional filters.
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function all(array $filters = [])
    {
        return Student::when(isset($filters['program_id']), function ($query) use ($filters) {
            $query->where('program_id', $filters['program_id']);
        })->get();
    }

    /**
     * Create a new student record.
     *
     * @param array $data
     * @return Student
     */
    public function create(array $data): Student
    {
        return Student::create($data);
    }
}