<?php

use App\Modules\Evaluation\Controllers\AssignmentController;
use App\Modules\Evaluation\Controllers\NominationController;
use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\Auth\Controllers\EnumController;
use App\Modules\UserManagement\Controllers\UserController;
use App\Modules\UserManagement\Controllers\LecturerController;
use App\Modules\UserManagement\Controllers\RoleController;
use App\Modules\Student\Controllers\StudentController;
use App\Modules\Student\Controllers\StudentEvaluationImportController;
use App\Modules\Evaluation\Controllers\ExaminerSuggestionController;
use App\Modules\Program\Controllers\ProgramController;
use App\Modules\Student\Controllers\StudentExportController;
use App\Modules\Evaluation\Controllers\EvaluationSummaryController;
use App\Modules\UserManagement\Controllers\LogController;
use App\Modules\Dashboard\Controllers\DashboardController;

// API Health Check
Route::get('/status', function () {
    return response()->json([
        'status' => 'up',
    ]);
});

// Enums Routes (Public - no authentication required)
Route::get('/enums', [EnumController::class, 'index']);
Route::get('/academic-years', [NominationController::class, 'getAcademicYears']);

// Authentication Routes
Route::prefix('auth')->group(function () {    // Public routes
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes (require JWT authentication)
    Route::middleware('jwt.verify')->group(function () {
        // Routes that are accessible even if password not updated
        Route::post('set-new-password', [PasswordResetController::class, 'setNewPassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('reactivate/{id}', [AuthController::class, 'reactivateAccount']);
        
        // Routes that require password to be updated
        Route::middleware('password.updated')->group(function () {
            Route::get('auth-user', [AuthController::class, 'user']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            // Add other protected routes here
        });
    });
});

// Password Reset Routes
Route::prefix('password')->group(function () {
    Route::post('reset-link', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1');

    Route::post('reset', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1');
});
// User Management Routes - Lecturers (Office Assistant, Program Coordinator, PGAM)
Route::prefix('lecturers')->middleware(['jwt.verify', 'password.updated', 'permission:lecturers,view'])->group(function () {
    Route::get('/', [LecturerController::class, 'index']);
    Route::post('/', [LecturerController::class, 'store'])->middleware('permission:lecturers,create');
    
    // Supervisor Suggestions Routes - must come before {id} route
    Route::get('/supervisor-suggestions', [LecturerController::class, 'getSupervisorSuggestions']);
    Route::get('/co-supervisor-suggestions', [LecturerController::class, 'getCoSupervisorSuggestions']);
    
    Route::get('/{id}', [LecturerController::class, 'lecturerDetail']);
    Route::put('/{id}', [LecturerController::class, 'update'])->middleware('permission:lecturers,edit');
    Route::delete('/{id}', [LecturerController::class, 'destroy'])->middleware('permission:lecturers,delete');
});

// User Management Routes - Users (Office Assistant, Program Coordinator, PGAM)
Route::prefix('users')->middleware(['jwt.verify', 'password.updated', 'permission:users,view'])->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store'])->middleware('permission:users,create');
    Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:users,edit');
    Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:users,delete');
});

// Role Management Routes (Program Coordinator, PGAM)
Route::prefix('roles')->middleware(['jwt.verify', 'password.updated', 'permission:users,view'])->group(function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::get('/{id}', [RoleController::class, 'show']);
    Route::get('/my/permissions', [RoleController::class, 'getUserPermissions']);
    Route::post('/check-permission', [RoleController::class, 'checkPermission']);
    Route::post('/assign-pgam', [RoleController::class, 'assignPGAMRole'])->middleware('permission:users,edit');
    Route::get('/pgam/users', [RoleController::class, 'getPGAMUsers']);
});

// Student Management Routes (Protected by JWT middleware)
Route::prefix('students')->middleware(['jwt.verify', 'password.updated', 'permission:students,view'])->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/', [StudentController::class, 'store'])->middleware('permission:students,create');
    Route::post('/import', [StudentController::class, 'importExcel'])->middleware('permission:students,import');
    Route::get('/{id}', [StudentController::class, 'show']);
    Route::put('/{id}', [StudentController::class, 'update'])->middleware('permission:students,edit');
    Route::delete('/{id}', [StudentController::class, 'destroy'])->middleware('permission:students,delete');
});

// Student Evaluation Import Routes (Office Assistant, Program Coordinator, PGAM)
Route::prefix('imports')->middleware(['jwt.verify', 'password.updated', 'permission:students,import'])->group(function () {
    Route::post('/upload', [StudentEvaluationImportController::class, 'upload']);
    Route::get('/template', [StudentEvaluationImportController::class, 'template']);
    Route::get('/{importId}/status', [StudentEvaluationImportController::class, 'status']);
    Route::get('/{importId}/stream', [StudentEvaluationImportController::class, 'stream']);
    Route::get('/{importId}/errors', [StudentEvaluationImportController::class, 'downloadErrors']);
});

