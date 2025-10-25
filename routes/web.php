<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\AssessorController;
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

    // Users management (Assessors)
    Route::get('/assessor', [AssessorController::class, 'index'])->name('assessor.index');
    Route::get('/assessor/{id}', [AssessorController::class, 'show'])->name('assessor.show');
    Route::post('/assessor', [AssessorController::class, 'store'])->name('assessor.store');
    Route::put('/assessor/{id}', [AssessorController::class, 'update'])->name('assessor.update');
    Route::delete('/assessor/{id}', [AssessorController::class, 'destroy'])->name('assessor.destroy');

    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
});
