<?php

namespace App\Http\Controllers\RestApi;

// use App\Models\PHP\Topic;
use Illuminate\Http\Request;
use App\Models\RestApi\Topic;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RestApiController extends Controller
{
    // Menampilkan halaman indeks materi untuk mahasiswa.
    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);

        return view('restapi.index', [
            'topics' => $topics,
            'topicsCount' => $topicsCount,
        ]);
    }

    // Menampilkan detail materi tertentu sesuai dengan id
    public function restapi_topic_detail(Request $request)
    {
        // check if user is logged in
        $user = Auth::user();

        // check user role
        // $user_role = DB::table('users')->where('id', $user->id)->value('role');

        // Get ID from URL parameter, pastikan tipe integer
        $topic_id = (int) $request->query('id');

        // Get data from database, gunakan first() untuk satu baris data
        $result = DB::table('restapi_topics')->where('id', $topic_id)->first();

        $pdf_reader = !empty($result->file_name) ? 1 : 0;


        $topics = Topic::all();
        $detail = Topic::findOrFail($topic_id);
        $topicsCount = count($topics);
        // $detailCount = ($topicsCount / $topicsCount) * 10;

        return view('restapi.topic_detail', [
            'row' => $detail,
            'topic_id' => $topic_id,
            'result' => $result,
            'pdf_reader' => $pdf_reader,
            'topicsCount' => $topicsCount,
        ]);
    }
}