// Program Management Routes (Program Coordinator, PGAM)
Route::prefix('programs')->middleware(['jwt.verify', 'password.updated', 'permission:programs,view'])->group(function () {
    Route::get('/', [ProgramController::class, 'index']);
    Route::post('/', [ProgramController::class, 'store'])->middleware('permission:programs,create');
    Route::get('/{id}', [ProgramController::class, 'show']);
    Route::put('/{id}', [ProgramController::class, 'update'])->middleware('permission:programs,edit');
    Route::delete('/{id}', [ProgramController::class, 'destroy'])->middleware('permission:programs,delete');
});

// Evaluation Routes
Route::prefix('evaluations')->middleware(['jwt.verify', 'password.updated', 'permission:evaluations,view'])->group(function () {
    // Nomination routes (Research Supervisor, Program Coordinator, PGAM)
    Route::prefix('nominations')->group(function () {
        Route::get('/', [NominationController::class, 'index']);
        Route::post('/', [NominationController::class, 'store'])->middleware('role:ResearchSupervisor');
        Route::post('/lock', [NominationController::class, 'lockNominations'])->middleware('role:ProgramCoordinator');
        Route::put('/{id}', [NominationController::class, 'update'])->middleware('role:ResearchSupervisor');
        Route::post('/{id}/postpone', [NominationController::class, 'postpone'])->middleware('role:ResearchSupervisor');
    });

    // Assignment routes (Program Coordinator, PGAM)
    Route::prefix('assignments')->group(function () {
        Route::get('/chairperson-suggestions', [AssignmentController::class, 'chairpersonSuggestions'])->middleware('role:ProgramCoordinator');
        Route::post('/', [AssignmentController::class, 'update'])->middleware('role:ProgramCoordinator');
    });
});

// Examiner Suggestions Routes (Research Supervisor, Program Coordinator, PGAM)
Route::prefix('examiner-suggestions')->middleware(['jwt.verify', 'password.updated', 'permission:evaluations,view'])->group(function () {
    Route::get('/examiner1/{studentId}', [ExaminerSuggestionController::class, 'getExaminer1Suggestions']);
    Route::get('/examiner2/{studentId}', [ExaminerSuggestionController::class, 'getExaminer2Suggestions']);
    Route::get('/examiner3/{studentId}', [ExaminerSuggestionController::class, 'getExaminer3Suggestions']);
});

// Reports Routes (Program Coordinator, PGAM)
Route::prefix('reports')->group(function () {
    Route::get('unique-examiners', [EvaluationSummaryController::class, 'uniqueExaminers']);
    Route::get('examiner-sessions', [EvaluationSummaryController::class, 'examinerSessions']);
    Route::get('unique-chairpersons', [EvaluationSummaryController::class, 'uniqueChairpersons']);
    Route::get('chairperson-sessions', [EvaluationSummaryController::class, 'chairpersonSessions']);
    Route::get('chart-data', [EvaluationSummaryController::class, 'evaluationChartData']);
    Route::get('evaluation-summary', [EvaluationSummaryController::class, 'evaluationSummaryByProgramSemesterType']);
});

// Settings Routes (PGAM only)
Route::prefix('settings')->middleware(['jwt.verify', 'password.updated', 'role:PGAM'])->group(function () {
    Route::get('/', function () {
        // Settings controller will be implemented
        return response()->json(['message' => 'Settings endpoint']);
    });
    Route::put('/', function () {
        // Update settings
        return response()->json(['message' => 'Update settings endpoint']);
    });
});

// Log Management Routes (PGAM only)
Route::prefix('logs')->middleware(['jwt.verify', 'password.updated', 'role:PGAM'])->group(function () {
    Route::get('/', [LogController::class, 'index']);
    Route::get('/statistics', [LogController::class, 'statistics']);
    Route::get('/action-types', [LogController::class, 'actionTypes']);
    Route::get('/entity-types', [LogController::class, 'entityTypes']);
    Route::get('/{id}', [LogController::class, 'show']);
    Route::delete('/clear-old', [LogController::class, 'clearOldLogs']);
});

// Dashboard Routes
Route::prefix('dashboard')->middleware(['jwt.verify', 'password.updated'])->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/office-assistant', [DashboardController::class, 'officeAssistant'])->middleware('role:OfficeAssistant');
    Route::get('/research-supervisor', [DashboardController::class, 'researchSupervisor'])->middleware('role:ResearchSupervisor');
    Route::get('/program-coordinator', [DashboardController::class, 'programCoordinator'])->middleware('role:ProgramCoordinator');
    Route::get('/pgam', [DashboardController::class, 'pgam'])->middleware('role:PGAM');
});

// Student Export Routes
Route::middleware(['jwt.verify', 'password.updated', 'permission:students,export'])->group(function () {
    Route::post('/students/export', [StudentExportController::class, 'export']);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found.',
    ], 404);
});
