<?php

namespace App\Http\Controllers\RestApi;

// use App\Models\PHP\Topic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use App\Models\RestApi\Submission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RestApiController extends Controller
{
    // Get all topics from database
    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);

        return view('restapi.index', [
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
        // $output = $request->query('output');

        // Get data from database
        $result = DB::table('restapi_topics')->where('id', $topic_id)->first();

        $pdf_reader = !empty($result->file_name) ? 1 : 0;

        $topics = Topic::all();
        $detail = Topic::findOrFail($topic_id);
        $topicsCount = count($topics);
        // $detailCount = ($topicsCount / $topicsCount) * 10;

        return view('restapi.topic_detail', [
            'row' => $detail,
            'user' => $user,
            'topic_id' => $topic_id,
            'topics' => $topics,
            'result' => $result,
            'pdf_reader' => $pdf_reader,
            'topicsCount' => $topicsCount,
            // 'output' => $output,
        ]);
    }

    // Submit task to database
    public function submit_task(Request $request)
    {
        // Input validation
        $request->validate([
            'file' => 'required|file|max:2048|extensions:php,html',
            'comment' => 'nullable|string',
            'id' => 'required|exists:restapi_topics,id'
        ]);

        // Save submit file to storage/app/public/uploads/
        $filePath = $request->file('file')->store('uploads', 'public');

        // Save submit data to database
        Submission::create([
            'user_id' => auth()->id(), // Get user ID
            'topic_id' => (int) $request->id,
            'submit_file_path' => $filePath,
            'submit_comment' => $request->comment,
            'created_at' => Carbon::now()
        ]);

        // Update progress
        $this->getProgress();

        return back()->with('success', 'Upload berhasil!');
    }

    public function getProgress()
    {
        // Get user ID
        $userId = auth()->id();

        // Count all topics in database
        $totalTopics = DB::table('restapi_topics')->count();

        // Count all submit by user and topic
        $submittedTopics = DB::table('restapi_student_submits')
                            ->where('user_id', $userId)
                            ->distinct()
                            ->count('topic_id');

        // Count progress presentation
        $progress = ($totalTopics > 0) ? round(($submittedTopics / $totalTopics) * 100) : 0;

        // Save progress to session
        session(['progress' => $progress]);

        return response()->json(['progress' => $progress]);
    }
}
