<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
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

        // Ambil submission terakhir untuk user dan task tertentu
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
            // 'output' => $output,
        ]);
    }

    // Add task to database
    public function addTask(Request $request)
    {
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/tasks', $fileName, 'public');
        }
        
        $data = [
            'topic_id' => $request->topic_id,
            'title' => $request->title,
            'order_number' => $request->order_number,
            'flag' => $request->flag,
            'file_path' => $filePath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        Task::create($data);

        return back()->with('success', 'Task added successfully!');
    }

    // Update task in database
    public function updateTask(Request $request)
    {
        $task = Task::findOrFail($request->id);

        // Cek apakah ada file baru yang diunggah
        if ($request->hasFile('file_path')) {
            // Hapus file lama jika ada
            if ($task->file_path && Storage::exists(str_replace('storage/', '', $task->file_path))) {
                Storage::delete(str_replace('storage/', '', $task->file_path));
            }

            // Simpan file baru
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('restapi/tasks', $fileName, 'public');

            // Simpan path file baru ke database
            $request['file_path'] = 'storage/' . $filePath;
        } else {
            // Gunakan file lama jika tidak ada file baru
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
}
