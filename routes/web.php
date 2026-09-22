<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'create'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('letters', LetterController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('letters/{letter}/comments', [LetterController::class, 'comment'])->name('letters.comments.store');
    Route::post('letters/{letter}/dispositions', [LetterController::class, 'dispose'])->name('letters.dispositions.store');
    Route::post('letters/{letter}/cc', [LetterController::class, 'cc'])->name('letters.cc.store');
    Route::post('letters/{letter}/close', [LetterController::class, 'close'])->name('letters.close');
    Route::post('letters/{letter}/verify', [LetterController::class, 'verify'])->middleware('role:admin_umum,super_admin')->name('letters.verify');
    Route::post('letters/{letter}/cancel', [LetterController::class, 'cancel'])->name('letters.cancel');
    Route::post('letters/{letter}/dispositions/{disposition}/reply', [LetterController::class, 'reply'])->name('letters.dispositions.reply');
    Route::middleware('role:admin_umum,super_admin')->prefix('master')->name('master.')->group(function (): void {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('categories', CategoryController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});
