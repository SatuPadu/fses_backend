<?php

namespace App\Modules\Student\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ImportStudentEvaluationJob;
use App\Modules\Student\Requests\ImportStudentEvaluationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentEvaluationImportController extends Controller
{
    /**
     * Upload and start import process
     */
    public function upload(ImportStudentEvaluationRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $importId = Str::uuid()->toString();
            
            // Store file temporarily
            $filePath = $file->storeAs('temp_imports', $importId . '.' . $file->getClientOriginalExtension());
            
            // Initialize import status
            Cache::put("import_status_{$importId}", [
                'status' => 'queued',
                'message' => 'Import job queued successfully',
                'errors' => [],
                'updated_at' => now()
            ], 3600);

            // Initialize progress
            Cache::put("import_progress_{$importId}", [
                'current' => 0,
                'total' => 0,
                'progress' => 0,
                'message' => 'Preparing import...',
                'updated_at' => now()
            ], 3600);

            // Dispatch job
            ImportStudentEvaluationJob::dispatch(
                Storage::path($filePath),
                auth()->id(),
                $importId
            );

            return response()->json([
                'success' => true,
                'message' => 'Import job started successfully',
                'import_id' => $importId,
                'data' => [
                    'import_id' => $importId,
                    'filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString()
                ]
            ], 202);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import status with detailed progress
     */
    public function status(string $importId): JsonResponse
    {
        try {
            $status = Cache::get("import_status_{$importId}");
            $progress = Cache::get("import_progress_{$importId}");
            $detailedProgress = Cache::get("import_detailed_progress_{$importId}", []);

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'import_id' => $importId,
                'status' => $status,
                'progress' => $progress,
                'detailed_progress' => $detailedProgress
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream import progress in real-time with detailed data
     */
    public function stream(string $importId): StreamedResponse
    {
        return response()->stream(function () use ($importId) {
            $lastProgress = 0;
            $lastStatus = null;
            $lastDetailedCount = 0;
            
            while (true) {
                $status = Cache::get("import_status_{$importId}");
                $progress = Cache::get("import_progress_{$importId}");
                $detailedProgress = Cache::get("import_detailed_progress_{$importId}", []);

                if (!$status) {
                    echo "data: " . json_encode([
                        'error' => 'Import not found'
                    ]) . "\n\n";
                    break;
                }

                $data = [
                    'import_id' => $importId,
                    'status' => $status,
                    'progress' => $progress,
                    'detailed_progress' => $detailedProgress
                ];

                // Send data if there's a change
                $hasProgressChange = $progress && ($progress['current'] !== $lastProgress || $status['status'] !== $lastStatus);
                $hasDetailedChange = count($detailedProgress) !== $lastDetailedCount;
                
                if ($hasProgressChange || $hasDetailedChange) {
                    echo "data: " . json_encode($data) . "\n\n";
                    $lastProgress = $progress['current'] ?? 0;
                    $lastStatus = $status['status'];
                    $lastDetailedCount = count($detailedProgress);
                }

                // Check if import is complete
                if (in_array($status['status'], ['completed', 'completed_with_errors', 'failed'])) {
                    echo "data: " . json_encode($data) . "\n\n";
                    break;
                }

                // Flush output buffer
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Wait before next check
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no'
        ]);
    }

    /**
     * Download import errors
     */
    public function downloadErrors(string $importId): JsonResponse
    {
        try {
            $status = Cache::get("import_status_{$importId}");

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import not found'
                ], 404);
            }

            if (empty($status['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No errors to download'
                ], 400);
            }

            // Generate CSV with errors
            $filename = "import_errors_{$importId}.csv";
            $filePath = storage_path("app/temp_imports/{$filename}");
            
            $file = fopen($filePath, 'w');
            
            // Write headers
            fputcsv($file, ['Row', 'Error', 'Data']);
            
            // Write error data
            foreach ($status['errors'] as $error) {
                fputcsv($file, [
                    $error['row'],
                    $error['error'],
                    json_encode($error['data'])
                ]);
            }
            
            fclose($file);

            return response()->json([
                'success' => true,
                'download_url' => url("/api/imports/{$importId}/download-errors"),
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate error report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import template
     * 
     */
    public function template()
    {
        try {
            $templatePath = public_path('fses_student_evaluation_template.xlsx');
            
            if (!file_exists($templatePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template file not found'
                ], 404);
            }

            return response()->download(
                $templatePath,
                'fses_student_evaluation_template.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="fses_student_evaluation_template.xlsx"'
                ]
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get template',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 