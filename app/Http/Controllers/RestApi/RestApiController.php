<?php

namespace App\Http\Controllers\RestApi;

// use App\Models\PHP\Topic;
use Carbon\Carbon;
use App\Models\RestApi\Task;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Session;
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
        $taskWithFile = $result->tasks->firstWhere('file_path', '!=', null);

        $pdf_reader = $taskWithFile ? 1 : 0;

        $activeTask = $tasks[$topic_id]->firstWhere('id', $request->query('task_id')) ?? null;

        // Ambil submission terakhir untuk user dan task tertentu
        $submission = Submission::where('user_id', auth()->id())
            ->where('task_id', $task_id)
            ->latest()
            ->first();

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
            // 'output' => $output,
        ]);
    }

    public function getProgress()
    {
        // Get user ID
        $userId = auth()->id();

        // Count all topics (total tasks in database)
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
            // If the user uploads a new file, upload the file & update the path
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('restapi/submissions', $fileName, 'public');
            }

            // Save submit data to database
            $submission = Submission::create([
                'user_id' => auth()->id(),
                'task_id' => (int) $request->task_id,
                'submit_path' => $filePath,
                'submit_comment' => $request->comment,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Commit if success
            DB::commit();

            // Progress calculation
            $this->getProgress();

            // Get topic ID
            $topicId = Task::where('id', $submission->task_id)->value('topic_id');

            // Run Codeception test
            $this->runCodeceptionTest($topicId, $filePath);

            // Get test result from codeception
            $testResult = $this->runCodeceptionTest($topicId, $filePath);

            return back()->with('success', 'Upload berhasil! Tes otomatis telah dijalankan.')
                     ->with('test_result', $testResult);

        } catch (\Exception $e) {
            DB::rollBack(); // rollback if failed
            return back()->with('error', 'Gagal menyimpan submission: ' . $e->getMessage());
        }
    }

    // Test files
    private $testFiles = [
        2 => 'Topic1DataTest.php',
        3 => 'Topic2PostTest.php',
        4 => 'Topic3GetTest.php',
        5 => 'Topic4PutTest.php',
        6 => 'Topic5DeleteTest.php',
    ];

    private function runCodeceptionTest($topicId, $filePath)
    {
        if (!isset($this->testFiles[$topicId])) {
            Session::put('test_result', "Tidak ada test yang cocok untuk Topic ID: $topicId");
            return "Tidak ada test yang cocok untuk Topic ID: $topicId";
        }

        $testFile = $this->testFiles[$topicId];
        $submissionPath = public_path("storage/" . $filePath);

        // Run Codeception test
        $command = ['C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe', base_path('vendor/bin/codecept'), 'run', 'unit', $testFile];
        $process = new Process($command);
        $process->setTimeout(300);
        $process->setEnv(['testFile' => $submissionPath]); // Set environment variable
        $process->setWorkingDirectory(base_path());

        try {
            $process->mustRun(); // Ensure the process runs successfully
            $output = $process->getOutput();
        } catch (\Exception $e) {
            $output = "Gagal menjalankan Codeception: " . ($e->getMessage() ?: $process->getErrorOutput());
        }

        // Save test result to session
        Session::put('test_result', $output);

        return $output;
    }
}
