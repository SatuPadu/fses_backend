<?php

namespace App\Modules\Student\Services;

use App\Modules\Evaluation\Models\Evaluation;
use App\Modules\Evaluation\Models\Supervisor;
use App\Modules\Evaluation\Models\Examiner;
use App\Modules\Evaluation\Models\Chairperson;
use App\Modules\Evaluation\Models\CoSupervisor;
use App\Modules\Program\Models\Program;
use App\Modules\Student\Models\Student;
use App\Modules\UserManagement\Models\Lecturer;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StudentExportService
{
    /**
     * Export student data in the specified format
     *
     * @param array $columns
     * @param string $format
     * @param array $filters
     * @return array
     */
    public function exportStudentData(array $columns, string $format, array $filters = []): array
    {
        try {
            $data = $this->getFilteredData($filters);
            $transformedData = $this->transformData($data, $columns);
            
            switch ($format) {
                case 'excel':
                case 'xlsx':
                    return $this->exportToExcel($transformedData, $columns);
                case 'csv':
                    return $this->exportToCsv($transformedData, $columns);
                case 'pptx':
                    return $this->exportToPptx($transformedData, $columns);
                default:
                    return ['success' => false, 'message' => 'Unsupported format'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get filtered student data
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getFilteredData(array $filters)
    {
        $query = Student::with([
            'program',
            'evaluations.examiner1.user',
            'evaluations.examiner2.user',
            'evaluations.examiner3.user',
            'evaluations.chairperson.user',
            'mainSupervisor.user',
            'coSupervisors.lecturer.user'
        ]);

        // Apply role-based filtering
        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Check if user has PGAM role (can see all data)
        if (in_array('PGAM', $userRoles)) {
            // PGAM can see all data - no additional filtering needed
        }
        // Check if user has Office Assistant role (can see all data)
        elseif (in_array('OfficeAssistant', $userRoles)) {
            // Office Assistant can see all data - no additional filtering needed
        }
        // Check if user is a Program Coordinator (can only see their department)
        elseif (in_array('ProgramCoordinator', $userRoles)) {
            $query->where('department', $user->department);
        }
        // Check if user is a Supervisor (can only see their supervised students)
        elseif (in_array('Supervisor', $userRoles)) {
            $query->whereHas('mainSupervisor', function ($q) use ($user) {
                $q->where('staff_number', $user->staff_number);
            });
        }
        // Check if user is a Chairperson (can only see students they're chairing)
        elseif (in_array('Chairperson', $userRoles)) {
            $query->whereHas('evaluations', function ($q) use ($user) {
                $q->whereHas('chairperson', function ($cQ) use ($user) {
                    $cQ->where('staff_number', $user->staff_number);
                });
            });
        }
        // Default: no access (empty result)
        else {
            $query->whereRaw('1 = 0'); // This will return no results
        }

        // Apply additional filters
        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status_re_pd', $filters['status']);
        }

        if (!empty($filters['semester'])) {
            $query->whereHas('evaluations', function ($q) use ($filters) {
                $q->where('semester', 'like', '%' . $filters['semester'] . '%');
            });
        }

        if (!empty($filters['academic_year'])) {
            $query->whereHas('evaluations', function ($q) use ($filters) {
                $q->where('academic_year', 'like', '%' . $filters['academic_year'] . '%');
            });
        }

        if (!empty($filters['supervisor_id'])) {
            $query->where('main_supervisor_id', $filters['supervisor_id']);
        }

        if (!empty($filters['coordinator_id'])) {
            $query->whereHas('program', function ($q) use ($filters) {
                $q->where('coordinator_id', $filters['coordinator_id']);
            });
        }

        return $query->get();
    }

    /**
     * Transform data according to selected columns
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param array $columns
     * @return array
     */
    private function transformData($data, array $columns): array
    {
        $transformed = [];
        $counter = 1;

        foreach ($data as $student) {
            $row = [];
            
            foreach ($columns as $column) {
                $row[$column] = $this->getColumnValue($student, $column, $counter);
            }
            
            $transformed[] = $row;
            $counter++;
        }

        return $transformed;
    }

    /**
     * Get value for a specific column
     *
     * @param Student $student
     * @param string $column
     * @param int $counter
     * @return string
     */
    private function getColumnValue(Student $student, string $column, int $counter): string
    {
        switch ($column) {
            case 'bil':
                return (string) $counter;
            
            case 'nama':
                return $student->name ?? '';
            
            case 'no_matrik':
                return $student->matric_number ?? '';
            
            case 'status_re_pd':
                return $student->status_re_pd ?? '';
            
            case 'pd':
                return $student->pd ?? '';
            
            case 'kod_program':
                return $student->program->code ?? '';
            
            case 'nama_program':
                return $student->program->name ?? '';
            
            case 'penyelia':
                $supervisor = $student->mainSupervisor;
                if ($supervisor) {
                    return $supervisor->title . ' ' . $supervisor->name;
                }
                return '';
            
            case 'penyelia_bersama_2':
                $coSupervisors = $student->coSupervisors;
                if ($coSupervisors->isNotEmpty()) {
                    $names = [];
                    foreach ($coSupervisors as $coSupervisor) {
                        if ($coSupervisor->external_name) {
                            $names[] = $coSupervisor->external_name . ' (' . $coSupervisor->external_institution . ')';
                        } elseif ($coSupervisor->lecturer) {
                            $names[] = $coSupervisor->lecturer->title . ' ' . $coSupervisor->lecturer->name;
                        }
                    }
                    return implode("\n", $names);
                }
                return '-';
            
            case 'sem':
                $evaluation = $student->evaluations->first();
                return $evaluation ? $evaluation->semester : '';
            
            case 'tajuk_sebelum':
                return $student->research_title ?? '';
            
            case 'pemeriksa_1':
                $evaluation = $student->evaluations->first();
                if ($evaluation && $evaluation->examiner1) {
                    return $evaluation->examiner1->title . ' ' . $evaluation->examiner1->name;
                }
                return '';
            
            case 'pemeriksa_2':
                $evaluation = $student->evaluations->first();
                if ($evaluation && $evaluation->examiner2) {
                    if ($evaluation->examiner2->is_from_fai) {
                        return $evaluation->examiner2->title . ' ' . $evaluation->examiner2->name;
                    } else {
                        return $evaluation->examiner2->name . ' (' . $evaluation->examiner2->external_institution . ')';
                    }
                }
                return '';
            
            case 'pemeriksa_3':
                $evaluation = $student->evaluations->first();
                if ($evaluation && $evaluation->examiner3) {
                    return $evaluation->examiner3->title . ' ' . $evaluation->examiner3->name;
                }
                return '';
            
            case 'pengerusi':
                $evaluation = $student->evaluations->first();
                if ($evaluation && $evaluation->chairperson) {
                    return $evaluation->chairperson->title . ' ' . $evaluation->chairperson->name;
                }
                return '';
            
            case 'country':
                return $student->country ?? '';
            
            default:
                return '';
        }
    }

    /**
     * Export to Excel format
     *
     * @param array $data
     * @param array $columns
     * @return array
     */
    private function exportToExcel(array $data, array $columns): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $columnMap = $this->getColumnHeaders();
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . '1', $columnMap[$column] ?? $column);
            $col++;
        }

        // Set data
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($columns as $column) {
                $sheet->setCellValue($col . $row, $rowData[$column]);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', $col) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Generate filename
        $filename = 'student_evaluation_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filepath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return [
            'success' => true,
            'file_path' => $filepath,
            'filename' => $filename,
            'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
    }

    /**
     * Export to CSV format
     *
     * @param array $data
     * @param array $columns
     * @return array
     */
    private function exportToCsv(array $data, array $columns): array
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $columnMap = $this->getColumnHeaders();
        $col = 'A';
        foreach ($columns as $column) {
            $sheet->setCellValue($col . '1', $columnMap[$column] ?? $column);
            $col++;
        }

        // Set data
        $row = 2;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($columns as $column) {
                $sheet->setCellValue($col . $row, $rowData[$column]);
                $col++;
            }
            $row++;
        }

        // Generate filename
        $filename = 'student_evaluation_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer = new Csv($spreadsheet);
        $writer->save($filepath);

        return [
            'success' => true,
            'file_path' => $filepath,
            'filename' => $filename,
            'content_type' => 'text/csv'
        ];
    }

