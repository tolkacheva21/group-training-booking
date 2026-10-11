<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainerController;

Route::inertia('/', 'welcome')->name('home');
Route::resource('trainers', TrainerController::class);