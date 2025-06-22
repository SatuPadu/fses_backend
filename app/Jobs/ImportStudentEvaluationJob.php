<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use App\Services\Import\ImportProgressTracker;
use App\Services\Import\ImportDataProcessor;
use App\Services\Import\ImportValidator;

class ImportStudentEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $filePath;
    protected $userId;
    protected $importId;

    public function __construct($filePath, $userId, $importId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->importId = $importId;
    }

    public function handle()
    {
        Log::info("ImportStudentEvaluationJob started", [
            'filePath' => $this->filePath,
            'userId' => $this->userId,
            'importId' => $this->importId
        ]);

        try {
            // Test database connection
            Log::info("Testing database connection");
            DB::connection()->getPdo();
            Log::info("Database connection successful");

            // Test model access
            Log::info("Testing model access");
            $programCount = \App\Modules\Program\Models\Program::count();
            $lecturerCount = \App\Modules\UserManagement\Models\Lecturer::count();
            $studentCount = \App\Modules\Student\Models\Student::count();
            $evaluationCount = \App\Modules\Evaluation\Models\Evaluation::count();
            
            Log::info("Model access successful", [
                'programs' => $programCount,
                'lecturers' => $lecturerCount,
                'students' => $studentCount,
                'evaluations' => $evaluationCount
            ]);

            // Check if file exists
            if (!file_exists($this->filePath)) {
                throw new \Exception("File not found: {$this->filePath}");
            }

            // Check if file is readable
            if (!is_readable($this->filePath)) {
                throw new \Exception("File not readable: {$this->filePath}");
            }

            Log::info("File validation passed", ['filePath' => $this->filePath]);
            
            $this->processImport();
        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->updateImportStatus('failed', $e->getMessage());
            throw $e;
        }
    }

    protected function processImport()
    {
        Log::info("Starting processImport method");
        
        $progressTracker = new ImportProgressTracker($this->importId);
        $dataProcessor = new ImportDataProcessor($progressTracker);
        $validator = new ImportValidator();

        $progressTracker->updateStatus('processing', 'Starting import process...');

        Log::info("Reading file");
        $csvData = $this->readFile();
        Log::info("File read successfully", ['rowCount' => count($csvData)]);
        
        $totalRows = count($csvData);
        $processedRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $importSummary = [
            'programs_created' => 0,
            'programs_updated' => 0,
            'lecturers_created' => 0,
            'lecturers_updated' => 0,
            'users_created' => 0,
            'users_updated' => 0,
            'students_created' => 0,
            'students_updated' => 0,
            'evaluations_created' => 0,
            'evaluations_updated' => 0,
            'co_supervisors_created' => 0,
            'co_supervisors_updated' => 0
        ];

        DB::beginTransaction();

        try {
            foreach ($csvData as $rowIndex => $row) {
                $processedRows++;
                $progressTracker->updateProgress($processedRows, $totalRows, "Processing row {$processedRows} of {$totalRows}");

                try {
                    Log::info("Processing row {$processedRows}", [
                        'rowData' => $row,
                        'rowIndex' => $rowIndex
                    ]);
                    
                    // Validate row
                    $validator->validateRow($row, $rowIndex + 2);
                    Log::info("Row {$processedRows} validation passed");
                    
                    // Process row
                    $rowResult = $dataProcessor->processRow($row, $rowIndex + 2);
                    $successCount++;
                    Log::info("Row {$processedRows} processing completed", ['result' => $rowResult]);
                    
                    // Update summary counts
                    foreach ($rowResult as $key => $value) {
                        if (isset($importSummary[$key])) {
                            $importSummary[$key] += $value;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                        'data' => $row
                    ];
                    Log::warning("Row " . ($rowIndex + 2) . " failed: " . $e->getMessage(), [
                        'rowData' => $row,
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }

            DB::commit();
            
            $status = $errorCount === 0 ? 'completed' : 'completed_with_errors';
            $message = "Import completed. Success: {$successCount}, Errors: {$errorCount}";
            $progressTracker->updateStatus($status, $message, $errors, $importSummary);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function readFile()
    {
        $fileExtension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));
        Log::info("Reading file", ['filePath' => $this->filePath, 'extension' => $fileExtension]);

        if ($fileExtension === 'csv') {
            return $this->readCsvFile();
        } else {
            return $this->readExcelFile();
        }
    }

    protected function readCsvFile()
    {
        Log::info("Reading CSV file");
        
        $file = fopen($this->filePath, 'r');
        if (!$file) {
            throw new \Exception("Could not open file: {$this->filePath}");
        }

        // Skip the first row (section headers)
        fgetcsv($file);
        
        // Read the actual headers from the second row
        $headers = fgetcsv($file);
        if (!$headers) {
            fclose($file);
            throw new \Exception("Could not read headers from CSV file");
        }

        Log::info("CSV headers read", ['headers' => $headers]);
        
        $data = [];

        while (($row = fgetcsv($file)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Ensure headers and row have the same number of elements
            if (count($headers) !== count($row)) {
                Log::warning("Row has different number of columns than headers", [
                    'headersCount' => count($headers),
                    'rowCount' => count($row),
                    'row' => $row
                ]);
                // Pad or truncate row to match headers
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } else {
                    $row = array_slice($row, 0, count($headers));
                }
            }
            
            $data[] = array_combine($headers, $row);
        }

        fclose($file);
        
        Log::info("CSV data read successfully", ['rowCount' => count($data)]);
        return $data;
    }

    protected function readExcelFile()
    {
        Log::info("Reading Excel file");
        
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip the first row (section headers)
            array_shift($rows);
            
            // Get headers from the second row
            $headers = array_shift($rows);
            if (!$headers) {
                throw new \Exception("Could not read headers from Excel file");
            }

            Log::info("Excel headers read", ['headers' => $headers]);
            
            $data = [];

            foreach ($rows as $row) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Ensure headers and row have the same number of elements
                if (count($headers) !== count($row)) {
                    Log::warning("Row has different number of columns than headers", [
                        'headersCount' => count($headers),
                        'rowCount' => count($row),
                        'row' => $row
                    ]);
                    // Pad or truncate row to match headers
                    if (count($row) < count($headers)) {
                        $row = array_pad($row, count($headers), '');
                    } else {
                        $row = array_slice($row, 0, count($headers));
                    }
                }
                
                $data[] = array_combine($headers, $row);
            }

            Log::info("Excel data read successfully", ['rowCount' => count($data)]);
            return $data;

        } catch (\Exception $e) {
            throw new \Exception("Error reading Excel file: " . $e->getMessage());
        }
    }

    protected function updateImportStatus($status, $message)
    {
        cache()->put("import_status_{$this->importId}", [
            'status' => $status,
            'message' => $message,
            'errors' => [],
            'updated_at' => now()
        ], 3600);
    }
} 