<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FuelingController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', DashboardController::class)->name('dashboard');

Route::resource('vehicles', VehicleController::class);
Route::get('vehicles/{vehicle}/photo', [VehicleController::class, 'photo'])
    ->name('vehicles.photo')
    ->withTrashed();
Route::patch('vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])
    ->name('vehicles.restore')
    ->withTrashed();

Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'update', 'destroy']);
Route::get('expenses/{expense}/receipt', [ExpenseController::class, 'receipt'])->name('expenses.receipt');
Route::resource('recurring', RecurringExpenseController::class)
    ->only(['index', 'store', 'update'])
    ->parameters(['recurring' => 'recurring']);
Route::post('recurring/occurrences/{occurrence}/pay', [RecurringExpenseController::class, 'pay'])->name('recurring.occurrences.pay');
Route::patch('recurring/occurrences/{occurrence}/cancel', [RecurringExpenseController::class, 'cancel'])->name('recurring.occurrences.cancel');
Route::resource('maintenances', MaintenanceController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('fuelings', FuelingController::class)->only(['index', 'store', 'update', 'destroy']);
Route::get('/reports', fn () => Inertia::render('Reports/Index'))->name('reports.index');
Route::resource('categories', ExpenseCategoryController::class)
    ->only(['index', 'store', 'update', 'destroy']);
Route::patch('categories/{category}/toggle', [ExpenseCategoryController::class, 'toggle'])->name('categories.toggle');
Route::patch('categories/{category}/move', [ExpenseCategoryController::class, 'move'])->name('categories.move');

Route::fallback(fn () => Inertia::render('Error', ['status' => 404])
    ->toResponse(request())
    ->setStatusCode(404));
