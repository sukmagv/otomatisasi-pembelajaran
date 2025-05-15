<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
use Barryvdh\DomPDF\PDF;
use App\Models\RestApi\Task;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    // Get all topics from database
    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);
        $tasks = Task::all();
        $tasksCount = count($tasks);
   
        return view('restapi.teacher.index', [
            'topics' => $topics,
            'topicsCount' => $topicsCount,
            'tasks' => $tasks,
            'tasksCount' => $tasksCount,
        ]);
    }

    // Add topic to database
    public function addTopic(Request $request)
    {
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        Topic::create($data);

        return back()->with('success', 'Topic added successfully!');
    }

    // Update topic in database
    public function updateTopic(Request $request)
    {
        $data = $request->validate([
            'id' => 'required',
            'title' => 'required',
            'description' => 'required',
        ]);

        $topic = Topic::findOrFail($data['id']);
        $topic->update($data);

        return back()->with('success', 'Topic updated successfully!');
    }

    // Delete topic from database
    public function deleteTopic(Request $request)
    {
        $data = $request->validate([
            'id' => 'required',
        ]);

        $topic = Topic::findOrFail($data['id']);
        $topic->delete();

        return back()->with('success', 'Topic deleted successfully!');
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

        // Get lastest submission data by user ID and task ID
        $submissions = Submission::where('task_id', $task_id)
            ->latest()
            ->with('user')
            ->get()
            ->unique('user_id');

        return view('restapi.teacher.topic_detail', [
            'row' => $result,
            'user' => $user,
            'topic_id' => $topic_id,
            'topics' => $topics,
            'tasks' => $tasks,
            'taskWithFile' => $taskWithFile,
            'pdf_reader' => $pdf_reader,
            'topicsCount' => $topicsCount,
            'submissions' => $submissions,
        ]);
    }

    // Add task to database
    public function addTask(Request $request)
    {
        // Validate: only 1 or 2 allowed
        $request->validate([
            'order_number' => 'in:1,2',
        ]);

        // Check if task with this topic_id and order_number already exists
        $exists = Task::where('topic_id', $request->topic_id)
                    ->where('order_number', $request->order_number)
                    ->exists();

        if ($exists) {
            return back()->withErrors(['order_number' => 'The selected order number is already used for this topic.']);
        }

        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/tasks', $fileName, 'public');
        }

        Task::create([
            'topic_id' => $request->topic_id,
            'title' => $request->title,
            'order_number' => $request->order_number,
            'flag' => $request->flag,
            'file_path' => 'storage/' . $filePath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Task added successfully!');
    }

    // Update task in database
    public function updateTask(Request $request)
    {
        $task = Task::findOrFail($request->id);

        $duplicate = Task::where('topic_id', $request->topic_id)
            ->where('order_number', $request->order_number)
            ->where('id', '!=', $task->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['order_number' => 'This order number is already used for the selected topic.']);
        }

        // Check if file_path is not empty
        if ($request->hasFile('file_path')) {
            // Delete old file if exists
            if ($task->file_path && Storage::exists(str_replace('storage/', '', $task->file_path))) {
                Storage::delete(str_replace('storage/', '', $task->file_path));
            }

            // Store new file
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/tasks', $fileName, 'public');

            // Set new file path
            $request['file_path'] = 'storage/' . $filePath;
        } else {
            // Use old file path if no new file is uploaded
            $request['file_path'] = $task->file_path;
        }

        // Update data
        $task->update([
            'topic_id' => $request->topic_id,
            'title' => $request->title,
            'order_number' => $request->order_number,
            'flag' => $request->flag,
            'updated_at' => Carbon::now(),
        ]);

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
        // Get user adn task ID
        $userId = $request->query('user_id');
        $taskId = $request->query('task_id');

        $submissions = Submission::with(['user', 'task.topic', 'feedback'])
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->whereNotNull('submit_path')
            ->latest()
            ->limit(1)
            ->get();
            
        // Array for storing file contents
        $filesContent = [];

        foreach ($submissions as $submission) {
            $filePath = "public/" . $submission->submit_path;

            // Check if file exists
            if (!Storage::exists($filePath)) {
                continue;
            }

            // Read file content
            $fileContent = Storage::get($filePath);
            
            // Escape HTML entities
            $escapedCode = htmlspecialchars_decode($fileContent);

            $filesContent[] = [
                'user_name'    => $submission->user->name ?? 'No Name',
                'user_email'   => $submission->user->email ?? 'No Email',
                'task_title'   => $submission->task->title ?? 'No Task',
                'topic_title'  => $submission->task->topic->title ?? 'No Topic',
                'topic_desc'   => $submission->task->topic->description ?? 'No Description',
                'submit_count' => $submission->submit_count ?? 0,
                'code'         => $escapedCode,
                'test_result'  => $submission->feedback->test_result ?? 'No Test Result',
                'updated_at'   => $submission->updated_at->format('Y-m-d H:i:s')
            ];
        }
        
        // Load view PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('restapi.teacher.pdf', compact('filesContent'));

        // Stream PDF
        return $pdf->stream('submission.pdf');
    }
}
