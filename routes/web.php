<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoomController;

Route::inertia('/', 'welcome')->name('home');

// Пользователи crud
Route::resource('users', UserController::class);

// Комнаты crud
Route::resource('rooms', RoomController::class);
