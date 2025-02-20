<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RestApi\RestApiController;
use App\Http\Controllers\RestApi\TeacherController;

Route::group(['middleware' => ['auth', 'student']], function() {
    Route::prefix('restapi')->group(function () {
        Route::get('/start-student', [RestApiController::class, 'index'])->name('restapi_student');
        Route::get('/topic-detail', [RestApiController::class, 'topic_detail'])->name('restapi_topic_detail');
        Route::post('/submit-task', [RestApiController::class, 'submit_task'])->name('restapi_submit_task');
        Route::get('/get-progress', [RestApiController::class, 'getProgress'])->name('restapi_get_progress');
    });
});

Route::group(['middleware' => ['auth', 'teacher']], function() {
    Route::prefix('restapi')->group(function () {
        Route::get('/start-teacher', [TeacherController::class, 'index'])->name('restapi_teacher');
        Route::post('/add-topic', [TeacherController::class, 'addTopic'])->name('restapi_add_topic');
        Route::post('/update-topic', [TeacherController::class, 'updateTopic'])->name('restapi_update_topic');
        Route::post('/delete-topic', [TeacherController::class, 'deleteTopic'])->name('restapi_delete_topic');
        Route::post('/add-task', [TeacherController::class, 'addTask'])->name('restapi_add_task');
        Route::post('/update-task', [TeacherController::class, 'updateTask'])->name('restapi_update_task');
        Route::post('/delete-task', [TeacherController::class, 'deleteTask'])->name('restapi_delete_task');
    });
});