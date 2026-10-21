<?php

use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Dashboard'))->name('dashboard');

Route::resource('vehicles', VehicleController::class);
Route::get('vehicles/{vehicle}/photo', [VehicleController::class, 'photo'])
    ->name('vehicles.photo')
    ->withTrashed();
Route::patch('vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])
    ->name('vehicles.restore')
    ->withTrashed();

Route::get('/expenses', fn () => Inertia::render('Expenses/Index'))->name('expenses.index');
Route::get('/fuelings', fn () => Inertia::render('Fuelings/Index'))->name('fuelings.index');
Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports.index');
Route::get('/categories', fn () => Inertia::render('Categories/Index'))->name('categories.index');

Route::fallback(fn () => Inertia::render('Error', ['status' => 404])
    ->toResponse(request())
    ->setStatusCode(404));