/**
 * Export to PowerPoint format
 *
 * @param array $data
 * @param array $columns
 * @return array
 */
private function exportToPptx(array $data, array $columns): array
{
    try {
        // Validate input
        if (empty($columns)) {
            throw new \Exception('No columns specified for export');
        }
        
        $presentation = new PhpPresentation();
        $slide = $presentation->getActiveSlide();
        
        // Add title
        $titleShape = $slide->createRichTextShape();
        $titleShape->setHeight(60)
            ->setWidth(900)
            ->setOffsetX(50)
            ->setOffsetY(20);
        
        $titleParagraph = $titleShape->createParagraph();
        $titleRun = $titleParagraph->createTextRun('Student Evaluation Report - ' . Carbon::now()->format('d/m/Y'));
        $titleRun->getFont()->setSize(18)->setBold(true);
        
        // Calculate dimensions
        $columnsCount = count($columns);
        $dataRowsCount = count($data);
        $cellWidth = 800 / $columnsCount;
        $cellHeight = 35;
        $startX = 50;
        $startY = 100;
        
        // Create headers
        $columnMap = $this->getColumnHeaders();
        for ($colIndex = 0; $colIndex < $columnsCount; $colIndex++) {
            $column = $columns[$colIndex];
            
            $headerShape = $slide->createRichTextShape();
            $headerShape->setHeight($cellHeight)
                ->setWidth($cellWidth)
                ->setOffsetX($startX + ($colIndex * $cellWidth))
                ->setOffsetY($startY);
            
            // Set background color for header
            $headerShape->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new Color('4472C4'));
            
            // Add border to header
            $headerShape->getBorder()
                ->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)
                ->setLineWidth(1)
                ->setColor(new Color('000000'));
            
            $headerParagraph = $headerShape->createParagraph();
            $headerRun = $headerParagraph->createTextRun($columnMap[$column] ?? $column);
            $headerRun->getFont()->setBold(true)->setSize(9)->setColor(new Color('FFFFFF'));
            
            // Center align text
            $headerParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $headerParagraph->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
        
        // Create data rows
        for ($dataIndex = 0; $dataIndex < $dataRowsCount; $dataIndex++) {
            $rowData = $data[$dataIndex];
            $rowY = $startY + (($dataIndex + 1) * $cellHeight);
            
            for ($colIndex = 0; $colIndex < $columnsCount; $colIndex++) {
                $column = $columns[$colIndex];
                
                $cellShape = $slide->createRichTextShape();
                $cellShape->setHeight($cellHeight)
                    ->setWidth($cellWidth)
                    ->setOffsetX($startX + ($colIndex * $cellWidth))
                    ->setOffsetY($rowY);
                
                // Set background color - alternate row colors
                if ($dataIndex % 2 == 0) {
                    $cellShape->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->setStartColor(new Color('F8F9FA'));
                } else {
                    $cellShape->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->setStartColor(new Color('FFFFFF'));
                }
                
                // Add border to data cell
                $cellShape->getBorder()
                    ->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)
                    ->setLineWidth(1)
                    ->setColor(new Color('CCCCCC'));
                
                $cellParagraph = $cellShape->createParagraph();
                $cellValue = $rowData[$column] ?? '';
                
                // Handle long text by truncating if necessary
                if (strlen($cellValue) > 40) {
                    $cellValue = substr($cellValue, 0, 37) . '...';
                }
                
                $cellRun = $cellParagraph->createTextRun($cellValue);
                $cellRun->getFont()->setSize(8);
                
                // Center align text
                $cellParagraph->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $cellParagraph->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        // Add a border around the entire table area
        $tableBorderShape = $slide->createRichTextShape();
        $tableBorderShape->setHeight(($dataRowsCount + 1) * $cellHeight)
            ->setWidth(800)
            ->setOffsetX($startX)
            ->setOffsetY($startY);
        
        // Make it transparent but with a thick border
        $tableBorderShape->getFill()->setFillType(Fill::FILL_NONE);
        $tableBorderShape->getBorder()
            ->setLineStyle(\PhpOffice\PhpPresentation\Style\Border::LINE_SINGLE)
            ->setLineWidth(2)
            ->setColor(new Color('000000'));

        // Save file
        $filename = 'student_evaluation_export_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pptx';
        $filepath = storage_path('app/temp/' . $filename);

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $writer = \PhpOffice\PhpPresentation\IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($filepath);

        return [
            'success' => true,
            'file_path' => $filepath,
            'filename' => $filename,
            'content_type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'PowerPoint export failed: ' . $e->getMessage() . ' (Data rows: ' . count($data) . ', Columns: ' . count($columns) . ')'
        ];
    }
}

    /**
     * Get column headers mapping
     *
     * @return array
     */
    private function getColumnHeaders(): array
    {
        return [
            'bil' => 'BIL.',
            'nama' => 'NAMA',
            'no_matrik' => 'NO. MATRIK',
            'status_re_pd' => 'STATUS RE-PD',
            'pd' => 'PD?',
            'kod_program' => 'KOD PROGRAM',
            'nama_program' => 'NAMA PROGRAM',
            'penyelia' => 'PENYELIA',
            'penyelia_bersama_2' => 'PENYELIA BERSAMA 2',
            'sem' => 'SEM',
            'tajuk_sebelum' => 'TAJUK SEBELUM',
            'pemeriksa_1' => 'PEMERIKSA 1',
            'pemeriksa_2' => 'PEMERIKSA 2',
            'pemeriksa_3' => 'PEMERIKSA 3',
            'pengerusi' => 'PENGERUSI',
            'country' => 'COUNTRY'
        ];
    }

    /**
     * Get available export columns
     *
     * @return array
     */
    public function getAvailableColumns(): array
    {
        return [
            'bil' => 'BIL.',
            'nama' => 'NAMA',
            'no_matrik' => 'NO. MATRIK',
            'status_re_pd' => 'STATUS RE-PD',
            'pd' => 'PD?',
            'kod_program' => 'KOD PROGRAM',
            'nama_program' => 'NAMA PROGRAM',
            'penyelia' => 'PENYELIA',
            'penyelia_bersama_2' => 'PENYELIA BERSAMA 2',
            'sem' => 'SEM',
            'tajuk_sebelum' => 'TAJUK SEBELUM',
            'pemeriksa_1' => 'PEMERIKSA 1',
            'pemeriksa_2' => 'PEMERIKSA 2',
            'pemeriksa_3' => 'PEMERIKSA 3',
            'pengerusi' => 'PENGERUSI',
            'country' => 'COUNTRY'
        ];
    }

    /**
     * Get available export formats
     *
     * @return array
     */
    public function getExportFormats(): array
    {
        return [
            'excel' => 'Excel (.xlsx)',
            'xlsx' => 'Excel (.xlsx)',
            'csv' => 'CSV (.csv)',
            'pptx' => 'PowerPoint (.pptx)'
        ];
    }
} 