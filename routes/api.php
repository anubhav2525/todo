<?php

use App\Http\Controllers\UserAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/user-signup', [UserAuthentication::class, 'create']);
Route::post('/user-signin', [UserAuthentication::class, 'show']);
Route::post('/user-forget', [UserAuthentication::class, 'edit']);
Route::post('/user-update', [UserAuthentication::class, 'update']);

// Route::post('/task-register/username','');
// Route::get('/task-completed/username','');
// Route::post('/task-history/username','');
// Route::post('/task-pending/username','');