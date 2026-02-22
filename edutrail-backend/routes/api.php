<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StorageController;

Route::post('/auth/sign-up', [AuthController::class, 'register']);

Route::middleware('web')->group(function () {
    Route::post('/auth/sign-in', [AuthController::class, 'login']);
    Route::post('/auth/sign-out', [AuthController::class, 'logout']);
    Route::get('/auth/session', [AuthController::class, 'session']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::get('/storage/{bucket}/public-url', [StorageController::class, 'publicUrl']);
    Route::post('/auth/update', [AuthController::class, 'update']);
    // ✅ CRUD
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('projects', ProjectController::class);

    // helper: project counts for tracker UI
    Route::get('projects/count', [ProjectController::class, 'count']);
    Route::get('projects/latest', [ProjectController::class, 'latest']);
    Route::get('projects/summary', [ProjectController::class, 'summary']);

    // ✅ Upload endpoint used by your api.js
    Route::post('/storage/edutrail/upload', [StorageController::class, 'upload']);

    // debug: recent laravel log lines (local debugging only)
    Route::get('/debug/logs', [\App\Http\Controllers\DebugController::class, 'recent']);
    
    // debug: create a project for a given user id (LOCAL ONLY)
    Route::post('/debug/projects/create-for-user', [\App\Http\Controllers\DebugController::class, 'createProjectForUser']);
    Route::get('/debug/projects/list-for-user', [\App\Http\Controllers\DebugController::class, 'listProjectsForUser']);
});

