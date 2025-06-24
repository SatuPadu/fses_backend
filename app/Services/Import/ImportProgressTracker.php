<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Cache;

class ImportProgressTracker
{
    protected $importId;

    public function __construct($importId)
    {
        $this->importId = $importId;
    }

    public function updateStatus($status, $message, $errors = [], $summary = [])
    {
        Cache::put("import_status_{$this->importId}", [
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'summary' => $summary,
            'updated_at' => now()
        ], 3600);
    }

    public function updateProgress($current, $total, $message)
    {
        $progress = ($current / $total) * 100;
        
        Cache::put("import_progress_{$this->importId}", [
            'current' => $current,
            'total' => $total,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()
        ], 3600);
    }

    public function updateStepProgress($message)
    {
        $progress = Cache::get("import_progress_{$this->importId}");
        if ($progress) {
            $progress['message'] = $message;
            $progress['updated_at'] = now();
            Cache::put("import_progress_{$this->importId}", $progress, 3600);
        }
    }

    public function updateDetailedProgress($title, $data)
    {
        $detailedProgress = Cache::get("import_detailed_progress_{$this->importId}", []);
        $detailedProgress[] = [
            'title' => $title,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];
        
        // Keep only last 50 entries to prevent memory issues
        if (count($detailedProgress) > 50) {
            $detailedProgress = array_slice($detailedProgress, -50);
        }
        
        Cache::put("import_detailed_progress_{$this->importId}", $detailedProgress, 3600);
    }
} 