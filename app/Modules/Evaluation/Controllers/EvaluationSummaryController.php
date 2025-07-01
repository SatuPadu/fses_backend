<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Evaluation\Services\EvaluationReportService;

class EvaluationSummaryController extends Controller
{
    protected $reportService;

    public function __construct(EvaluationReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    // a. First Stage Evaluation vs Postponed (bar chart data)
    public function firstStageEvaluation(Request $request)
    {
        try {
            $data = $this->reportService->getFirstStageEvaluationSummary();
            return $this->sendResponse($data, 'First stage evaluation summary retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve first stage evaluation summary.', ['error' => $e->getMessage()], 500);
        }
    }

    // b. Unique Examiners for dropdown
    public function uniqueExaminers(Request $request)
    {
        try {
            $data = $this->reportService->getUniqueExaminers();
            return $this->sendResponse($data, 'Unique examiners retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve unique examiners.', ['error' => $e->getMessage()], 500);
        }
    }

    // b. Examiner sessions by examiner and academic year
    public function examinerSessions(Request $request)
    {
        try {
            $lecturerId = $request->query('lecturer_id');
            $academicYear = $request->query('academic_year');
            $sessions = $this->reportService->getExaminerSessions($lecturerId, $academicYear);
            return $this->sendResponse($sessions, 'Examiner sessions retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve examiner sessions.', ['error' => $e->getMessage()], 500);
        }
    }

    // c. Unique Chairpersons for dropdown
    public function uniqueChairpersons(Request $request)
    {
        try {
            $data = $this->reportService->getUniqueChairpersons();
            return $this->sendResponse($data, 'Unique chairpersons retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve unique chairpersons.', ['error' => $e->getMessage()], 500);
        }
    }

    // c. Chairperson sessions by chairperson and academic year
    public function chairpersonSessions(Request $request)
    {
        try {
            $lecturerId = $request->query('lecturer_id');
            $academicYear = $request->query('academic_year');
            $sessions = $this->reportService->getChairpersonSessions($lecturerId, $academicYear);
            return $this->sendResponse($sessions, 'Chairperson sessions retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve chairperson sessions.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get evaluation chart data for the bar chart visualization
     * Returns data formatted for frontend chart consumption
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function evaluationChartData(Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');
            $data = $this->reportService->getFormattedChartData($academicYear);
            return $this->sendResponse($data, 'Chart data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve chart data: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get raw evaluation data grouped by program and category
     * Alternative format for different chart implementations
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rawEvaluationData(Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');
            $data = $this->reportService->getCurrentSemesterChartData($academicYear);
            return $this->sendResponse($data, 'Raw evaluation data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve evaluation data: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get locked evaluation summary grouped by program, semester, and evaluation type.
     * Filterable by program_code and academic_year.
     */
    public function evaluationSummaryByProgramSemesterType(Request $request)
    {
        try {
            $programCode = $request->query('program_code');
            $academicYear = $request->query('academic_year');
            $data = $this->reportService->getEvaluationSummaryByProgramSemesterType($programCode, $academicYear);
            return $this->sendResponse($data, 'Evaluation summary by program, semester, and type retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve evaluation summary: ' . $e->getMessage(), [], 500);
        }
    }
}