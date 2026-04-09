<?php

use App\Http\Controllers\Professional\JobController;
use App\Http\Controllers\Student\SelfAssessmentController;
use App\Http\Controllers\Student\InterestAssessmentController;
use App\Http\Controllers\Student\JobApplicationController;
use App\Http\Controllers\Student\KnowledgeCheckController;
use App\Http\Controllers\Student\PortfolioController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route Authentication Modules
Route::post('/register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'register']);
Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'login']);
Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [App\Http\Controllers\Auth\NewPasswordController::class, 'store']);
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json([
        "user" => $request->user()
    ]);
});

Route::prefix('student')->middleware(['auth:sanctum', 'role:student'])->group(function () {
    // Route Student Dashboard
    // Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index']);

    // Route Interest Assessment
    Route::post('/interest/start', [InterestAssessmentController::class, 'start']);

    Route::post('/interest/answer', [InterestAssessmentController::class, 'answer']);

    // Career Recommendation Result
    Route::get('/career-recommendations', [InterestAssessmentController::class, 'recommendations']);

    // Module Skill Assessment
    // Route Self Assesment
    Route::get('/assesment/skills', [SelfAssessmentController::class, 'questions']);

    Route::post('/assesment/skills/submit', [SelfAssessmentController::class, 'submit']);

    // Route Knowledge Check Assesment
    Route::get('/assessment/knowledge-check/questions', [KnowledgeCheckController::class, 'questions']);
    Route::post('/assessment/knowledge-check/submit', [KnowledgeCheckController::class, 'submit']);

    // Route Roadmap Generator
    // Route::post('/roadmap/generate', [App\Http\Controllers\RoadmapGeneratorController::class, 'store']);
    // Route::get('/roadmap/{user}', [App\Http\Controllers\RoadmapController::class, 'index']);

    // Route Learning Progress Tracker
    // Route::get('/progress', [App\Http\Controllers\LearningProgressController::class, 'index']);
    Route::get('/progress', [ProgressController::class, 'index']);
    Route::get('/progress/{node}', [ProgressController::class, 'show']);
    Route::put('/progress/mark-complete', [ProgressController::class, 'markComplete']);
    // Route::post('/progress/update', [App\Http\Controllers\LearningProgressController::class, 'store']);

    // Route Portfolio System
    Route::get('/portfolio', [PortfolioController::class, 'index']);
    Route::post('/portfolio', [PortfolioController::class, 'store']);

    // Route Project Management
    Route::post('/project', [ProjectController::class, 'store']);
    // Route::post('/portofolio', [App\Http\Controllers\PortofolioController::class, 'store']);
    // Route::get('/portofolio/{user}', [App\Http\Controllers\PortofolioController::class, 'index']);

    // Route Apply Job
    Route::post('/jobs/{id}/apply', [JobApplicationController::class, 'apply']);
});


Route::prefix('professional')->middleware(['auth:sanctum', 'role:professional'])->group(function () {
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

    Route::post('/jobs', [JobController::class, 'store']);
});
