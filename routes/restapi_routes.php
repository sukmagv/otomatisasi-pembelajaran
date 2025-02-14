<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RestApi\RestApiController;

Route::group(['middleware' => ['auth']], function() {
    Route::prefix('restapi')->group(function () {
        Route::get('/start', [RestApiController::class, 'index'])->name('restapi_welcome');
        Route::get('/topic_detail', [RestApiController::class, 'restapi_topic_detail'])->name('restapi_topic_detail');
        // Route::get('/php-admin', [PHPController::class, 'php_admin'])->name('php_admin');
        // Route::post('/uploadimage', [PHPController::class, 'upload'])->name('uploadimage');
        // Route::get('/send-task', [PHPController::class, 'send_task'])->name('send_task');
        // Route::post('/session_progress', [PHPController::class, 'session_progress'])->name('session_progress');
        // Route::post('/task/submission', [PHPController::class, 'task_submission'])->name('task_submission');
        // Route::get('/result-task', [PHPController::class, 'result_task'])->name('result_task');
        // Route::get('/result-test-student', [PHPController::class, 'result_test'])->name('phpunit.result-test-student');
        // Route::any('/akhir-ujian', [PHPController::class, 'unittesting'])->name('unittesting');


        // Route::any('/baru/submit_score', [PHPController::class, 'submit_score_baru'])->name('php_submit_score_baru');
    });
});

// Route::group(['middleware' => ['auth', 'teacher']], function() {
//     Route::prefix('php')->group(function () {

//         Route::get('/teacher/topics',[PHPDosenController::class, 'topics'])->name('topics');
//         Route::get('/teacher/topics/add/{id}', [PHPDosenController::class, 'add_topics'])->name('topics');
//         Route::post('/teacher/topics/simpan', [PHPDosenController::class, 'simpan'])->name('simpan');
//     });

// });
