<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstructorsController;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    
});

Route::middleware('auth')->group(function (): void {
    Route::resource('instructors', InstructorsController::class);
});
