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
    
            $runOutput = null;
            $testResult = null;
            if ($submission) {
                $feedback = Feedback::where('submission_id', $submission->id)->latest()->first();          
                $runOutput = $feedback ? json_decode($feedback->run_output, true) : null;
                $testResult = $feedback?->test_result;
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
        
        $filteredResult = $this->filterTestResult($testResult ?? '');

        // Set waktu mulai hanya jika belum pernah diset
        $sessionKey = "start_time_topic_{$topic_id}_task_{$task_id}";
        if (!session()->has($sessionKey)) {
            session([$sessionKey => now()]);
        }

        $startTime = session($sessionKey);

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
            'runOutput' => $runOutput ?? null,
            'testResult' => $filteredResult ?? null,
            'fileContent' => $fileContent ?? null,
            'startTime' => $startTime
        ]);
    }

    public function getProgress()
    {
        // Get user ID
        $userId = auth()->id();

        // Count all topics
        $totalTasks = Task::where('flag', 1)->count();

        // Count unique submitted tasks by user
        $uniqueSubmittedTasks = Submission::where('user_id', $userId)
            ->whereHas('task', function ($query) {
                $query->where('flag', 1);
            })
            ->distinct('task_id')
            ->count('task_id');

        // Calculate progress percentage
        $progress = ($totalTasks > 0) ? round(($uniqueSubmittedTasks / $totalTasks) * 100) : 0;

        // Save progress to session
        session(['progress' => $progress]);

        return response()->json(['progress' => $progress]);
    }

    public function submit_task(Request $request)
    {
        // Input validation
        $request->validate([
            'file' => 'required|file|max:2048|extensions:php,html',
            'comment' => 'nullable|string',
            'task_id' => 'required|exists:restapi_topic_tasks,id',
        ]);

        DB::beginTransaction(); // Start DB transaction

        try {
            $user = auth()->user();
            $username = $user->name;
            $file = $request->file('file');
            $fileName = time() . "_" . $file->getClientOriginalName();

            // Simpan file ke dalam storage/public/restapi/username/filename
            $filePath = "restapi/{$username}/{$fileName}";
            Storage::disk('public')->put($filePath, file_get_contents($file->getRealPath()));

            // Create new submission entry
            Submission::create([
                'user_id' => $user->id,
                'task_id' => (int) $request->task_id,
                'submit_path' => $filePath,
                'submit_comment' => $request->comment,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit(); // Commit if success

            // Optionally update progress if needed
            $this->getProgress();

            return back()->with('success', 'Upload berhasil!');

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

    private function testEnv(): array
    {
        return array_merge($_ENV, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => config('testenv.testing_connection', 'mysql_api_testing'),
            'CACHE_DRIVER' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]);
    }

    public function runCodeceptionTest(Request $request)
    {
        $userId = auth()->id();
        $taskId = $request->input('task_id');

        $submission = Submission::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->latest()
            ->firstOrFail();

        $runOutput = $this->runFile($userId, $taskId);

        $submissionPath = public_path("storage/" . $submission->submit_path);
        $topicId = Task::where('id', $taskId)->value('topic_id');

        $testBase = $this->testFiles[$topicId] ?? null;

        if (!$testBase) {
            $errorMessage = "Tidak ada test yang cocok untuk Topic ID: $topicId";
            Session::put('test_result', $errorMessage);
            return ['output' => $errorMessage, 'fileContents' => null];
        }

        $codeceptionFile = "tests/Api/{$testBase}Cest.php";

        // Simpan path file untuk digunakan oleh test
        File::put(base_path('tests/test-config.json'), json_encode([
            'testFile' => str_replace('/', DIRECTORY_SEPARATOR, $submissionPath),
        ]));

        $process = new Process([
            'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe',
            'vendor/bin/codecept',
            'run',
            'Api',
            $codeceptionFile,
        ], base_path());

        $process->setEnv($this->testEnv());

        $process->run();

        $cleanOutput = preg_replace('/\e\[([;\d]+)?m/', '', 
            $process->getOutput() . "\n" . $process->getErrorOutput()
        );

        Feedback::create([
            'submission_id' => $submission->id,
            'test_result' => $cleanOutput,
            'run_output' => json_encode($runOutput),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $filteredResult = $this->filterTestResult($cleanOutput);
        Session::flash('test_result', $filteredResult);

        return back()->with([
            'testResult' => $filteredResult,
            'runOutput' => $runOutput,
        ]);
    }

    protected function runFile($userId, $taskId)
    {
        $submission = Submission::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->latest()
            ->firstOrFail();

        if (!$submission) {
            return response()->json([
                'status' => 404,
                'error' => 'Submission not found.'
            ], 404);
        }

        $filePath = public_path("storage/" . $submission->submit_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 404,
                'error' => 'Submission file not found.'
            ], 404);
        }

        // Jalankan file eksternal tanpa input karena data hardcoded di dalam file
        $process = new Process(['php', $filePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengeksekusi file',
            ], 500);
        }

        // Ambil output dari file eksternal (harus format JSON)
        $output = json_decode($process->getOutput(), true);

        return [
            'output' => $output
        ];
    }

    protected function filterTestResult(string $output): string
    {
        // Jika semua pengujian berhasil
        if (preg_match('/^OK\s+\(\d+\stests?,\s+\d+\sassertions\)/m', $output)) {
            return "Semua pengujian berhasil.";
        }

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            // Step Fail lebih prioritas
            if (preg_match('/Step\s+Fail\s+"(.*?)"/', $line, $matches)) {
                return "Step Fail: " . $matches[1];
            }

            // Fail umum
            if (preg_match('/^Fail\s+(.*)/', $line, $matches)) {
                return "Fail: " . trim($matches[1]);
            }

            // Fatal / Parse error
            if (preg_match('/(Parse error|Fatal error|on line \d+)/i', $line)) {
                $normalizedLine = preg_replace('/in .*?restapi[\\\\\/][^\\\\\/]+[\\\\\/](\w+\.php)/', 'in $1', $line);
                return trim($normalizedLine);
            }
        }

        return "Pengujian gagal, tetapi tidak ditemukan detail error yang jelas.";
    }
}    