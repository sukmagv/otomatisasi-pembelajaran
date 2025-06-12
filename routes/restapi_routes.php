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
        Route::post('/verify', [RestApiController::class, 'runCodeceptionTest'])->name('restapi_verify');
        Route::get('/hasil-praktikum/{username}',[RestApiController::class, 'runIndex'])->name('restapi_run_test_index');
    });
});

Route::get('/run-test/{username}',[RestApiController::class, 'runIndex']);
Route::match(['get', 'post', 'put', 'delete'], '/run-test/{username}/{filename}', [RestApiController::class, 'runTest']);


Route::group(['middleware' => ['auth', 'teacher']], function() {
    Route::prefix('restapi')->group(function () {
        Route::get('/start-teacher', [TeacherController::class, 'index'])->name('restapi_teacher');
        Route::get('/open-task', [TeacherController::class, 'openTask'])->name('restapi_open_task');
        Route::post('/add-task', [TeacherController::class, 'addTask'])->name('restapi_add_task');
        Route::post('/update-task', [TeacherController::class, 'updateTask'])->name('restapi_update_task');
        Route::post('/delete-task', [TeacherController::class, 'deleteTask'])->name('restapi_delete_task');
        Route::get('/export-pdf', [TeacherController::class, 'exportPdf'])->name('restapi_export_pdf');

        // Route::get('/start-teacher/tasks', [TeacherController::class, 'tasks'])->name('restapi_teacher_tasks');
    });
});