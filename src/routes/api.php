<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);

    Route::post('/password/forgot', [UserController::class, 'forgotPassword']);
    Route::post('/password/reset', [UserController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::post('/logout-all', [LoginController::class, 'logoutAll']);
    });
});


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {

        // User Management
        Route::post('/register', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Role Management
        Route::post('/users/{user}/roles', [RoleController::class, 'assign']);
        Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'revoke']);
        Route::get('/users/{user}/roles', [RoleController::class, 'userRoles']);

        // Enrollment Management
        Route::prefix('enrollments')->group(function () {
            Route::post('/', [EnrollmentController::class, 'store']);
            Route::get('/{student}', [EnrollmentController::class, 'show']);
            Route::put('/', [EnrollmentController::class, 'update']);
            Route::delete('/', [EnrollmentController::class, 'destroy']);
        });

        // Schedule Management
        Route::prefix('schedules')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::get('/subject/{subject}', [ScheduleController::class, 'show']);
            Route::put('/{id}', [ScheduleController::class, 'update']);
            Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        });
    });


/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('student')
    ->middleware(['auth:sanctum', 'role:student'])
    ->group(function () {

        Route::get('/my-subjects', [StudentController::class, 'index']);
        Route::get('/my-schedule', [ScheduleController::class, 'mySchedule']);

        Route::get('/academic-history', [GradeController::class, 'history']);
        Route::get('/gpa', [GradeController::class, 'gpa']);
        Route::get('/grades', [GradeController::class, 'grades']);

        Route::get('/enrollments/{student}', [EnrollmentController::class, 'show']);
    });


/*
|--------------------------------------------------------------------------
| TEACHER ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('teacher')
    ->middleware(['auth:sanctum', 'role:teacher'])
    ->group(function () {

        Route::post('/enrollments/{id}/grades', [GradeController::class, 'store']);
    });