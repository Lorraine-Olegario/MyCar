<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Dashboard'))->name('dashboard');

Route::get('/vehicles', fn () => Inertia::render('Vehicles/Index'))->name('vehicles.index');
Route::get('/expenses', fn () => Inertia::render('Expenses/Index'))->name('expenses.index');
Route::get('/fuelings', fn () => Inertia::render('Fuelings/Index'))->name('fuelings.index');
Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports.index');
Route::get('/categories', fn () => Inertia::render('Categories/Index'))->name('categories.index');

Route::fallback(fn () => Inertia::render('Error', ['status' => 404])
    ->toResponse(request())
    ->setStatusCode(404));
