<?php

namespace App\Modules\Evaluation\Services;

use App\Modules\Evaluation\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Student\Models\Student;
/**
 * Service class for handling evaluation-related business logic.
 */
class EvaluationService
{
    /**
     * Store a new evaluation record.
     *
     * @param array $data
     * @return Evaluation
     */
    public function store(array $data): Evaluation
    {
        return DB::transaction(function () use ($data) {
            return Evaluation::create($data);
        });
    }

    /**
     * Update an existing evaluation record.
     *
     * @param int $id
     * @param array $data
     * @return Evaluation
     */
    public function update(int $id, array $data): Evaluation
    {
        return DB::transaction(function () use ($id, $data) {
            $evaluation = Evaluation::findOrFail($id);
            $evaluation->update($data);
            return $evaluation;
        });
    }

    /**
     * Postpone an evaluation by setting its status and timestamp.
     *
     * @param int $id
     * @return Evaluation
     */
    public function postpone(int $id): Evaluation
    {
        return DB::transaction(function () use ($id) {
            $evaluation = Evaluation::findOrFail($id);
            $evaluation->nomination_status = \App\Enums\NominationStatus::POSTPONED;
            $evaluation->postponed_at = now();
            $evaluation->save();
            return $evaluation;
        });
    }

    /**
     * Lock an evaluation nomination with user and time context.
     *
     * @param int $id
     * @param int $lockedBy
     * @param \DateTimeInterface $lockedAt
     * @return Evaluation
     */
    public function lock(int $id, int $lockedBy, \DateTimeInterface $lockedAt): Evaluation
    {
        return DB::transaction(function () use ($id, $lockedBy, $lockedAt) {
            $evaluation = Evaluation::findOrFail($id);
            $evaluation->locked_by = $lockedBy;
            $evaluation->locked_at = $lockedAt;
            $evaluation->nomination_status = \App\Enums\NominationStatus::LOCKED;
            $evaluation->save();
            return $evaluation;
        });
    }

    /**
     * Retrieve students eligible for evaluation based on RS supervision.
     *
     * @param int $rsId
     * @return array
     */
    public function getEligibleStudents(int $rsId): array
    {
        return Student::where('main_supervisor_id', $rsId)
            ->where('is_postponed', false)
            ->get()
            ->toArray();
    }
}