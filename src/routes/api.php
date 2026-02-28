<?php

use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [UserController::class, 'store']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/logout-all', [LoginController::class, 'logoutAll'])->middleware('auth:sanctum');

    Route::post('/password/forgot', [UserController::class, 'forgotPassword']);
    Route::post('/password/reset', [UserController::class, 'resetPassword']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/users/{user}/roles', [RoleController::class, 'assign']);
    Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'revoke']);
    Route::get('/users/{user}/roles', [RoleController::class, 'userRoles']);

    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/my-subjects', [StudentController::class, 'index']);
    Route::get('/enrollments/{student}', [EnrollmentController::class, 'show']);
    Route::get('/my-schedule', [ScheduleController::class, 'mySchedule']);
});

Route::prefix('enrollments')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/', [EnrollmentController::class, 'store']);
    Route::get('/{student}', [EnrollmentController::class, 'show']);
    Route::delete('/', [EnrollmentController::class, 'destroy']);
    Route::put('/', [EnrollmentController::class, 'update']);
});

Route::prefix('schedules')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/', [ScheduleController::class, 'store']);
    Route::get('/subject/{subject}', [ScheduleController::class, 'show']);
    Route::put('/{id}', [ScheduleController::class, 'update']);
    Route::delete('/{id}', [ScheduleController::class, 'destroy']);
});