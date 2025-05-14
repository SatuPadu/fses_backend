<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\Student;
use App\Modules\Student\Imports\StudentsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Modules\Student\Repositories\StudentRepository;

class StudentService
{
    private StudentRepository $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    /**
     * Retrieve all students, optionally filtered by criteria.
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getAllStudents(array $filters = [])
    {
        return $this->studentRepository->all($filters);
    }

    /**
     * Create a new student record.
     *
     * @param array $data
     * @return Student
     */
    public function createStudent(array $data): Student
    {
        return $this->studentRepository->create($data);
    }

    /**
     * Import students in bulk from an Excel file.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function importFromExcel(UploadedFile $file): void
    {
        Excel::import(new StudentsImport, $file);
    }
}