<?php

namespace App\Http\Controllers\RestApi;

// use App\Models\PHP\Topic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\RestApi\Task;
use Illuminate\Support\Facades\Auth;

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
            ->where('task_id', $request->id)
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

    // Submit task to database
    public function submit_task(Request $request)
    {
        // Validasi input
        $request->validate([
            'file' => 'required|file|max:2048|extensions:php,html',
            'comment' => 'nullable|string',
            'id' => 'required|exists:restapi_topic_tasks,id'
        ]);

        // Jika user mengupload file baru, upload file & update path
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('storage/restapi/submissions', $fileName, 'public');
        }

        // Save submit data to database
        Submission::create([
            'user_id' => auth()->id(), // Get user ID
            'task_id' => (int) $request->id,
            'submit_path' => $filePath,
            'submit_comment' => $request->comment,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Update progress
        $this->getProgress();

        return back()->with('success', 'Upload berhasil!');
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
}
