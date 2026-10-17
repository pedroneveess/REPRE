<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SchoolClassesController;
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

Route::post('/signUp', [AuthController::class, 'createUser']);

Route::post('/signIn', [AuthController::class, 'userLogin']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/loggedUser', [AuthController::class, 'loggedUser']);

    Route::put('/updateUser', [AuthController::class, 'updateUser']);

    Route::delete('/deleteUser', [AuthController::class, 'deleteUser']);

    Route::post('/logoutUser', [AuthController::class, 'logoutUser']);

    Route::apiResource('/school_classes', SchoolClassesController::class)->where(['school_class' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('/tasks', TaskController::class)->where(['task' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('/task_completions', TaskCompletionController::class)->where(['task_completion' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('/announcements', AnnouncementController::class)->where(['announcement' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('/organizations', OrganizationController::class)->where(['organization' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::apiResource('/posts', PostController::class)->where(['post' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);

    Route::get('/school_classes/{classId}/members', [ClassMemberController::class, 'index'])->whereUuid('classId');
    Route::post('/school_classes/{classId}/members', [ClassMemberController::class, 'store'])->whereUuid('classId');
    Route::patch('/school_classes/{classId}/members/{userId}', [ClassMemberController::class, 'update'])->whereUuid(['classId', 'userId']);
    Route::delete('/school_classes/{classId}/members/{userId}', [ClassMemberController::class, 'destroy'])->whereUuid(['classId', 'userId']);

    Route::get('/organizations/{organizationId}/members', [OrganizationMemberController::class, 'index'])->whereUuid('organizationId');
    Route::post('/organizations/{organizationId}/members', [OrganizationMemberController::class, 'store'])->whereUuid('organizationId');
    Route::patch('/organizations/{organizationId}/members/{userId}', [OrganizationMemberController::class, 'update'])->whereUuid(['organizationId', 'userId']);
    Route::delete('/organizations/{organizationId}/members/{userId}', [OrganizationMemberController::class, 'destroy'])->whereUuid(['organizationId', 'userId']);

    Route::apiResource('/calendar_events', CalendarEventController::class)->where(['calendar_event' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);
    Route::apiResource('/users', UserController::class)->where(['user' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}']);
    Route::get('/dashboard', [DashboardController::class, 'show']);
});
