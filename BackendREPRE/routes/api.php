<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/signUp', [AuthController::class, 'createUser']);

Route::post('/signIn', [AuthController::class, 'userLogin']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/loggedUser', [AuthController::class, 'loggedUser']);

    Route::put('/updateUser', [AuthController::class, 'updateUser']);

    Route::delete('/deleteUser', [AuthController::class, 'deleteUser']);

    Route::post('/logoutUser', [AuthController::class, 'logoutUser']);

});
