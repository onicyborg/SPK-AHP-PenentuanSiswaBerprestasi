<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\AssessorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Assessment\PeriodController as AssessmentPeriodController;
use App\Http\Controllers\Assessment\CriteriaController as AssessmentCriteriaController;
use App\Http\Controllers\Assessment\CandidateController as AssessmentCandidateController;
use App\Http\Controllers\Assessment\PairwiseCriteriaController as AssessmentPairwiseCriteriaController;
use App\Http\Controllers\Assessment\PairwiseAlternativesController as AssessmentPairwiseAlternativesController;
use App\Http\Controllers\Assessment\ScoreController as AssessmentScoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This file defines the web routes for the application.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [AuthController::class, 'updateProfilePhoto'])->name('profile.photo');
    Route::post('/profile/change-password', [AuthController::class, 'changePassword'])->name('profile.change_password');

    // Students
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/{id}', [SiswaController::class, 'show'])->name('siswa.show');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/siswa/{id}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])->name('siswa.destroy');

    // Assessment Periods (Wizard Steps)
    Route::prefix('assessment/periods')->name('assessment.periods.')->group(function () {
        // Period CRUD
        Route::get('/', [AssessmentPeriodController::class, 'index'])->name('index');
        Route::get('/{period}', [AssessmentPeriodController::class, 'show'])->name('show');
        Route::post('/', [AssessmentPeriodController::class, 'store'])->name('store');
        Route::put('/{period}', [AssessmentPeriodController::class, 'update'])->name('update');
        Route::delete('/{period}', [AssessmentPeriodController::class, 'destroy'])->name('destroy');

        // Step 1: Criteria CRUD
        Route::post('/{period}/criteria', [AssessmentCriteriaController::class, 'store'])->name('criteria.store');
        Route::put('/{period}/criteria/{criterion}', [AssessmentCriteriaController::class, 'update'])->name('criteria.update');
        Route::delete('/{period}/criteria/{criterion}', [AssessmentCriteriaController::class, 'destroy'])->name('criteria.destroy');

        // Step 1: Pairwise criteria
        Route::get('/{period}/pairwise/criteria', [AssessmentPairwiseCriteriaController::class, 'list'])->name('pairwise.criteria.list');
        Route::post('/{period}/pairwise/criteria', [AssessmentPairwiseCriteriaController::class, 'upsert'])->name('pairwise.criteria.upsert');
        Route::post('/{period}/pairwise/criteria/calculate', [AssessmentPairwiseCriteriaController::class, 'calculate'])->name('pairwise.criteria.calculate');

        // Step 1: Submit Setup (lock)
        Route::post('/{period}/submit-setup', [AssessmentPeriodController::class, 'submitSetup'])->name('submit_setup');

        // Step 2: Candidates pick list
        Route::get('/{period}/candidates/available', [AssessmentCandidateController::class, 'available'])->name('candidates.available');
        Route::get('/{period}/candidates/selected', [AssessmentCandidateController::class, 'selected'])->name('candidates.selected');
        Route::post('/{period}/candidates/attach', [AssessmentCandidateController::class, 'attach'])->name('candidates.attach');
        Route::post('/{period}/candidates/detach', [AssessmentCandidateController::class, 'detach'])->name('candidates.detach');

        // Step 3: Pairwise alternatives per criterion
        Route::get('/{period}/pairwise/alternatives', [AssessmentPairwiseAlternativesController::class, 'list'])->name('pairwise.alternatives.list');
        Route::post('/{period}/pairwise/alternatives', [AssessmentPairwiseAlternativesController::class, 'upsert'])->name('pairwise.alternatives.upsert');
        Route::post('/{period}/pairwise/alternatives/calculate', [AssessmentPairwiseAlternativesController::class, 'calculate'])->name('pairwise.alternatives.calculate');

        // Step 3 (new): Scores grid endpoints
        Route::get('/{period}/scores', [AssessmentScoreController::class, 'index'])->name('scores.index');
        Route::post('/{period}/scores/batch', [AssessmentScoreController::class, 'batchStore'])->name('scores.batch');
        Route::post('/{period}/scores/calculate', [AssessmentScoreController::class, 'calculate'])->name('scores.calculate');
        Route::get('/{period}/scores/stats', [AssessmentScoreController::class, 'stats'])->name('scores.stats');
        Route::get('/{period}/scores/details', [AssessmentScoreController::class, 'normalizationDetails'])->name('scores.details');
        Route::get('/{period}/scores/completeness', [AssessmentScoreController::class, 'completeness'])->name('scores.completeness');

        // Step 4: Results & summaries
        Route::get('/{period}/results', [AssessmentScoreController::class, 'results'])->name('results.index');
        Route::get('/{period}/results/{candidateId}/breakdown', [AssessmentScoreController::class, 'breakdown'])->name('results.breakdown');
        Route::get('/{period}/weights', [AssessmentScoreController::class, 'weightsList'])->name('weights.list');
        Route::get('/{period}/weights/roots', [AssessmentScoreController::class, 'weightsRoots'])->name('weights.roots');
        Route::get('/{period}/criteria', [AssessmentScoreController::class, 'criteriaList'])->name('criteria.list');
        Route::get('/{period}/criteria/{parentId}/children/stats', [AssessmentScoreController::class, 'childrenStats'])->name('criteria.children.stats');
        Route::post('/{period}/finalize', [AssessmentScoreController::class, 'finalize'])->name('periods.finalize');
    });

    // Users management (Assessors)
    Route::get('/assessor', [AssessorController::class, 'index'])->name('assessor.index');
    Route::get('/assessor/{id}', [AssessorController::class, 'show'])->name('assessor.show');
    Route::post('/assessor', [AssessorController::class, 'store'])->name('assessor.store');
    Route::put('/assessor/{id}', [AssessorController::class, 'update'])->name('assessor.update');
    Route::delete('/assessor/{id}', [AssessorController::class, 'destroy'])->name('assessor.destroy');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
