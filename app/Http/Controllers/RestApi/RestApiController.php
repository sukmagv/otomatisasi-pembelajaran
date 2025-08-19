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
use App\Helpers\PHPUnitUploadHelper;

class RestApiController extends Controller
{
    // Get all topics from database
    public function index()
    {
        $topics = Topic::all();
        $topicsCount = count($topics);
        $topicFinished = Task::where('flag', 1)
            ->whereHas('submissions', function ($query) {
                $query->where('user_id', Auth::id());
            })->get();

        return view('restapi.student.index', [
            'topics' => $topics,
            'topicsCount' => $topicsCount,
            'topicFinished' => $topicFinished,
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

        $decodedOutput = json_decode($testResult, true);

        $codeceptionOutput = $this->filterTestResult($decodedOutput['codeception'] ?? '');
        $phpunitOutput = $this->filterTestResult($decodedOutput['phpunit'] ?? '');

        // Fallback jika codeception kosong/null
        if (empty($codeceptionOutput) && !empty($phpunitOutput)) {
            $finalTestResult = $phpunitOutput;
        } else {
            $finalTestResult = $codeceptionOutput;
        }

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
            'testResult' => $finalTestResult,
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

            // Ambil task terkait untuk akses topic_id
            $task = Task::findOrFail((int)$request->task_id);
            $topicId = $task->topic_id;

            // Ambil submission lama jika ada
            $existingSubmission = Submission::where('user_id', $user->id)
                ->where('task_id', $task->id)
                ->first();

            // Hapus file lama jika ada
            if ($existingSubmission && $existingSubmission->submit_path) {
                Storage::disk('public')->delete($existingSubmission->submit_path);
            }
            
            $file->storeAs("restapi/{$username}", $fileName, 'public');

            // Ambil waktu mulai dari session atau default now()
            $sessionKey = "start_time_topic_{$topicId}_task_{$task->id}";
            $startedAt = $existingSubmission
                ? $existingSubmission->started_at
                : session($sessionKey) ?? now();

            // Simpan atau update submission
            Submission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                ],
                [
                    'submit_path' => $filePath,
                    'submit_comment' => $request->comment,
                    'updated_at' => now(),
                    'created_at' => $existingSubmission ? $existingSubmission->created_at : now(),
                    'started_at' => $startedAt,
                ]
            );

            DB::commit();

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
        // 2 => 'DbConnectTest',
        3 => 'PostCest',
        4 => 'GetCest',
        5 => 'PutCest',
        6 => 'DeleteCest',
        7 => 'FormCest',
    ];

