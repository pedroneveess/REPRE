<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskCompletionController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ClassMemberController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;

Route::post('/auth/register', [AuthController::class, 'createUser']);

Route::post('/auth/login', [AuthController::class, 'userLogin']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/me', [AuthController::class, 'loggedUser']);

    Route::put('/auth/me', [AuthController::class, 'updateUser']);

    Route::delete('/auth/me', [AuthController::class, 'deleteUser']);

    Route::post('/auth/logout', [AuthController::class, 'logoutUser']);

    Route::apiResource('/school-classes', SchoolClassController::class)->whereUuid('school_class');

    Route::apiResource('/tasks', TaskController::class)->whereUuid('task');

    Route::apiResource('/task-completions', TaskCompletionController::class)->whereUuid('task_completion');

    Route::apiResource('/announcements', AnnouncementController::class)->whereUuid('announcement');

    Route::apiResource('/organizations', OrganizationController::class)->whereUuid('organization');

    Route::apiResource('/posts', PostController::class)->whereUuid('post');

    Route::get('/school-classes/{school_class}/members', [ClassMemberController::class, 'index'])->whereUuid('school_class');
    Route::post('/school-classes/{school_class}/members', [ClassMemberController::class, 'store'])->whereUuid('school_class');
    Route::patch('/school-classes/{school_class}/members/{user_id}', [ClassMemberController::class, 'update'])->whereUuid(['school_class', 'user_id']);
    Route::delete('/school-classes/{school_class}/members/{user_id}', [ClassMemberController::class, 'destroy'])->whereUuid(['school_class', 'user_id']);

    Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->whereUuid('organization');
    Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])->whereUuid('organization');
    Route::patch('/organizations/{organization}/members/{user_id}', [OrganizationMemberController::class, 'update'])->whereUuid(['organization', 'user_id']);
    Route::delete('/organizations/{organization}/members/{user_id}', [OrganizationMemberController::class, 'destroy'])->whereUuid(['organization', 'user_id']);

    Route::apiResource('/calendar-events', CalendarEventController::class)->whereUuid('calendar_event');
    Route::apiResource('/users', UserController::class)->whereUuid('user');
    Route::get('/dashboard', [DashboardController::class, 'show']);
});
