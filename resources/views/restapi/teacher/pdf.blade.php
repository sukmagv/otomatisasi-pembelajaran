<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Submissions PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h2>Task Submissions Report</h2>
    
    @foreach ($filesContent as $file)
        <h3>{{ $file['task_title'] }} ({{ $file['topic_title'] }})</h3>
        <p><strong>Nama mahasiswa:</strong> {{ $file['user_name'] }}</p>
        <p><strong>Email mahasiswa:</strong> {{ $file['user_email'] }}</p>
        <p><strong>Waktu pengumpulan:</strong> {{ $file['created_at'] }}</p>
        <p><strong>Deskripsi topik:</strong> {{ $file['topic_desc'] }}</p>
        @if($file['interval'])
            <p><strong>Rentang waktu dengan pengumpulan sebelumnya:</strong> 
                {{ str_replace(' before', '', $file['interval']['interval_readable']) }}
            </p>
        @else
            <p><strong>Rentang waktu dengan pengumpulan sebelumnya:</strong>-</p>
        @endif
        <p><strong>Pengumpulan ke-{{ $file['submission_position'] }}</strong></p>
        <h4>Submitted Code:</h4>
        <pre>{{ $file['code'] }}</pre>
        <p><strong>Output:</strong></p>
        <pre>{!! nl2br(e($file['run_output'])) !!}</pre>
        <p><strong>Test Result:</strong></p>
        <pre>{!! nl2br(e($file['test_result'])) !!}</pre>
        <hr>
    @endforeach
</body>
</html>