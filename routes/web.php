<?php

use App\Http\Controllers\RecordController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\TrainerController;

Route::inertia('/', 'welcome')->name('home');

// Пользователи crud
Route::resource('users', UserController::class);

// Комнаты crud
Route::resource('rooms', RoomController::class);

// Тренеры crud
Route::resource('trainers', TrainerController::class);

// Тренировки crud
// Route::recource('workouts', WorkoutController::class);

// Записи crud (возможно реализовать по другому)
// Route::resource('records', RecordController::class);
