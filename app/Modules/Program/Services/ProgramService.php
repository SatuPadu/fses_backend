<?php

namespace App\Modules\Program\Services;

use App\Modules\Program\Models\Program;

/**
 * @service ProgramService
 * @description Provides business logic for managing academic programs.
 */
class ProgramService
{
    /**
     * Retrieve all programs.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return Program::all();
    }

    /**
     * Retrieve a program by ID.
     *
     * @param int $id
     * @return Program|null
     */
    public function getById($id)
    {
        return Program::findOrFail($id);
    }

    /**
     * Create a new program.
     *
     * @param array $data
     * @return Program
     */
    public function create(array $data)
    {
        return Program::create($data);
    }

    /**
     * Update an existing program.
     *
     * @param int $id
     * @param array $data
     * @return Program
     */
    public function update($id, array $data)
    {
        $program = Program::findOrFail($id);
        $program->update($data);
        return $program;
    }

    /**
     * Delete a program by ID.
     *
     * @param int $id
     * @return void
     */
    public function delete($id)
    {
        Program::destroy($id);
    }
}