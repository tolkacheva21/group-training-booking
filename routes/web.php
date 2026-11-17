<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

// Гость попадает на страницу входа, авторизованный пользователь на начальную страницу
Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

// Точка входа после авторизации: ведёт на существующие страницы приложения
Route::get('/dashboard', function () {
    return redirect()->route(
        auth()->user()->role?->name === 'admin' ? 'users.index' : 'records.index'
    );
})->middleware('auth')->name('dashboard');

// Профиль и записи доступны всем авторизованным пользователям
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('records', RecordController::class);
});

// Тренеры: администратор, менеджер и тренер
Route::middleware(['auth', 'role:admin,manager,trainer'])->group(function () {
    Route::resource('trainers', TrainerController::class);
});

// Залы и тренировки: администратор и менеджер
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('rooms', RoomController::class);
    Route::resource('workouts', WorkoutController::class);
});

// Управление пользователями: только администратор
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});

require __DIR__.'/auth.php';
