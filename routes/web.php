<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\AssessorController;
use App\Http\Controllers\Assessment\PeriodController as AssessmentPeriodController;
use App\Http\Controllers\Assessment\CriteriaController as AssessmentCriteriaController;
use App\Http\Controllers\Assessment\PairwiseCriteriaController as AssessmentPairwiseCriteriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});


Route::group(['middleware' => 'auth'], function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('dashboard');


    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/photo', [AuthController::class, 'updateProfilePhoto'])->name('profile.photo');
    Route::post('/profile/change-password', [AuthController::class, 'changePassword'])->name('profile.change_password');

    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/{id}', [SiswaController::class, 'show'])->name('siswa.show');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/siswa/{id}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])->name('siswa.destroy');

    // Assessment Periods
    Route::prefix('assessment/periods')->name('assessment.periods.')->group(function () {
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
    });

    // Users management (Assessors)
    Route::get('/assessor', [AssessorController::class, 'index'])->name('assessor.index');
    Route::get('/assessor/{id}', [AssessorController::class, 'show'])->name('assessor.show');
    Route::post('/assessor', [AssessorController::class, 'store'])->name('assessor.store');
    Route::put('/assessor/{id}', [AssessorController::class, 'update'])->name('assessor.update');
    Route::delete('/assessor/{id}', [AssessorController::class, 'destroy'])->name('assessor.destroy');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
