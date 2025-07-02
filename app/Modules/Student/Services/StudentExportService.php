<?php

namespace App\Modules\Student\Services;
use App\Modules\Student\Models\Student;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
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
            $transformedData = $this->transformData($data, $columns, $filters);
            
            switch ($format) {
                case 'excel':
                case 'xlsx':
                    return $this->exportToExcel($transformedData, $columns, $filters);
                case 'csv':
                    return $this->exportToCsv($transformedData, $columns, $filters);
                case 'pptx':
                    return $this->exportToPptx($transformedData, $columns, $filters);
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
            'coSupervisors.lecturer'
        ]);

        $user = auth()->user();
        $userRoles = $user->roles->pluck('role_name')->toArray();

        // Only PGAM and Program Coordinator can export
        if (!(in_array('PGAM', $userRoles) || in_array('ProgramCoordinator', $userRoles))) {
            // No access for other roles
            $query->whereRaw('1 = 0');
        } else if (in_array('ProgramCoordinator', $userRoles) && !in_array('PGAM', $userRoles)) {
            // Program Coordinator: only their department
            $query->where('department', $user->department);
        }
        // PGAM: no restriction

        // All filters are optional
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (!empty($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }
        if (!empty($filters['academic_year'])) {
            $query->whereHas('evaluations', function ($q) use ($filters) {
                $q->where('academic_year', 'like', '%' . $filters['academic_year'] . '%');
            });
        }
        
        // Filter by postponement status
        if (isset($filters['is_postponed'])) {
            if (filter_var($filters['is_postponed'], FILTER_VALIDATE_BOOLEAN)) {
                // Only get students with postponed evaluations (nomination_status = 'Postponed')
                $query->whereHas('evaluations', function ($q) {
                    $q->where('nomination_status', 'Postponed');
                });
            } else {
                // Only get students with non-postponed evaluations (nomination_status = 'Locked')
                $query->whereHas('evaluations', function ($q) {
                    $q->where('nomination_status', 'Locked');
                });
            }
        }
        
        return $query->get();
    }

    /**
     * Transform data according to selected columns
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param array $columns
     * @param array $filters
     * @return array
     */
    private function transformData($data, array $columns, array $filters = []): array
    {
        $transformed = [];
        $counter = 1;
        foreach ($data as $student) {
            // Determine which evaluations to include based on filters
            $evaluationsToInclude = $student->evaluations;
            
            // Check if is_postponed filter is set
            if (isset($filters['is_postponed'])) {
                if (filter_var($filters['is_postponed'], FILTER_VALIDATE_BOOLEAN)) {
                    // Only include evaluations with nomination_status 'Postponed'
                    $evaluationsToInclude = $student->evaluations->where('nomination_status', 'Postponed');
                } else {
                    // Only include evaluations with nomination_status 'Locked' (non-postponed)
                    $evaluationsToInclude = $student->evaluations->where('nomination_status', 'Locked');
                }
            } else {
                // Default behavior: only include evaluations with nomination_status 'Locked'
                $evaluationsToInclude = $student->evaluations->where('nomination_status', 'Locked');
            }
            
            foreach ($evaluationsToInclude as $evaluation) {
                $row = [];
                foreach ($columns as $column) {
                    $row[$column] = $this->getColumnValue($student, $column, $counter, $evaluation);
                }
                $transformed[] = $row;
                $counter++;
            }
        }
        return $transformed;
    }

    /**
     * Get value for a specific column
     *
     * @param Student $student
     * @param string $column
     * @param int $counter
     * @param Evaluation|null $evaluation
     * @return string
     */
    private function getColumnValue(Student $student, string $column, int $counter, $evaluation = null): string
    {
        switch ($column) {
            case 'no':
                return (string) $counter;
            case 'student_name':
                return (string) ($student->name ?? '');
            case 'program':
                return (string) ($student->program->program_name ?? '');
            case 'evaluation_type':
                return (string) ($student->evaluation_type ?? '');
            case 'research_title':
                return (string) ($student->research_title ?? '');
            case 'current_semester':
                $current = $evaluation && $evaluation->semester ? $evaluation->semester : '';
                $total = $student->program && $student->program->total_semesters ? $student->program->total_semesters : '';
                if ($current !== '' && $total !== '') {
                    return (string) ("$current/$total");
                } elseif ($current !== '') {
                    return (string) $current;
                } elseif ($total !== '') {
                    return (string) ("0/$total");
                }
                return '';
            case 'main_supervisor':
                $supervisor = $student->mainSupervisor;
                if ($supervisor) {
                    return (string) ($supervisor->title . ' ' . $supervisor->name);
                }
                return '';
            case 'co_supervisor':
                $coSupervisors = $student->coSupervisors;
                if ($coSupervisors && $coSupervisors->isNotEmpty()) {
                    $names = [];
                    foreach ($coSupervisors as $coSupervisor) {
                        if ($coSupervisor->external_name) {
                            $names[] = $coSupervisor->external_name . ' (' . $coSupervisor->external_institution . ')';
                        } elseif ($coSupervisor->lecturer) {
                            $names[] = $coSupervisor->lecturer->title . ' ' . $coSupervisor->lecturer->name;
                        }
                    }
                    return (string) implode("\n", $names);
                }
                return '';
            case 'examiner_1':
                if ($evaluation && $evaluation->examiner1) {
                    return (string) ($evaluation->examiner1->title . ' ' . $evaluation->examiner1->name);
                }
                return '';
            case 'examiner_2':
                if ($evaluation && $evaluation->examiner2) {
                    if ($evaluation->examiner2->is_from_fai) {
                        return (string) ($evaluation->examiner2->title . ' ' . $evaluation->examiner2->name);
                    } else {
                        return (string) ($evaluation->examiner2->name . ' (' . $evaluation->examiner2->external_institution . ')');
                    }
                }
                return '';
            case 'examiner_3':
                if ($evaluation && $evaluation->examiner3) {
                    return (string) ($evaluation->examiner3->title . ' ' . $evaluation->examiner3->name);
                }
                return '';
            case 'chairperson':
                if ($evaluation && $evaluation->chairperson) {
                    return (string) ($evaluation->chairperson->title . ' ' . $evaluation->chairperson->name);
                }
                return '';
            default:
                return '';
        }
    }

    /**
     * Export to Excel format
     *
     * @param array $data
     * @param array $columns
     * @param array $filters
     * @return array
     */
    private function exportToExcel(array $data, array $columns, array $filters = []): array
    {
        try {
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

            // Generate filename with department and program code
            $department = !empty($filters['department']) ? preg_replace('/\s+/', '_', $filters['department']) : 'all';
            $programCode = 'all';
            if (!empty($filters['program_id'])) {
                // Try to get program code from first row if available
                if (!empty($data[0]['program'])) {
                    $programCode = preg_replace('/\s+/', '_', $data[0]['program']);
                } else {
                    $programCode = $filters['program_id'];
                }
            }
            $filename = 'student_evaluation_export_' . $department . '_' . $programCode . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.xlsx';
            $filepath = public_path('exports/' . $filename);

            // Ensure exports directory exists in public folder
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            return [
                'success' => true,
                'file_path' => $filepath,
                'filename' => $filename,
                'download_url' => url('exports/' . $filename),
                'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create Excel file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Export to CSV format
     *
     * @param array $data
     * @param array $columns
     * @param array $filters
     * @return array
     */
    private function exportToCsv(array $data, array $columns, array $filters = []): array
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

        // Generate filename with department and program code
        $department = !empty($filters['department']) ? preg_replace('/\s+/', '_', $filters['department']) : 'all';
        $programCode = 'all';
        if (!empty($filters['program_id'])) {
            if (!empty($data[0]['program'])) {
                $programCode = preg_replace('/\s+/', '_', $data[0]['program']);
            } else {
                $programCode = $filters['program_id'];
            }
        }
        $filename = 'student_evaluation_export_' . $department . '_' . $programCode . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
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
     * @param array $filters
     * @return array
     */
    private function exportToPptx(array $data, array $columns, array $filters = []): array
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

            // Generate filename with department and program code
            $department = !empty($filters['department']) ? preg_replace('/\s+/', '_', $filters['department']) : 'all';
            $programCode = 'all';
            if (!empty($filters['program_id'])) {
                if (!empty($data[0]['program'])) {
                    $programCode = preg_replace('/\s+/', '_', $data[0]['program']);
                } else {
                    $programCode = $filters['program_id'];
                }
            }
            $filename = 'student_evaluation_export_' . $department . '_' . $programCode . '_' . Carbon::now()->format('Y-m-d_H-i-s') . '.pptx';
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
            'no' => 'No.',
            'student_name' => 'Student Name',
            'program' => 'Program',
            'evaluation_type' => 'Evaluation Type',
            'research_title' => 'Research Title',
            'current_semester' => 'Current Semester',
            'main_supervisor' => 'Main Supervisor',
            'co_supervisor' => 'Co-Supervisor',
            'examiner_1' => 'Examiner 1',
            'examiner_2' => 'Examiner 2',
            'examiner_3' => 'Examiner 3',
            'chairperson' => 'Chairperson',
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
            'no' => 'No.',
            'student_name' => 'Student Name',
            'program' => 'Program',
            'evaluation_type' => 'Evaluation Type',
            'research_title' => 'Research Title',
            'current_semester' => 'Current Semester',
            'main_supervisor' => 'Main Supervisor',
            'co_supervisor' => 'Co-Supervisor',
            'examiner_1' => 'Examiner 1',
            'examiner_2' => 'Examiner 2',
            'examiner_3' => 'Examiner 3',
            'chairperson' => 'Chairperson',
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