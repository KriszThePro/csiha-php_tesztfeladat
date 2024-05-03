<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Database\Seeders\DatabaseSeeder;

Route::get('/api/tasks', [TaskController::class, 'list']);
Route::get('/api/tasks/count', [TaskController::class, 'count']);
Route::post('/api/tasks', [TaskController::class, 'add']);
Route::put('/api/tasks/{id}', [TaskController::class, 'edit']);
Route::put('/api/tasks/{id}/complete', [TaskController::class, 'complete']);
Route::delete('/api/tasks', [TaskController::class, 'deleteMany']);
Route::post('/api/tasks/factory', [TaskController::class, 'factory']);

Route::get('/api/users', [UserController::class, 'list']);

Route::post('/api/db-seed', [DatabaseSeeder::class, 'run']);

Route::view('/{any}', 'welcome')->where('any', '.*');