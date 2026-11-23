<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

// Гость попадает на страницу входа, авторизованный пользователь на начальную страницу
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

// Точка входа после авторизации: ведёт на существующие страницы приложения
Route::get('/dashboard', function () {
    return redirect()->route(match (auth()->user()->role?->name) {
        'admin' => 'users.index',
        'trainer' => 'workouts.index',
        default => 'records.index',
    });
})->middleware('auth')->name('dashboard');

// Профиль и записи доступны всем авторизованным пользователям
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('records', RecordController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::patch('records/{record}/pay', [RecordController::class, 'pay'])->name('records.pay');
    Route::patch('records/{record}/cancel-payment', [RecordController::class, 'cancelPayment'])
        ->name('records.cancel-payment');
});

// Залы и инвентарь: администратор и менеджер
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('rooms', RoomController::class);
    Route::resource('inventories', InventoryController::class)->except(['show']);
});

// Управление тренировками: администратор, менеджер (любые) и тренер (только свои).
// Права на конкретную тренировку проверяются политикой WorkoutPolicy.
Route::middleware(['auth', 'role:admin,manager,trainer'])->group(function () {
    Route::resource('workouts', WorkoutController::class);
});

// Подтверждение и отклонение записей: администратор и менеджер
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::patch('records/{record}/confirm', [RecordController::class, 'confirm'])->name('records.confirm');
    Route::patch('records/{record}/reject', [RecordController::class, 'reject'])->name('records.reject');
});

// Управление пользователями: только администратор
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});

require __DIR__.'/auth.php';
