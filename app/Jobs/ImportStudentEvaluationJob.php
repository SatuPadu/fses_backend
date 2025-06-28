<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\Import\ImportProgressTracker;
use App\Services\Import\ImportDataProcessor;
use App\Modules\Student\Services\StudentEvaluationImportService;
use Illuminate\Support\Facades\Log;

class ImportStudentEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    protected $filePath;
    protected $userId;
    protected $importId;

    // Main required fields (always required)
    protected $mainRequiredFields = [
        'student_matric_number', 'student_name', 'student_email', 'program_name',
        'current_semester', 'student_department', 'evaluation_type', 'country', 'research_title',
        'main_supervisor_staff_number', 'main_supervisor_name', 'main_supervisor_title', 'main_supervisor_department',
        'main_supervisor_email', 'main_supervisor_phone', 'main_supervisor_specialization'
    ];

    // Co-supervisor fields (all-or-none logic: all required or all empty)
    protected $coSupervisorFields = [
        'co_supervisor_staff_number', 'co_supervisor_name', 'co_supervisor_title', 'co_supervisor_department',
        'co_supervisor_is_coordinator', 'co_supervisor_email', 'co_supervisor_phone', 'co_supervisor_specialization', 'co_supervisor_external_institution'
    ];

    public function __construct($filePath, $userId, $importId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->importId = $importId;
    }

    public function handle()
    {
        try {
            // Test database connection
            DB::connection()->getPdo();

            // Check if file exists
            if (!file_exists($this->filePath)) {
                throw new \Exception("File not found: {$this->filePath}");
            }

            // Check if file is readable
            if (!is_readable($this->filePath)) {
                throw new \Exception("File not readable: {$this->filePath}");
            }
            
            $this->processImport();
        } catch (\Exception $e) {
            $this->updateImportStatus('failed', $e->getMessage());
            throw $e;
        }
    }

    protected function processImport()
    {
        $progressTracker = new ImportProgressTracker($this->importId);
        $dataProcessor = new ImportDataProcessor($progressTracker);
        $validator = new StudentEvaluationImportService();

        $progressTracker->updateStatus('processing', 'Importing...');

        $csvData = $this->readFile();
        
        $totalRows = count($csvData);
        $processedRows = 0;
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;
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
            'co_supervisors_created' => 0,
            'co_supervisors_updated' => 0
        ];

        DB::beginTransaction();

        try {
            foreach ($csvData as $rowIndex => $row) {
                $processedRows++;
                $actualRowNumber = $rowIndex + 3; // +3 because we skip header rows and array is 0-indexed
                
                $progressTracker->updateProgress($processedRows, $totalRows, "Processing row {$processedRows} of {$totalRows}");

                try {
                    // Clean and normalize the row data
                    $cleanedRow = $this->cleanRowData($row);
                    
                    // Check if this is an empty or incomplete row
                    if ($this->isEmptyRow($cleanedRow)) {
                        $skippedCount++;
                        continue;
                    }
                    
                    // Validate using the StudentEvaluationImportService
                    $validationErrors = $validator->validateRow($cleanedRow, $actualRowNumber);
                    if (!empty($validationErrors)) {
                        throw new \Exception("Validation failed: " . implode('; ', $validationErrors));
                    }
                    
                    // Process row
                    $rowResult = $dataProcessor->processRow($cleanedRow, $actualRowNumber);
                    $successCount++;
                    
                    // Update summary counts
                    foreach ($rowResult as $key => $value) {
                        if (isset($importSummary[$key]) && is_numeric($value)) {
                            $importSummary[$key] += $value;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorData = [
                        'row' => $actualRowNumber,
                        'error' => $e->getMessage(),
                        'student_name' => $cleanedRow['student_name'] ?? 'Unknown',
                        'matric_number' => $cleanedRow['student_matric_number'] ?? 'Unknown'
                    ];
                    
                    $errors[] = $errorData;
                }
            }

            DB::commit();
            
            $status = $errorCount === 0 ? 'completed' : 'completed_with_errors';
            $message = "Import completed. Success: {$successCount}, Errors: {$errorCount}, Skipped: {$skippedCount}";
            
            $progressTracker->updateStatus($status, $message, $errors, $importSummary);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clean and normalize row data
     */
    protected function cleanRowData($row)
    {
        $cleaned = [];
        
        // Boolean fields that need special handling
        $booleanFields = [
            'main_supervisor_is_coordinator',
            'co_supervisor_is_coordinator'
        ];
        
        // Numeric fields
        $numericFields = ['current_semester'];
        
        foreach ($row as $key => $value) {
            // Trim whitespace and convert null/empty strings
            $cleanValue = is_string($value) ? trim($value) : $value;
            
            // Convert empty strings to null for better database handling
            if ($cleanValue === '' || $cleanValue === 'NULL' || $cleanValue === 'null') {
                $cleanValue = null;
            }
            
            // Handle boolean fields
            if (in_array($key, $booleanFields)) {
                $cleanValue = $this->normalizeBooleanValue($cleanValue);
            }
            
            // Handle numeric fields
            if (in_array($key, $numericFields)) {
                $cleanValue = $this->normalizeNumericValue($cleanValue);
            }
            
            // Handle email fields - ensure they are properly formatted
            if (str_contains($key, '_email') && !empty($cleanValue)) {
                $cleanValue = strtolower($cleanValue);
            }
            
            $cleaned[$key] = $cleanValue;
        }
        
        return $cleaned;
    }

    /**
     * Check if a row is empty or contains only minimal data
     */
    protected function isEmptyRow($row)
    {
        // Check if all required fields are missing
        $hasRequiredData = false;
        
        // Check the most critical fields first
        $criticalFields = [
            'student_matric_number',
            'student_name',
            'main_supervisor_staff_number',
            'main_supervisor_name'
        ];
        
        foreach ($criticalFields as $field) {
            if (!empty($row[$field])) {
                $hasRequiredData = true;
                break;
            }
        }
        
        return !$hasRequiredData;
    }

    /**
     * Normalize boolean values from various formats
     */
    protected function normalizeBooleanValue($value)
    {
        if ($value === null || $value === '') {
            return 0; 
        }
        
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        
        if (is_numeric($value)) {
            return (int)$value > 0 ? 1 : 0;
        }
        
        $stringValue = strtolower(trim((string)$value));
        
        if (in_array($stringValue, ['true', 'yes', '1', 'on', 'enabled'])) {
            return 1;
        }
        
        return 0;
    }

    /**
     * Normalize numeric values
     */
    protected function normalizeNumericValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_numeric($value)) {
            return (int)$value;
        }
        
        // Try to extract number from string
        $numeric = preg_replace('/[^0-9]/', '', (string)$value);
        return $numeric !== '' ? (int)$numeric : null;
    }

    protected function readFile()
    {
        $fileExtension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        if ($fileExtension === 'csv') {
            return $this->readCsvFile();
        } else {
            return $this->readExcelFile();
        }
    }

    protected function readCsvFile()
    {
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
        
        // Clean headers - remove any BOM and trim
        $headers = array_map(function($header) {
            // Remove BOM if present
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            return trim($header);
        }, $headers);
        
        $data = [];
        $rowNumber = 2; // Starting after header rows

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;
            
            // Skip completely empty rows
            if (empty(array_filter($row, function($value) { return trim($value) !== ''; }))) {
                continue;
            }
            
            // Ensure row has the same number of elements as headers
            $row = $this->normalizeRowLength($row, $headers);
            
            try {
                $combinedRow = array_combine($headers, $row);
                if ($combinedRow !== false) {
                    $data[] = $combinedRow;
                } else {
                    Log::warning("Could not combine headers with row {$rowNumber}", [
                        'headers_count' => count($headers),
                        'row_count' => count($row)
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("Error processing CSV row {$rowNumber}: " . $e->getMessage());
            }
        }

        fclose($file);
        
        return $data;
    }

    protected function readExcelFile()
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true); // Keep null values, format data

            // Remove the first row (section headers)
            $firstKey = array_key_first($rows);
            unset($rows[$firstKey]);
            
            // Get headers from the second row
            $secondKey = array_key_first($rows);
            $headers = $rows[$secondKey];
            unset($rows[$secondKey]);
            
            if (!$headers) {
                throw new \Exception("Could not read headers from Excel file");
            }
            
            // Clean headers and remove any null values
            $headers = array_map(function($header) {
                return trim($header ?? '');
            }, $headers);
            
            // Remove empty headers from the end
            while (end($headers) === '') {
                array_pop($headers);
            }
            
            $data = [];
            $rowNumber = 2; // Starting after header rows

            foreach ($rows as $excelRowNumber => $row) {
                $rowNumber++;
                
                // Convert row to array and handle null values
                $row = array_values($row);
                
                // Skip completely empty rows
                if (empty(array_filter($row, function($value) { 
                    return $value !== null && trim((string)$value) !== ''; 
                }))) {
                    continue;
                }
                
                // Ensure row has the same number of elements as headers
                $row = $this->normalizeRowLength($row, $headers);
                
                try {
                    $combinedRow = array_combine($headers, $row);
                    if ($combinedRow !== false) {
                        $data[] = $combinedRow;
                    } else {
                        Log::warning("Could not combine headers with Excel row {$excelRowNumber}", [
                            'headers_count' => count($headers),
                            'row_count' => count($row)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Error processing Excel row {$excelRowNumber}: " . $e->getMessage());
                }
            }

            return $data;

        } catch (\Exception $e) {
            throw new \Exception("Error reading Excel file: " . $e->getMessage());
        }
    }

    /**
     * Normalize row length to match headers
     */
    protected function normalizeRowLength($row, $headers)
    {
        $headerCount = count($headers);
        $rowCount = count($row);
        
        if ($rowCount < $headerCount) {
            // Pad row with null values
            $row = array_pad($row, $headerCount, null);
        } elseif ($rowCount > $headerCount) {
            // Truncate row to match headers
            $row = array_slice($row, 0, $headerCount);
        }
        
        return $row;
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