<?php

use App\Modules\Evaluation\Controllers\AssignmentController;
use App\Modules\Evaluation\Controllers\NominationController;
use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\PasswordResetController;
use App\Modules\UserManagement\Controllers\UserController;
use App\Modules\UserManagement\Controllers\LecturerController;
use App\Modules\UserManagement\Controllers\RoleController;
use App\Modules\Student\Controllers\StudentController;
use App\Modules\Student\Controllers\StudentEvaluationImportController;
use App\Modules\Program\Controllers\ProgramController;

// API Health Check
Route::get('/status', function () {
    return response()->json([
        'status' => 'up',
    ]);
});

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
    Route::get('/my/permissions', [RoleController::class, 'myPermissions']);
    Route::post('/check-permission', [RoleController::class, 'checkPermission']);
    Route::post('/assign-pgam', [RoleController::class, 'assignPGAMRole'])->middleware('permission:users,edit');
    Route::get('/pgam/users', [RoleController::class, 'getPGAMUser']);
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
    // Nomination routes (Supervisor, Program Coordinator, PGAM)
    Route::prefix('nominations')->group(function () {
        Route::get('/', [NominationController::class, 'index']);
        Route::post('/', [NominationController::class, 'store'])->middleware('role:Supervisor');
        Route::put('/{id}', [NominationController::class, 'update'])->middleware('role:Supervisor');
        Route::post('/{id}/postpone', [NominationController::class, 'postpone'])->middleware('role:Supervisor');
        Route::post('/lock', [NominationController::class, 'lockNominations'])->middleware('role:ProgramCoordinator');
    });

    // Assignment routes (Program Coordinator, PGAM)
    Route::prefix('assignments')->group(function () {
        Route::get('/', [AssignmentController::class, 'index']);
        Route::post('/', [AssignmentController::class, 'assign'])->middleware('role:ProgramCoordinator');
        Route::put('/{id}', [AssignmentController::class, 'update'])->middleware('role:ProgramCoordinator');
        Route::post('/{id}/lock', [AssignmentController::class, 'lock'])->middleware('role:ProgramCoordinator');
    });
});

// Reports Routes (Program Coordinator, PGAM)
Route::prefix('reports')->middleware(['jwt.verify', 'password.updated', 'permission:reports,view'])->group(function () {
    Route::get('/statistics', function () {
        // Reports controller will be implemented
        return response()->json(['message' => 'Reports endpoint']);
    });
    Route::get('/download', function () {
        // Download reports
        return response()->json(['message' => 'Download reports endpoint']);
    })->middleware('permission:reports,download');
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

// Student Export Routes
Route::middleware(['jwt.verify', 'password.updated', 'permission:students,export'])->group(function () {
    Route::post('/students/export', [App\Modules\Student\Controllers\StudentExportController::class, 'export']);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found.',
    ], 404);
});
