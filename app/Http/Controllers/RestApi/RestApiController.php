<?php

namespace App\Http\Controllers\RestApi;

use Carbon\Carbon;
use Illuminate\Support\Str;
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
use Illuminate\Support\Facades\Response;

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
        $testResult = null;
        // $runOutput = null;
        $fileContent = null;

        if ($task_id && $activeTask) {
            // Ambil submission terakhir user untuk task tertentu
            $submission = Submission::where('user_id', $user->id)
                ->where('task_id', $task_id)
                ->latest()
                ->first();

            if ($submission) {
                // Ambil feedback terbaru untuk submission ini
                $feedback = Feedback::where('submission_id', $submission->id)
                    ->latest()
                    ->first();

                if ($feedback) {
                    // $runOutput = $feedback->run_output ? json_decode($feedback->run_output, true) : null;
                    $testResult = $feedback->test_result;
                }

                // Ambil isi file jika path ada dan file tersedia
                if ($submission->submit_path) {
                    $fullPath = public_path("storage/" . $submission->submit_path);
                    if (file_exists($fullPath)) {
                        $fileContent = file_get_contents($fullPath);
                    }
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
            // 'runOutput' => $runOutput ?? null,
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
        // Validasi input
        $request->validate([
            'file' => 'required|file|max:2048|extensions:php,html',
            'comment' => 'nullable|string',
            'task_id' => 'required|exists:restapi_topic_tasks,id',
        ]);

        DB::beginTransaction();

        try {
            $user = auth()->user();
            $username = $user->name;
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = "restapi/{$username}/{$fileName}";

            // Cari submission lama jika ada
            $existingSubmission = Submission::where('user_id', $user->id)
                ->where('task_id', (int)$request->task_id)
                ->first();

            // Hapus file lama jika ada
            if ($existingSubmission && $existingSubmission->submit_path) {
                Storage::disk('public')->delete($existingSubmission->submit_path);
            }

            // Simpan file baru ke storage
            Storage::disk('public')->put($filePath, file_get_contents($file->getRealPath()));

            // Simpan atau update submission
            Submission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'task_id' => (int)$request->task_id,
                ],
                [
                    'submit_path' => $filePath,
                    'submit_comment' => $request->comment,
                    'updated_at' => now(),
                    'created_at' => $existingSubmission ? $existingSubmission->created_at : now(), // preserve created_at if update
                ]
            );

            DB::commit();

            // Optional: generate response test result jika ada

             return response()->json([
                'success' => true,
                'message' => 'Upload berhasil!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan submission.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Test files
    private $testFiles = [
        2 => 'DbConnectTest',
        3 => 'PostCest',
        4 => 'GetCest',
        5 => 'PutCest',
        6 => 'DeleteCest',
        7 => 'FormCest',
    ];

    public function runCodeceptionTest(Request $request)
    {
        $userId = auth()->id();
        $taskId = $request->input('task_id');

        $submission = Submission::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->latest()
            ->firstOrFail();

        // $runOutput = $this->runFile($userId, $taskId);

        $submissionPath = public_path("storage/" . $submission->submit_path);
        $topicId = Task::where('id', $taskId)->value('topic_id');

        $testBase = $this->testFiles[$topicId] ?? null;

        if (!$testBase) {
            $errorMessage = "Tidak ada test yang cocok untuk Topic ID: $topicId";
            Session::put('test_result', $errorMessage);
            return ['output' => $errorMessage, 'fileContents' => null];
        }

        switch ($topicId) {
            case 2:
                $testFolder = 'Unit';
                break;
            case 7:
                $testFolder = 'Functional';
                break;
            default:
                $testFolder = 'Api';
        }

        $codeceptionFile = "tests/{$testFolder}/{$testBase}.php";

        // Simpan path file untuk digunakan oleh test
        $relativePath = Str::after($submissionPath, public_path() . DIRECTORY_SEPARATOR);

        File::put(base_path('tests/test-config.json'), json_encode([
            'testFile' => str_replace('/', DIRECTORY_SEPARATOR, $relativePath),
        ]));

        $process = new Process([
            'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe',
            'vendor/bin/codecept',
            'run',
            $testFolder,
            $codeceptionFile,
        ], base_path());

        $process->run();

        $cleanOutput = preg_replace('/\e\[([;\d]+)?m/', '', 
            $process->getOutput() . "\n" . $process->getErrorOutput()
        );

        Feedback::create([
            'submission_id' => $submission->id,
            'test_result' => $cleanOutput,
            // 'run_output' => json_encode($runOutput),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $filteredResult = $this->filterTestResult($cleanOutput);
        Session::flash('test_result', $filteredResult);

        return response()->json([
            'status' => 'success',
            'testResult' => $filteredResult,
            // 'runOutput' => $runOutput,
        ]);
    }

    public function runIndex($username)
    {
        $phpPath = public_path("storage/restapi/{$username}/index.php");
        $htmlPath = public_path("storage/restapi/{$username}/index.html");

        if (File::exists($phpPath)) {
            ob_start();
            include($phpPath);
            $renderedContent = ob_get_clean();
        } elseif (File::exists($htmlPath)) {
            $renderedContent = File::get($htmlPath);
        } else {
            abort(404, 'File not found.');
        }

        return view('restapi.runtest', [
            'username' => $username,
            'content' => $renderedContent,
        ]);
    }

    public function runTest($username, $filename)
    {
        $baseDir = public_path("storage/restapi/{$username}");
        $file = $baseDir . DIRECTORY_SEPARATOR . $filename;

        // Amankan agar tidak bisa akses di luar folder ini
        $realBase = realpath($baseDir);
        $realFile = realpath($file);

        if (!$realFile || strpos($realFile, $realBase) !== 0 || !file_exists($realFile)) {
            abort(404, 'File not found or access denied');
        }

        ob_start();
        include $realFile;
        return response(ob_get_clean());
    }

    protected function filterTestResult(string $output): array
    {
        $result = [];

        if (preg_match('/Codeception PHP Testing Framework v([\d\.]+)/', $output, $matchVersion)) {
            $result['version'] = $matchVersion[1];
        }

        if (preg_match('/Time:\s+([0-9:.]+),\s+Memory:/', $output, $matchTime)) {
            $result['duration'] = $matchTime[1];
        }
        if (preg_match('/Memory:\s+([\d\.]+\s?[KMG]B)/', $output, $matchMemory)) {
            $result['memory'] = $matchMemory[1];
        }

        // Ambil nomor line error berdasarkan banyak keyword
        $keywords = ['db.php', 'index.html', 'index.php', 'post.php', 'get.php', 'put.php', 'delete.php'];
        $escapedKeywords = array_map(function($kw) {
            return preg_quote($kw, '/');
        }, $keywords);

        // Deteksi error dari trace (#1 ... post.php:8)
        $pattern = '/^#\d+\s+.*(' . implode('|', $escapedKeywords) . '):(\d+)/mi';
        preg_match_all($pattern, $output, $matches, PREG_SET_ORDER);
        $errorLines = [];

        foreach ($matches as $match) {
            $errorLines[] = (int)$match[2];
        }

        // Deteksi juga "on line X" dari pesan error langsung (misalnya Parse error di HTML)
        if (preg_match_all('/on line <b>(\d+)<\/b>/i', $output, $htmlLineMatches)) {
            foreach ($htmlLineMatches[1] as $lineNum) {
                $errorLines[] = (int)$lineNum;
            }
        }

        // Hapus duplikat dan simpan jika ada
        if (!empty($errorLines)) {
            $result['error_lines'] = array_unique($errorLines);
        }

        // Terjemahan error umum
        $errorTranslations = [
            'Access denied for user' => 'Autentikasi gagal. Periksa username dan password database',
            'Unknown database' => 'Nama database tidak ditemukan. Pastikan database telah dibuat',
            'Connection refused' => 'Tidak dapat terhubung ke server MySQL. Periksa apakah server aktif dan port benar',
            'Class not found' => 'Kesalahan penulisan class (mungkin typo atau class tidak di-include)',
            'syntax error' => 'Kesalahan sintaks. Periksa penulisan kode',
            'Undefined variable' => 'Variabel belum didefinisikan. Periksa deklarasi variabel'
        ];

        $translatedErrors = [];

        $lines = explode("\n", $output);

        $captureAssertion = false; // Flag untuk mulai parsing Expected & Actual
        $expected = null;
        $actual = null;

        foreach ($lines as $i => $line) {
            if (preg_match("/File yang diuji bukan\s+'(.+?)',\s+tetapi\s+'(.+?)'/i", $line, $match)) {
                $expectedFile = $match[1];
                $actualFile = $match[2];

                // Cari saran file paling mirip
                $suggested = null;
                $minDistance = PHP_INT_MAX;
                foreach ($keywords as $keyword) {
                    $distance = levenshtein($actualFile, $keyword);
                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $suggested = $keyword;
                    }
                }

                $translatedErrors[] = "Nama file yang diuji salah: seharusnya '$expectedFile', tetapi ditemukan '$actualFile'. ";
                continue; // lanjut ke baris berikutnya, jangan diproses ulang
            }

            // Tangkap error HTTP status code mismatch
            if (preg_match('/Expected HTTP Status Code:\s+(\d+)[^\d]+Actual Status Code:\s+(\d+)/i', $line, $match)) {
                $expected = $match[1];
                $actual = $match[2];
                $translatedErrors[] = "Kode status HTTP tidak sesuai. Diharapkan: $expected, tetapi server memberikan: $actual. "
                    . "Periksa apakah input data valid, dan logika kondisi sukses (misal: 201 Created) telah terpenuhi dalam kode PHP.";
                continue;
            }

            // Step Fail lebih prioritas
            if (preg_match('/Step\s+Fail\s+"(.*?)"/', $line, $matches)) {
                $result['step_fail'] = "Langkah gagal: " . $matches[1];
            }

            // Tangkap error dari "see" gagal menemukan teks di selector tertentu
            if (preg_match('/Step\s+See\s+"(.+?)","(.+?)"/i', $line, $match)) {
                $teksDicari = $match[1];
                $selector = $match[2];

                // Cek baris setelahnya apakah mengandung pesan kegagalan
                if (isset($lines[$i + 1]) && strpos($lines[$i + 1], "Failed asserting that any element by") !== false) {
                    // Ambil semua elemen tag <selector> yang muncul setelahnya
                    $elemenTersedia = [];
                    for ($j = $i + 2; $j < count($lines); $j++) {
                        $safeSelector = preg_quote($selector, '/');
                        if (preg_match('/<(' . $safeSelector . ')[^>]*>/i', $lines[$j], $m)) {
                            $elemenTersedia[] = trim($m[1]);
                        }
                    }

                    $elemenStr = count($elemenTersedia) > 0
                        ? implode(', ', $elemenTersedia)
                        : 'Tidak ada elemen <' . $selector . '> yang ditemukan di halaman.';

                    $translatedErrors[] = "Elemen <$selector> dengan teks \"$teksDicari\" tidak ditemukan. Elemen yang tersedia: $elemenStr";
                }
            }

            // === Case 2: Step seeElement("form[action=...]") gagal ===
            elseif (preg_match('/Step\s+See element\s+"(.+?)"/i', $line, $match)) {
                $selector = $match[1];

                if (isset($lines[$i + 1]) && strpos($lines[$i + 1], "was not found") !== false) {
                    // Coba kumpulkan form yang tersedia
                    $availableForms = [];
                    for ($j = $i + 2; $j < count($lines); $j++) {
                        if (preg_match('/<form[^>]*action="([^"]+)"[^>]*>/i', $lines[$j], $m)) {
                            $availableForms[] = $m[1];
                        }
                    }

                    $formStr = count($availableForms) > 0
                        ? "Form yang tersedia di halaman: " . implode(', ', $availableForms)
                        : "Tidak ada elemen <form> dengan atribut `action` ditemukan.";

                    $translatedErrors[] = "Elemen dengan selector `$selector` tidak ditemukan di halaman. $formStr";
                }
            }

            // === Case 3: Metode HTTP tidak didukung (POS method error) ===
            elseif (preg_match('/The (\w+) method is not supported.*Supported methods:\s*(.+?)\./i', $line, $match)) {
                $method = strtoupper($match[1]); // POS
                $supported = strtoupper($match[2]); // GET, POST, etc

                $translatedErrors[] = "Metode \"$method\" tidak didukung. Metode yang didukung: $supported.";
            }

            // ✅ Tambahkan di sini: Tangani warning seperti Undefined variable
            if (preg_match('/<b>Warning<\/b>:\s+(.*?) in <b>(.*?)<\/b> on line <b>(\d+)<\/b>/', $line, $match)) {
                $pesan = htmlspecialchars_decode($match[1], ENT_QUOTES);
                $file = $match[2];
                $baris = $match[3];
                $translatedErrors[] = "Peringatan: $pesan pada baris $baris. Kemungkinan variabel belum didefinisikan.";
            }

            // Tangani error HTML
            if (preg_match('/<b>Parse error<\/b>:\s+(.+?) in <b>.+?<\/b> on line <b>(\d+)<\/b>/i', $line, $match)) {
                $pesan = htmlspecialchars_decode($match[1], ENT_QUOTES); // decode &quot;, &lt; dst
                $baris = $match[2];
                $translatedErrors[] = "Kesalahan sintaks: $pesan pada baris $baris. Periksa kembali struktur kode.";
                continue; // skip agar tidak diproses ulang oleh bagian lain
            }

            // Deteksi error keyword teknis
            if (preg_match('/(Parse error|Fatal error|on line \d+|Exception|Error|Gagal mendapatkan koneksi database)/i', $line)) {
                if (strpos($line, 'Invalid json') !== false && preg_match('/System message: (.+)/', $line, $match)) {
                    $translatedErrors[] = "Respons tidak valid: JSON rusak karena ada output lain (seperti warning atau HTML)."
                        . " Pesan sistem: {$match[1]}. Pastikan file tidak mencetak HTML sebelum JSON.";
                }

                foreach ($errorTranslations as $keyword => $explanation) {
                    if ($keyword === 'Class not found') {
                        if (preg_match('/Class\s+"[^"]+"\s+not\s+found/i', $line)) {
                            $translatedErrors[] = "$explanation. $line";
                        }
                    } else {
                        if (strpos($line, $keyword) !== false) {
                            $translatedErrors[] = "$explanation. $line";
                        }
                    }
                }

                // Tangkap error khusus "Gagal mendapatkan koneksi database"
                if (preg_match('/Gagal mendapatkan koneksi database: (.+?) tidak ditemukan pada: (.+)/', $line, $match)) {
                    $fileMissing = $match[1];
                    $path = $match[2];
                    $translatedErrors[] = "File yang dibutuhkan tidak ditemukan: '$fileMissing' \n"
                        . "Pastikan file tersebut tersedia dan namanya benar.";
                }

                if (preg_match('/Fatal error.*mysqli_sql_exception: Table \'([^\']+)\' doesn\'t exist/', $line, $match)) {
                    $namaTabel = $match[1];
                    $translatedErrors[] = "Tabel database tidak ditemukan: '$namaTabel'. Periksa apakah tabel tersebut sudah dibuat dalam database dan penulisan nama tabel sudah benar.";
                }

                // Tangkap error khusus "Gagal mendapatkan koneksi database"
                if (preg_match('/Gagal mendapatkan koneksi database: (.+?) tidak ditemukan pada: (.+)/', $line, $match)) {
                    $fileMissing = $match[1];
                    $path = $match[2];
                    $translatedErrors[] = "File yang dibutuhkan tidak ditemukan: '$fileMissing' \n"
                        . "Pastikan file tersebut tersedia dan namanya benar.";
                }

                continue; // skip agar tidak diproses ulang oleh bagian lain
            }

            // Deteksi error asserting dua string identik mulai
            if (stripos($line, 'Failed asserting that two strings are identical') !== false) {
                $captureAssertion = true; // Mulai parsing baris Expected & Actual selanjutnya
                continue;
            }

            // Parsing Expected & Actual string saat flag capture aktif
            if ($captureAssertion) {
                if (preg_match("/^-\'(.+?)\'$/", trim($line), $m)) {
                    $expected = $m[1];
                } elseif (preg_match("/^\+\'(.+?)\'$/", trim($line), $m)) {
                    $actual = $m[1];
                }

                if ($expected !== null && $actual !== null) {
                    $translatedErrors[] = "Kesalahan penulisan string. Expected: $expected. Actual: $actual";
                    // Reset flag dan nilai
                    $captureAssertion = false;
                    $expected = null;
                    $actual = null;
                }
            }

            if (preg_match('/^Fail\s+(.*)/', $line, $matches)) {
                $result['fail'] = "Gagal: " . trim($matches[1]);
            }

            if (preg_match('/require\((.*?)\): Failed to open stream: No such file or directory/i', $line, $match)) {
                $file = $match[1];
                $translatedErrors[] = "File `$file` tidak ditemukan. Pastikan file tersebut ada dan namanya benar.";
            }

            if (preg_match('/Fatal error:\s+Uncaught Error: Failed opening required \'(.+?)\'/', $line, $match)) {
                $file = $match[1];
                $translatedErrors[] = "Gagal membuka file `$file`. Periksa apakah file tersebut tersedia dan dapat diakses.";
            }

            if (preg_match('/on line (\d+)/', $line, $match)) {
                $translatedErrors[] = "Kesalahan terjadi pada baris ke-" . $match[1] . ". Periksa baris tersebut di file yang disebutkan.";
            }

            // Deteksi error JSON mismatch
            if (stripos($line, 'Fail  Response JSON does not contain the provided JSON') !== false) {
                $translatedErrors[] = "Respons JSON tidak mengandung struktur yang diharapkan. "
                    . "Pastikan response memiliki key 'status' dan 'message'.";
                continue;
            }

            if (preg_match('/- *\'status\' *=> *\'([^\']+)\'/', $line, $match)) {
                $expectedStatus = $match[1];
                $translatedErrors[] = "Status yang diharapkan: '$expectedStatus' tidak ditemukan dalam response JSON.";
            }
            if (preg_match('/- *\'message\' *=> *\'([^\']+)\'/', $line, $match)) {
                $expectedMessage = $match[1];
                $translatedErrors[] = "Pesan yang diharapkan: '$expectedMessage' tidak ada dalam response JSON.";
            }
            if (preg_match('/\+ *\'data\' *=>/', $line)) {
                $translatedErrors[] = "Response hanya berisi 'data'. Periksa apakah response juga menyertakan 'status' dan 'message'.";
            }

            if (preg_match('/Step\s+See response is json\s+Fail\s+response is empty/i', $output)) {
                $translatedErrors[] = "Respons kosong dari server saat diharapkan JSON. Pastikan endpoint API mengirim output JSON valid dan tidak kosong.";
            }
        }

        // --- Tambahan baru: Capture semua judul test (class & title) ---
        $testTitles = [];
        foreach ($lines as $line) {
            if (preg_match('/^\d+\)\s+([\w\\\\]+):\s+(.+)$/', $line, $matches)) {
                $testTitles[] = [
                    'class' => $matches[1],
                    'title' => $matches[2],
                ];
            }
        }
        if (!empty($testTitles)) {
            $result['test_titles'] = $testTitles;
        }

        // Jika ada test_titles dan translatedErrors, susun message dalam format array untuk Blade
        if (!empty($testTitles) && !empty($translatedErrors) && !isset($result['message'])) {
            $structuredMessages = [];
            $errorChunks = array_chunk($translatedErrors, 1); // 1 error per test
            foreach ($testTitles as $index => $title) {
                $structuredMessages[] = [
                    'title' => $title['title'] ?? 'Judul tidak tersedia',
                    'errors' => $errorChunks[$index] ?? ["Test sukses"],
                ];
            }
            $result['message'] = $structuredMessages;
        }

        // Cegah penimpaan message array jika sudah disusun sebelumnya
        if (empty($result['message']) && empty($translatedErrors) && empty($result['duration']) && empty($result['memory']) && empty($result['error_lines'])) {
            $result = [];
        } elseif (!empty($translatedErrors) && (!isset($result['message']) || !is_array($result['message']))) {
            $result['message'] = implode("\n", $translatedErrors);
        } elseif (preg_match('/^OK\s+\(\d+\stests?,\s+\d+\sassertions\)/m', $output)) {
            $result['message'] = "Semua pengujian berhasil.";
        } elseif (empty($result['message'])) {
            $result['message'] = "Pengujian gagal, tetapi tidak ditemukan detail error yang jelas.";
        }

        // dd($result);
        return $result;
    }
}    