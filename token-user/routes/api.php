<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\AuthenticateToken;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::post('/login', [UserController::class, 'login']);
Route::patch('/user/name', [UserController::class, 'updateName'])
    ->middleware(AuthenticateToken::class);
