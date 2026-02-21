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

    // ✅ CRUD
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('projects', ProjectController::class);

    // ✅ Upload endpoint used by your api.js
    Route::post('/storage/edutrail/upload', [StorageController::class, 'upload']);
});

