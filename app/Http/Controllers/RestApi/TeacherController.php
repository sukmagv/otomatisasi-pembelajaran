<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
use Barryvdh\DomPDF\PDF;
use App\Models\RestApi\Task;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Feedback;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    // Get all topics from database
    // public function topics()
    // {
    //     $topics = Topic::all();
    //     $topicsCount = count($topics);

    //     return view('restapi.teacher.topic', [
    //         'topics' => $topics,
    //         'topicsCount' => $topicsCount,
    //     ]);
    // }

    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);
        
        // Urutkan task berdasarkan topic_id
        $tasks = Task::orderBy('topic_id')
            ->with('topic')
            ->get();
            
        $tasksCount = count($tasks);

        return view('restapi.teacher.index', [
            'topics' => $topics,
            'topicsCount' => $topicsCount,
            'tasks' => $tasks,
            'tasksCount' => $tasksCount,
        ]);
    }

    // Get topic detail from database
    public function openTask(Request $request)
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

        $activeTask = $tasks[$topic_id]->firstWhere('id', $task_id) ?? null;

        // Get submission data by user ID and task ID
        $submissions = Submission::where('task_id', $task_id)
            ->with('user')
            ->get();

        return view('restapi.teacher.topic_detail', [
            'row' => $result,
            'user' => $user,
            'topic_id' => $topic_id,
            'topics' => $topics,
            'tasks' => $tasks,
            'taskWithFile' => $taskWithFile,
            'pdf_reader' => $pdf_reader,
            'activeTask' => $activeTask,
            'topicsCount' => $topicsCount,
            'submissions' => $submissions,
        ]);
    }

    // Add task to database
    public function addTask(Request $request)
    {
        // Check if task with this topic_id already exists
        $exists = Task::where('topic_id', $request->topic_id)
                    ->exists();

        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/tasks', $fileName, 'public');
        }

        Task::create([
            'topic_id' => $request->topic_id,
            'title' => $request->title,
            'flag' => $request->flag,
            'file_path' => $filePath ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Task added successfully!');
    }

    // Update task in database
    public function updateTask(Request $request)
    {
        // Cari task berdasarkan topic_id
        $task = Task::where('topic_id', $request->topic_id)->first();

        if (!$task) {
            return back()->with('error', 'Task not found!');
        }

        // Jika ada file baru diunggah
        if ($request->hasFile('file_path')) {
            $newFile = $request->file('file_path');
            $newFileName = time() . '_' . $newFile->getClientOriginalName();
            $newFilePath = $newFile->storeAs('restapi/tasks', $newFileName, 'public');

            // Hapus file lama jika ada
            if ($task->file_path && Storage::disk('public')->exists($task->file_path)) {
                Storage::disk('public')->delete($task->file_path);
            }

            $task->file_path = $newFilePath;
        }

        // Update field lainnya
        $task->title = $request->title;
        $task->flag = $request->flag;
        $task->updated_at = Carbon::now();
        $task->save();

        return back()->with('success', 'Task updated successfully!');
    }

    // Delete task from database
    public function deleteTask(Request $request)
    {
        $data = $request->validate([
            'id' => 'required',
        ]);

        $task = Task::findOrFail($data['id']);
        $task->delete();

        return back()->with('success', 'Task deleted successfully!');
    }

    // Export PDF
    public function exportPDF(Request $request)
    {
        $userId = $request->query('user_id');
        $taskId = $request->query('task_id');
        $submissionId = $request->query('submission_id');

        // Ambil submission spesifik
        $submission = Submission::with(['user', 'task.topic', 'feedbacks'])
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->where('id', $submissionId)
            ->whereNotNull('submit_path')
            ->firstOrFail();

        $filePath = "public/" . $submission->submit_path;

        if (!Storage::exists($filePath)) {
            abort(404, 'File not found.');
        }

        $fileContent = Storage::get($filePath);
        $escapedCode = htmlspecialchars_decode($fileContent);

        // Hitung interval terhadap submission sebelumnya
        $intervalData = $this->getSubmitIntervalForSubmission($submission);

        $stats = $this->getSubmissionStats($submissionId, $userId, $taskId);
        $submissionPosition = $stats['position'];
        $latestFeedback = $submission->feedbacks->sortByDesc('created_at')->first();
        $testResult = $latestFeedback->test_result ?? 'No Test Result';

        $filesContent = [[
            'user_name'    => $submission->user->name ?? 'No Name',
            'user_email'   => $submission->user->email ?? 'No Email',
            'task_title'   => $submission->task->title ?? 'No Task',
            'topic_title'  => $submission->task->topic->title ?? 'No Topic',
            'topic_desc'   => $submission->task->topic->description ?? 'No Description',
            'code'         => $escapedCode,
            'test_result'  => $testResult ?? 'No Test Result',
            'interval'     => $intervalData,
            'submission_position' => $submissionPosition,
            'created_at'   => $submission->created_at->format('Y-m-d H:i:s'),
        ]];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('restapi.teacher.pdf', compact('filesContent'));

        return $pdf->stream('submission.pdf');
    }

    public function getSubmitIntervalForSubmission(Submission $currentSubmission)
    {
        // Ambil feedback terkait current submission, ambil waktu created_at feedback
        $currentFeedback = $currentSubmission->feedbacks()->latest()->first();

        if (!$currentFeedback) {
            // Jika current submission belum punya feedback, return null
            return null;
        }

        // Cari feedback sebelumnya untuk user dan task yang sama, dengan waktu feedback sebelum current feedback
        $previousFeedback = Feedback::whereHas('submission', function ($q) use ($currentSubmission) {
                $q->where('user_id', $currentSubmission->user_id)
                ->where('task_id', $currentSubmission->task_id);
            })
            ->where('created_at', '<', $currentFeedback->created_at)
            ->orderByDesc('created_at')
            ->first();

        if (!$previousFeedback) {
            return null;
        }

        // Hitung interval antara previous feedback dan current feedback
        $intervalReadable = $previousFeedback->created_at->diffForHumans($currentFeedback->created_at, [
            'parts' => 3,
            'short' => true,
        ]);

        $intervalInSeconds = $previousFeedback->created_at->diffInSeconds($currentFeedback->created_at);

        return [
            'from' => $previousFeedback->created_at->toDateTimeString(),
            'to' => $currentFeedback->created_at->toDateTimeString(),
            'interval_readable' => $intervalReadable,
            'interval_seconds' => $intervalInSeconds,
        ];
    }

    protected function getSubmissionStats($submissionId, $userId, $taskId)
    {
        // Ambil semua feedback untuk submission tertentu, diurutkan berdasarkan created_at
        $feedbacks = Feedback::where('submission_id', $submissionId)
            ->orderBy('created_at')
            ->get();

        $totalFeedback = $feedbacks->count();

        if ($totalFeedback == 0) {
            return [
                'position' => null,
            ];
        }

        // Ambil feedback terbaru (misal feedback terakhir) dan cari posisinya
        $latestFeedback = $feedbacks->last();

        $position = $feedbacks->search(function ($feedback) use ($latestFeedback) {
            return $feedback->id === $latestFeedback->id;
        });

        return [
            'position' => $position !== false ? $position + 1 : null,
        ];
    }
}
