<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Student\Requests\ExportStudentRequest;
use App\Modules\Student\Services\StudentExportService;
use App\Helpers\PermissionHelper;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @OA\Tag(
 *     name="Student Export",
 *     description="API Endpoints related to Student Data Export"
 * )
 */
class StudentExportController extends Controller
{
    use PermissionHelper;

    protected $exportService;

    public function __construct(StudentExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export student evaluation data
     * 
     * @param ExportStudentRequest $request
     * @return JsonResponse|BinaryFileResponse
     */
    public function export(ExportStudentRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // Check permissions - should check for export permission specifically
            if (!$this->userCan('students', 'export')) {
                return $this->sendError('Access denied. Insufficient permissions for export.', [], 403);
            }
            // Only PGAM and Program Coordinator can export
            $user = auth()->user();
            $userRoles = $user->roles->pluck('role_name')->toArray();
            if (!(in_array('PGAM', $userRoles) || in_array('ProgramCoordinator', $userRoles))) {
                return $this->sendError('Access denied. Only PGAM and Program Coordinator can export.', [], 403);
            }

            $result = $this->exportService->exportStudentData(
                $validated['columns'],
                $validated['format'],
                $validated['filters'] ?? []
            );

            if ($result['success']) {
                return response()->download(
                    $result['file_path'],
                    $result['filename'],
                    [
                        'Content-Type' => $result['content_type'],
                        'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"'
                    ]
                )->deleteFileAfterSend(true);
            } else {
                return $this->sendError('Export failed.', ['error' => $result['message']], 500);
            }

        } catch (\Exception $e) {
            return $this->sendError(
                'Export failed. An unexpected error occurred.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
} 