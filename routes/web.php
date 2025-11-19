<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('diet-calculator');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/diet-calculator', function () {
    return view('diet-calculator');
})->name('diet-calculator');

Route::post('/diet-plan/calculate', [App\Http\Controllers\DietPlanController::class, 'calculate'])
    ->name('diet-plan.calculate');

Route::get('/diet-plan/status', [App\Http\Controllers\DietPlanController::class, 'checkStatus'])
    ->name('diet-plan.status');

require __DIR__.'/settings.php';