    public function runCodeceptionTest(Request $request)
    {
        $userId = auth()->id();
        $username = Auth::user()->name;
        $taskId = $request->input('task_id');

        $submission = Submission::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->latest()
            ->firstOrFail();

        $submissionPath = public_path($submission->submit_path);
        $topicId = Task::where('id', $taskId)->value('topic_id');

        // Simpan path file untuk digunakan oleh PHPUnit & Codeception
        $relativePath = Str::after($submissionPath, public_path() . DIRECTORY_SEPARATOR);
        File::put(base_path('tests/test-config.json'), json_encode([
            'testFile' => str_replace('/', DIRECTORY_SEPARATOR, $relativePath),
            'username' => $username,
        ]));

        /**
         * 🔍 Jalankan PHPUnit terlebih dahulu
         */
        $phpunit = null;
        $codeception = null;
        $uploadedFile = basename($submissionPath);

        $phpunitTestMap = [
            'db.php' => 'DbConnectionTest',
            'post.php' => 'UserCreateTest',
            'get.php' => 'UserGetTest',
            'put.php' => 'UserUpdateTest',
            'delete.php' => 'UserDeleteTest',
        ];

        $phpunitClass = $phpunitTestMap[$uploadedFile] ?? null;

        if ($phpunitClass) {
            try {
                // Siapkan file refactor
                PHPUnitUploadHelper::prepareTestableFile($submissionPath);

                // Jalankan PHPUnit
                $phpunit = new Process([
                    'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe',
                    '-d', "auto_prepend_file=" . base_path('tests/prepend/db_override.php'),
                    'vendor/bin/phpunit',
                    '--testdox',
                    '--filter', $phpunitClass,
                    "tests/PHPUnitUpload/{$phpunitClass}.php",
                ], base_path());

                $phpunit->run();

                $phpunitOutput = preg_replace('/\e\[([;\d]+)?m/', '', 
                    $phpunit->getOutput() . "\n" . $phpunit->getErrorOutput()
                );
            } catch (\Exception $e) {
                $phpunitOutput = "Gagal menjalankan test: " . $e->getMessage();
            } finally {
                // 🧹 Hapus file refactor
                // PHPUnitUploadHelper::cleanupTestableFile($uploadedFile);
            }
        } else {
            $phpunitOutput = "Tidak ada test PHPUnit yang cocok untuk file: $uploadedFile";
        }
        
        $runCodeception = !in_array($uploadedFile, ['db.php']) && $topicId !== 2;

        $testBase = $this->testFiles[$topicId] ?? null;

        if (!$testBase && $topicId !== 2) {
            $errorMessage = "Tidak ada test yang cocok untuk Topic ID: $topicId";
            Session::put('test_result', $errorMessage);
            return response()->json([
                'rawOutput' => $errorMessage,
                'fileContents' => null,
                'status' => 'failed',
                'testResult' => $errorMessage
            ]);
        }

        switch ($topicId) {
            // case 2:
            //     $testFolder = 'Unit';
            //     break;
            case 7:
                $testFolder = 'Functional';
                break;
            default:
                $testFolder = 'Api';
        }

        $codeceptionFile = "tests/{$testFolder}/{$testBase}.php";

        $codeceptionOutput = '';
        if ($runCodeception) {
            $codeception = new Process([
                'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe',
                '-d', "auto_prepend_file=" . base_path('tests/prepend/db_override_server.php'),
                'vendor/bin/codecept',
                'run',
                $testFolder,
                $codeceptionFile,
            ], base_path());

            $codeception->run();

            $codeceptionOutput = preg_replace('/\e\[([;\d]+)?m/', '', 
                $codeception->getOutput() . "\n" . $codeception->getErrorOutput()
            );
        }

        // Gabungkan hasil keduanya
        // $combinedOutput = "[PHPUNIT OUTPUT]\n" . $phpunitOutput . "\n\n[CODECEPTION OUTPUT]\n" . $codeceptionOutput;

        // Serialisasi jadi JSON
        $serializedOutput = json_encode([
            'phpunit' => $phpunitOutput,
            'codeception' => $codeceptionOutput,
        ]);

        // Simpan dalam 1 Feedback
        Feedback::create([
            'submission_id' => $submission->id,
            'test_result' => $serializedOutput,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Buat flash dan response
        Session::flash('test_result', "[PHPUNIT OUTPUT]\n" . $phpunitOutput . "\n\n[CODECEPTION OUTPUT]\n" . $codeceptionOutput);

        $filesContents = file_exists($submissionPath) ? file_get_contents($submissionPath) : null;

        $status = 'failed'; // default

        if ($uploadedFile === 'db.php') {
            $status = ($phpunit && $phpunit->isSuccessful()) ? 'success' : 'failed';
        } elseif (in_array($uploadedFile, ['index.php', 'index.html'])) {
            $status = ($codeception && $codeception->isSuccessful()) ? 'success' : 'failed';
        } else {
            if ($phpunit && $codeception) {
                $status = ($phpunit->isSuccessful() && $codeception->isSuccessful()) ? 'success' : 'partial';
            } elseif ($phpunit) {
                $status = $phpunit->isSuccessful() ? 'partial' : 'failed';
            } elseif ($codeception) {
                $status = $codeception->isSuccessful() ? 'partial' : 'failed';
            }
        }
        
        $decodedOutput = json_decode($serializedOutput, true);
        return response()->json([
            'rawOutput' => $decodedOutput, // terpisah per bagian
            'fileContents' => $filesContents,
            'status' => $status,
            'testResult' => [
                'phpunit' => $this->filterTestResult($decodedOutput['phpunit'] ?? ''),
                'codeception' => $this->filterTestResult($decodedOutput['codeception'] ?? ''),
            ],
        ]);
    }

    public function runIndex($username)
    {
        $phpPath = public_path("storage/restapi/{$username}/index.php");
        $htmlPath = public_path("storage/restapi/{$username}/index.html");

        // Validasi: Kalau dua-duanya tidak ada
        if (!File::exists($phpPath) && !File::exists($htmlPath)) {
            return redirect()->back()->with('alert', 'Belum upload file Form Manajemen Data.');
        }

        // Render jika file ada
        if (File::exists($phpPath)) {
            ob_start();
            include($phpPath);
            $renderedContent = ob_get_clean();
        } else {
            $renderedContent = File::get($htmlPath);
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

        // if (preg_match('/Codeception PHP Testing Framework v([\d\.]+)/', $output, $matchVersion)) {
        //     $result['version'] = $matchVersion[1];
        // }
        // 1. Versi Codeception
        if (preg_match('/Codeception PHP Testing Framework v([\d\.]+)/i', $output, $matchCodeception)) {
            $result['tool'] = 'Codeception';
            $result['version'] = $matchCodeception[1];
        }

        // 2. Versi PHPUnit
        elseif (preg_match('/PHPUnit\s+([\d\.]+)\s+by Sebastian Bergmann/', $output, $matchPHPUnit)) {
            $result['tool'] = 'PHPUnit';
            $result['version'] = $matchPHPUnit[1];
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
            'File yang diuji harus bernama db.php' => 'Check if the db.php file is correctly named',
            'No such host is known' => 'Database host not found. Check the hostname and connection',
            'Access denied for user' => 'Authentication failed. Check the database username and password',
            'Unknown database' => 'Database not found. Make sure the database has been created',
            'Connection refused' => 'Cannot connect to the MySQL server. Check if the server is running and the port is correct',
            'Class not found' => 'Class name error (possibly a typo or the class was not included)',
            'syntax error' => 'Syntax error. Check the code formatting and syntax',
            'Undefined variable' => 'Variable not defined. Check if the variable was declared properly'
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

                $translatedErrors[] = "The tested file name is incorrect: it should be '$expectedFile', but was found to be '$actualFile'. ";
                continue;
            } else if (preg_match('/Gagal mendapatkan koneksi database: File (.+?) tidak ditemukan/i', $line, $match)) {
                $file = $match[1];
                $translatedErrors[] = "File `$file` not found. Please ensure the file name matches the requirements in the learning module.";
            } else if (preg_match('/Expected HTTP Status Code:\s+(\d+)[^\d]+Actual Status Code:\s+(\d+)/i', $line, $match)) {
                $expected = $match[1];
                $actual = $match[2];
                $translatedErrors[] = "The HTTP status code is incorrect. Expected: $expected, but the server returned: $actual. Check whether the input data is valid, and whether the success condition logic (e.g., 201 Created) is correctly implemented in the PHP code.";
                continue;
            } else if (preg_match('/Step\s+Fail\s+"(.*?)"/', $line, $matches)) {
                $result['step_fail'] = "Step Fail: " . $matches[1];
            } else if (preg_match('/Step\s+See\s+"(.+?)","(.+?)"/i', $line, $match)) {
                $teksDicari = $match[1];
                $selector = $match[2];

                if (isset($lines[$i + 1]) && strpos($lines[$i + 1], "Failed asserting that any element by") !== false) {
                    $elemenTersedia = [];
                    for ($j = $i + 2; $j < count($lines); $j++) {
                        $safeSelector = preg_quote($selector, '/');
                        if (preg_match('/<(' . $safeSelector . ')[^>]*>/i', $lines[$j], $m)) {
                            $elemenTersedia[] = trim($m[1]);
                        }
                    }

                    $elemenStr = count($elemenTersedia) > 0
                        ? implode(', ', $elemenTersedia)
                        : 'There is no <' . $selector . '> element found on the page.';

                    $translatedErrors[] = "Element <$selector> with text \"$teksDicari\" not found. Available elements: $elemenStr";
                }
            } else if (preg_match('/Step\s+See element\s+"(.+?\[.*?=.*?\])"/i', $line, $match)) {
                $selector = stripcslashes($match[1]);

                if (isset($lines[$i + 1]) && strpos($lines[$i + 1], "was not found") !== false) {
                    $availableForms = [];
                    for ($j = $i + 2; $j < count($lines); $j++) {
                        if (preg_match('/<form[^>]*action="([^"]+)"[^>]*>/i', $lines[$j], $m)) {
                            $availableForms[] = $m[1];
                        }
                    }

                    $formStr = count($availableForms) > 0
                        ? "Forms available on the page: " . implode(', ', $availableForms)
                        : "No <form> element with `action` attribute found.";

                    $translatedErrors[] = "Element with selector `$selector` not found on the page. $formStr";
                }
            } else if (preg_match('/The (\w+) method is not supported.*Supported methods:\s*(.+?)\./i', $line, $match)) {
                $method = strtoupper($match[1]);
                $supported = strtoupper($match[2]);
                $translatedErrors[] = "Method \"$method\" is not supported. Supported methods: $supported.";
            } else if (preg_match('/<b>Warning<\/b>:\s+(.*?) in <b>(.*?)<\/b> on line <b>(\d+)<\/b>/', $line, $match)) {
                $pesan = htmlspecialchars_decode($match[1], ENT_QUOTES);
                $file = $match[2];
                $baris = $match[3];
                $translatedErrors[] = "Warning: $pesan on line $baris. The variable is possibly undefined.";
            } else if (preg_match('/<b>Parse error<\/b>:\s+(.+?) in <b>.+?<\/b> on line <b>(\d+)<\/b>/i', $line, $match)) {
                $pesan = htmlspecialchars_decode($match[1], ENT_QUOTES);
                $baris = $match[2];
                $translatedErrors[] = "Syntax error: $pesan on line $baris. Check the code structure.";
                continue;
            } else if (preg_match('/(Parse error|Fatal error|on line \d+|Exception|Error|Gagal mendapatkan koneksi database)/i', $line)) {
                if (strpos($line, 'Invalid json') !== false && preg_match('/System message: (.+)/', $line, $match)) {
                    $translatedErrors[] = "Response not valid: JSON corrupted due to other output (such as warnings or HTML)."
                        . " System message: {$match[1]}. Please ensure the file does not output HTML before JSON.";
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

                if (preg_match('/Fatal error.*mysqli_sql_exception: Table \'([^\']+)\' doesn\'t exist/', $line, $match)) {
                    $namaTabel = $match[1];
                    $translatedErrors[] = "Database table not found: '$namaTabel'. Check that the table has been created in the database and that the table name is correct.";
                }

                continue;
            } else if (stripos($line, 'Failed asserting that two strings are identical') !== false) {
                $captureAssertion = true;
                continue;
            } else if ($captureAssertion) {
                if (preg_match("/^-\'(.+?)\'$/", trim($line), $m)) {
                    $expected = $m[1];
                } elseif (preg_match("/^\+\'(.+?)\'$/", trim($line), $m)) {
                    $actual = $m[1];
                }

                if ($expected !== null && $actual !== null) {
                    $translatedErrors[] = "String writing error. Expected: $expected. Actual: $actual";
                    $captureAssertion = false;
                    $expected = null;
                    $actual = null;
                }
            } else if (preg_match('/^Fail\s+(.*)/', $line, $matches)) {
                $result['fail'] = "Fail: " . trim($matches[1]);
            } else if (preg_match('/require\((.*?)\): Failed to open stream: No such file or directory/i', $line, $match)) {
                $file = $match[1];
                $translatedErrors[] = "File `$file` not found. Please ensure the file name matches the requirements in the learning module.";
            } else if (preg_match('/Fatal error:\s+Uncaught Error: Failed opening required \'(.+?)\'/', $line, $match)) {
                $file = $match[1];
                $translatedErrors[] = "Failed to open file `$file`. Please check if the file is available and accessible.";
            } else if (preg_match('/on line (\d+)/', $line, $match)) {
                $translatedErrors[] = "Error occurred on line " . $match[1] . ". Please check that line in the mentioned file.";
            } else if (stripos($line, 'Fail  Response JSON does not contain the provided JSON') !== false) {
                $translatedErrors[] = "Response JSON does not contain the expected structure. "
                    . "Ensure the response includes the 'status' and 'message' keys.";
                continue;
            } else if (preg_match('/- *\'status\' *=> *\'([^\']+)\'/', $line, $match)) {
                $expectedStatus = $match[1];
                $translatedErrors[] = "Expected status: '$expectedStatus' not found in response JSON.";
            } else if (preg_match('/- *\'message\' *=> *\'([^\']+)\'/', $line, $match)) {
                $expectedMessage = $match[1];
                $translatedErrors[] = "Expected message: '$expectedMessage' not found in response JSON.";
            } else if (preg_match('/\+ *\'data\' *=>/', $line)) {
                $translatedErrors[] = "Response only contains 'data'. Check if the response also includes 'status' and 'message'.";
            } else if (preg_match('/Form element with \'form\[action="(.+?)"\]\' was not found\./i', $line, $match)) {
    $action = $match[1];

    // Cari form-form lain yang tersedia di output HTML jika ada
    $availableForms = [];
    for ($j = $i + 1; $j < count($lines); $j++) {
        if (preg_match('/<form[^>]*action="([^"]+)"[^>]*>/i', $lines[$j], $m)) {
            $availableForms[] = $m[1];
        }
    }

    $formList = count($availableForms)
        ? "Forms available on the page: " . implode(', ', array_unique($availableForms))
        : "No <form> tag with `action` attribute found on the page.";

    $translatedErrors[] = "Form with action \"$action\" not found. $formList. "
        . "Ensure the HTML form is written correctly and the action is appropriate.";
}

            if (preg_match('/Step\s+See response is json\s+Fail\s+response is empty/i', $output)) {
                $translatedErrors[] = "Empty response from server when JSON is expected. Ensure that the API endpoint sends valid and non-empty JSON output.";
            }
        }

        // --- Tambahan baru: Capture semua judul test (class & title) ---
        $testTitles = [];
        $successTestTitles = [];
        foreach ($lines as $line) {
            // Tangkap baris yang diawali dengan "+ Kelas: Judul (durasi)"
            if (preg_match('/^\+\s+([\w\\\\]+):\s+(.+?)\s+\(\d+\.\d+s\)/', $line, $matches)) {
                $successTestTitles[] = [
                    'class' => $matches[1],
                    'title' => $matches[2],
                ];
            }
        }

        // Simpan ke result jika semua test sukses
        if (!empty($successTestTitles)) {
            $result['success_test_titles'] = $successTestTitles;
        }

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
                $errors = $errorChunks[$index] ?? [];
                if (!empty($errors)) { // Hanya tambahkan jika ada error
                    $structuredMessages[] = [
                        'title' => $title['title'] ?? 'Title not available',
                        'errors' => $errors,
                    ];
                }
            }
            if (!empty($structuredMessages)) {
                $result['message'] = $structuredMessages;
            }
        }

        // Cegah penimpaan message array jika sudah disusun sebelumnya
        if (empty($result['message']) && empty($translatedErrors) && empty($result['duration']) && empty($result['memory']) && empty($result['error_lines'])) {
            $result = [];
        } elseif (!empty($translatedErrors) && (!isset($result['message']) || !is_array($result['message']))) {
            $result['message'] = implode("\n", $translatedErrors);
        } elseif (preg_match('/^OK\s+\(\d+\s+tests?,\s+\d+\s+assertions\)/mi', $output)) {
            if (!empty($result['success_test_titles'])) {
                $structuredMessages = [];
                foreach ($result['success_test_titles'] as $test) {
                    $structuredMessages[] = [
                        'title' => $test['title'],
                        'errors' => ['Test Success.'],
                    ];
                }
                $result['message'] = $structuredMessages;
            } else {
                $result['message'] = "Test Success.";
            }
        } elseif (empty($result['message'])) {
            $result['message'] = "Test failed. Make sure there are no typos in the code and that the code has been written completely.";
        }

        return $result;
    }

    // public function runIndex($username)
    // {
    //     $phpPath = public_path("storage/restapi/{$username}/index.php");
    //     $htmlPath = public_path("storage/restapi/{$username}/index.html");

    //     if (File::exists($phpPath)) {
    //         ob_start();
    //         include($phpPath);
    //         $renderedContent = ob_get_clean();
    //     } elseif (File::exists($htmlPath)) {
    //         $renderedContent = File::get($htmlPath);
    //     } else {
    //         abort(404, 'File not found.');
    //     }

    //     return view('restapi.runtest', [
    //         'username' => $username,
    //         'content' => $renderedContent,
    //     ]);
    // }

    // public function runIndex($username)
    // {
    //     $phpPath = public_path("storage/restapi/{$username}/index.php");
    //     $htmlPath = public_path("storage/restapi/{$username}/index.html");

    //     if (!File::exists($phpPath) && !File::exists($htmlPath)) {
    //         return response()->json([
    //             'message' => 'Belum upload file Form HTML Manajemen Data.'
    //         ], 422);
    //     }

    //     ob_start();
    //     if (File::exists($phpPath)) {
    //         include($phpPath);
    //     } else {
    //         echo File::get($htmlPath);
    //     }
    //     $renderedContent = ob_get_clean();

    //     return response()->json([
    //         'message' => 'File berhasil dijalankan.',
    //         'html' => $renderedContent,
    //     ]);
    // }
}    