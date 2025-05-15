<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
use App\Models\RestApi\Task;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Feedback;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RestApiController extends Controller
{
    // Get all topics from database
    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);

        return view('restapi.student.index', [
            'topics' => $topics,
            'topicsCount' => $topicsCount,
        ]);
    }

    // Get topic detail from database
    public function topic_detail(Request $request)
    {
        // check if user is logged in
        $user = Auth::user();

        // Get ID from URL parameter
        $topic_id = (int) $request->query('id');
        $task_id = (int) $request->query('task_id');
        
        // Get topic details
        $result = Topic::with('tasks')->findOrFail($topic_id);
        
        // Get all topics
        $topics = Topic::all();

        // Get total topics count
        $topicsCount = Topic::count();

        $tasks = Task::all()->groupBy('topic_id');

        // Search file in tasks table by ID topic
        $taskWithFile = $result->tasks->where('file_path', '!=', null);
        $pdf_reader = $taskWithFile ? 1 : 0;
        $activeTask = $tasks[$topic_id]->firstWhere('id', $task_id) ?? null;

        // Get lastest submission data by user ID and task ID
        $submission = null;
        if ($task_id && $activeTask) {
            $submission = Submission::where('user_id', $user->id)
                ->where('task_id', $task_id)
                ->latest()
                ->first();
    
            $testResult = null;
            if ($submission) {
                $testResult = Feedback::where('submission_id', $submission->id)
                    ->latest()
                    ->value('test_result');
                if ($submission->submit_path) {
                    $fullPath = storage_path('app/public/' . $submission->submit_path);
                    if (file_exists($fullPath)) {
                        $fileContent = file_get_contents($fullPath);
                    }
                }
            }
            $viewFile = $request->query('view_file');
            if ($viewFile && $submission && $submission->submit_path) {
                $fullPath = storage_path('app/public/' . $submission->submit_path);
                if (file_exists($fullPath)) {
                    $content = file_get_contents($fullPath);
                    return response($content)->header('Content-Type', 'text/plain');
                }
            }

        }

        return view('restapi.student.topic_detail', [
            'row' => $result,
            'user' => $user,
            'topic_id' => $topic_id,
            'topics' => $topics,
            'tasks' => $tasks,
            'taskWithFile' => $taskWithFile,
            'pdf_reader' => $pdf_reader,
            'activeTask' => $activeTask,
            'topicsCount' => $topicsCount,
            'submission' => $submission,
            'testResult' => $testResult ?? null,
            'fileContent' => $fileContent ?? null,
        ]);
    }

    public function getProgress()
    {
        // Get user ID
        $userId = auth()->id();

        // Count all topics
        $totalTasks = Task::count();

        // Count unique submitted tasks by user
        $uniqueSubmittedTasks = Submission::where('user_id', $userId)
            ->distinct('task_id') // Hanya hitung task unik
            ->count('task_id');

        // Calculate progress percentage
        $progress = ($totalTasks > 0) ? round(($uniqueSubmittedTasks / $totalTasks) * 100) : 0;

        // Save progress to session
        session(['progress' => $progress]);

        return response()->json(['progress' => $progress]);
    }

    // Submit task to database
    public function submit_task(Request $request)
    {
        // Input validation
        $request->validate([
            'file' => 'required|file|max:2048|extensions:php,html',
            'comment' => 'nullable|string',
            'task_id' => 'required|exists:restapi_topic_tasks,id',
        ]);

        DB::beginTransaction(); // start database transaction

        try {
            // Get submission by user ID and task ID
            $submission = Submission::where('user_id', auth()->id())
                ->where('task_id', $request->task_id)
                ->first();

            // If submission already exists, delete the old file
            if ($submission && $submission->submit_path && Storage::disk('public')->exists($submission->submit_path)) {
                Storage::disk('public')->delete($submission->submit_path);
            }

            // Store the new file
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/submissions', $fileName, 'public');

            // If submission already exists, update it
            if ($submission) {
                $submission->submit_path = $filePath;
                $submission->submit_comment = $request->comment;
                $submission->submit_count = $submission->submit_count + 1;
                $submission->updated_at = now();
                $submission->save();
            } else {
                // If submission does not exist, create a new one
                Submission::create([
                    'user_id' => auth()->id(),
                    'task_id' => (int) $request->task_id,
                    'submit_path' => $filePath,
                    'submit_comment' => $request->comment,
                    'submit_count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit(); // Commit if success

            // Calculate progress
            $this->getProgress();

            return back()->with('success', 'Upload berhasil! Tes otomatis telah dijalankan.');

        } catch (\Exception $e) {
            DB::rollBack(); // rollback if failed
            return back()->with('error', 'Gagal menyimpan submission: ' . $e->getMessage());
        }
    }

    // Test files
    private $testFiles = [
        2 => 'Post',
        3 => 'Get',
        4 => 'Put',
        5 => 'Delete',
    ];

    public function runCodeceptionTest(Request $request)
    {
        $userId = auth()->id();
        $taskId = $request->input('task_id');

        $submission = Submission::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->latest()
            ->first();

        if (!$submission) {
            return back()->withErrors('Submission not found.');
        }

        $filePath = $submission->submit_path;
        $submissionPath = public_path("storage/" . $filePath);

        $topicId = Task::where('id', $taskId)->value('topic_id');

        // Check if the topic ID exists in the test files mapping
        if (!isset($this->testFiles[$topicId])) {
            $errorMessage = "Tidak ada test yang cocok untuk Topic ID: $topicId";
            Session::put('test_result', $errorMessage);
            return ['output' => $errorMessage, 'fileContents' => null];
        }
    
        // Get the test file name based on the topic ID
        $testBase = $this->testFiles[$topicId];

        // Set codeception file path
        $codeceptionFile = "tests/Api/{$testBase}Cest.php";
        File::put(base_path('tests/test-config.json'), json_encode([
            'testFile' => str_replace('/', DIRECTORY_SEPARATOR, $submissionPath),
        ]));
        
        // Run codeception
        $phpPath = 'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe'; // Sesuaikan jika portable
        $projectPath = base_path();

        $process = new Process([
            $phpPath,
            'vendor/bin/codecept',
            'run',
            'Api',
            $codeceptionFile,
        ], $projectPath);

        // Set environment variables
        $process->setEnv(array_merge($_ENV, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'null',
            'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]));

        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        // Combine output and error for full visibility
        $fullOutput = $output . "\n" . $errorOutput;

        // Optional: strip ANSI escape sequences
        $cleanOutput = preg_replace('/\e\[([;\d]+)?m/', '', $fullOutput);
        dd($cleanOutput);

        // Check if feedback already exists.
        $existingFeedback = Feedback::where('submission_id', $submission->id)->first();

        // If feedback exists, update it; otherwise, create a new one.
        if ($existingFeedback) {
            $existingFeedback->update([
                'test_result' => $cleanOutput,
                'updated_at' => Carbon::now(),
            ]);
        } else {
            Feedback::create([
                'submission_id' => $submission->id,
                'test_result' => $cleanOutput,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Save output to session (flash)
        Session::flash('test_result', $cleanOutput);

        return back()->with('testResult', $cleanOutput);
    }
}    