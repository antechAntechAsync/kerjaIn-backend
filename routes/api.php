<?php

use App\Http\Controllers\Student\InterestAssessmentController;
use App\Http\Controllers\Student\ProgressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route Authentication Modules

Route::post('/register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);
Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [App\Http\Controllers\Auth\NewPasswordController::class, 'store']);
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json([
        "user" => $request->user()
    ]);
});

Route::prefix('student')->middleware('auth:sanctum')->group(function () {
    // Route Student Dashboard
    // Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index']);

    // Interest Assessment
    Route::get('/assessment/interests', [InterestAssessmentController::class, 'index']);

    Route::post('/assessment/interests/submit', [InterestAssessmentController::class, 'submit']);

    // Career Recommendation Result
    Route::get('/career-recommendations', [InterestAssessmentController::class, 'recommendations']);
    // Route Skill Assesment
    // Route::get('/assessment/skills', [App\Http\Controllers\SkillAssessmentController::class, 'index']);
    // Route::post('/assessment/skills/submit', [App\Http\Controllers\SkillAssessmentController::class, 'store']);

    // Route Roadmap Generator
    // Route::post('/roadmap/generate', [App\Http\Controllers\RoadmapGeneratorController::class, 'store']);
    // Route::get('/roadmap/{user}', [App\Http\Controllers\RoadmapController::class, 'index']);

    // Route Learning Progress Tracker
    // Route::get('/progress', [App\Http\Controllers\LearningProgressController::class, 'index']);
    Route::get('/progress', [ProgressController::class, 'index']);
    Route::get('/progress/{node}', [ProgressController::class, 'show']);
    Route::post('/progress/update', [ProgressController::class, 'update']);
    // Route::post('/progress/update', [App\Http\Controllers\LearningProgressController::class, 'store']);

    // Route Portofolio System
    // Route::post('/portofolio', [App\Http\Controllers\PortofolioController::class, 'store']);
    // Route::get('/portofolio/{user}', [App\Http\Controllers\PortofolioController::class, 'index']);
});


Route::prefix('human-resource')->middleware('auth:sanctum')->group(function () {
    // Route Human Resource Dashboard
    // Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index']);

    // Route::get('/employees', [App\Http\Controllers\EmployeeController::class, 'index']);
    // Route::get('/employees/{id}', [App\Http\Controllers\EmployeeController::class, 'show']);
    // Route::post('/employees', [App\Http\Controllers\EmployeeController::class, 'store']);
    // Route::put('/employees/{id}', [App\Http\Controllers\EmployeeController::class, 'update']);
    // Route::delete('/employees/{id}', [App\Http\Controllers\EmployeeController::class, 'destroy']);

    // Route Job Listing
    // Route::post('/jobs', [App\Http\Controllers\JobListingController::class, 'store']);
    // Route::get('/jobs', [App\Http\Controllers\JobListingController::class, 'index']);
    // Route::get('/job/{id}', [App\Http\Controllers\JobListingController::class, 'show']);
});
