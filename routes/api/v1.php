<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\InterestAssessmentController;
use App\Http\Controllers\Student\JobListingController as StudentJobListingController;
use App\Http\Controllers\Student\KnowledgeCheckController;
use App\Http\Controllers\Student\PortfolioController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\RoadmapController;
use App\Http\Controllers\Student\RoadmapGeneratorController;
use App\Http\Controllers\Student\SelfAssessmentController;
use App\Http\Controllers\Student\StreakController;
use App\Http\Controllers\Professional\DashboardController as ProfessionalDashboardController;
use App\Http\Controllers\Professional\JobController;
use App\Http\Controllers\Professional\ApplicantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/ automatically.
| Organized by: Auth, Profile, Student, Professional
|
*/

// ============================================================================
// AUTH ROUTES (Public)
// ============================================================================
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'register']);
    Route::post('/login', [AuthenticatedSessionController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
});

// Google OAuth (no throttle — redirect flow)
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    // Current user info
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => ['user' => $request->user()->load(['studentProfile', 'professionalProfile'])],
        ]);
    });

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout']);

    // Profile completion & management
    Route::post('/complete-profile', [ProfileController::class, 'completeProfile']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

    // ========================================================================
    // STUDENT ROUTES
    // ========================================================================
    Route::prefix('student')->middleware(['role:student', 'profile.completed'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [StudentDashboardController::class, 'index']);

        // Interest Assessment (AI-driven)
        Route::middleware('throttle:ai')->group(function () {
            Route::post('/interest/start', [InterestAssessmentController::class, 'start']);
            Route::post('/interest/answer', [InterestAssessmentController::class, 'answer']);
        });
        Route::get('/career-recommendations', [InterestAssessmentController::class, 'recommendations']);

        // Self Assessment & Knowledge Check
        Route::middleware('throttle:ai')->group(function () {
            Route::post('/assessment/start', [KnowledgeCheckController::class, 'start']);
            Route::post('/assessment/submit', [KnowledgeCheckController::class, 'submit']);
        });
        Route::get('/assessment/history', [KnowledgeCheckController::class, 'history']);
        Route::get('/assessment/history/{nodeId}', [KnowledgeCheckController::class, 'nodeHistory']);

        // Roadmap
        Route::middleware('throttle:ai')->group(function () {
            Route::post('/roadmap/generate', [RoadmapGeneratorController::class, 'store']);
        });
        Route::get('/roadmap', [RoadmapController::class, 'index']);

        // Progress Tracker
        Route::get('/progress', [ProgressController::class, 'index']);
        Route::get('/progress/{nodeId}', [ProgressController::class, 'show']);

        // Job Listings (Student view)
        Route::get('/jobs', [StudentJobListingController::class, 'index']);
        Route::get('/jobs/applied', [StudentJobListingController::class, 'applied']);
        Route::get('/jobs/{id}', [StudentJobListingController::class, 'show']);
        Route::post('/jobs/{id}/apply', [StudentJobListingController::class, 'apply']);

        // Portfolio
        Route::get('/portfolio', [PortfolioController::class, 'index']);
        Route::post('/portfolio', [PortfolioController::class, 'store']);
        Route::put('/portfolio/{id}', [PortfolioController::class, 'update']);
        Route::delete('/portfolio/{id}', [PortfolioController::class, 'destroy']);
        Route::patch('/portfolio/{id}/toggle', [PortfolioController::class, 'toggleVisibility']);

        // Daily Streak
        Route::get('/streak', [StreakController::class, 'index']);
        Route::post('/streak/checkin', [StreakController::class, 'checkin']);
        Route::get('/streak/history', [StreakController::class, 'history']);
    });

    // ========================================================================
    // PROFESSIONAL ROUTES
    // ========================================================================
    Route::prefix('professional')->middleware(['role:professional', 'profile.completed'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [ProfessionalDashboardController::class, 'index']);

        // Job Management
        Route::get('/jobs', [JobController::class, 'index']);
        Route::post('/jobs', [JobController::class, 'store']);
        Route::get('/jobs/{id}', [JobController::class, 'show']);
        Route::put('/jobs/{id}', [JobController::class, 'update']);
        Route::patch('/jobs/{id}/status', [JobController::class, 'toggleStatus']);

        // Applicant Tracking
        Route::get('/applicants', [ApplicantController::class, 'index']);
        Route::get('/jobs/{id}/applicants', [ApplicantController::class, 'byJob']);
        Route::get('/applicants/{id}', [ApplicantController::class, 'show']);
        Route::get('/applicants/{id}/profile', [ApplicantController::class, 'profile']);
    });

    // ========================================================================
    // [FUTURE FEATURE] Employee Management
    // ========================================================================
    // Route::prefix('professional')->middleware(['role:professional', 'profile.completed'])->group(function () {
    //     Route::get('/employees', [EmployeeController::class, 'index']);
    //     Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    //     Route::post('/employees', [EmployeeController::class, 'store']);
    //     Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    //     Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    // });
});
