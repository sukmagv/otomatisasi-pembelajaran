<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
use App\Models\RestApi\Task;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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

    // Add task to database
    public function addTask(Request $request)
    {
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('storage/restapi/tasks', $fileName, 'public');
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
            $filePath = $file->storeAs('storage/restapi/tasks', $fileName, 'public');

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
