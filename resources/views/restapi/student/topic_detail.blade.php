<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <title>iCLOP</title>
    <link rel="icon" href="./images/logo.png" type="image/png">
    <style>
        .text {
            font-family: 'Poppins', sans-serif;
            color: #3F3F46;
            text-decoration: none
        }

        .text-list {
            font-family: 'Poppins', sans-serif;
            color: #3F3F46;
        }

        /* CSS untuk mengatur sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            right: 0;
            overflow-x: hidden;
        }

        /* Gaya dropdown */
        .dropdown {
            padding: 6px 8px;
            display: inline-block;
            cursor: pointer;
        }

        /* Gaya dropdown content */
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .list-item {
            display: flex;
            align-items: center;
            /* justify-content: space-between; */
            padding: 10px;
            border: 1px solid #E4E4E7;
            cursor: pointer;
            margin-bottom: 10px;
            border: none;
        }

        .list-item:hover {
            background-color: #F5F5F8;
        }

        .list-item-title {
            font-size: 18px;
            margin-left: 10px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            color: #3F3F46;
        }

        .list-item-icon {
            font-size: 20px;
        }

        .expandable-content {
            margin-top: 0px;
            display: none;
            padding: 10px;
            border-top: 1px solid #E4E4E7;
            border: none;
            margin-left: 32px;
        }

        .radio-label {
            font-weight: bold;
            color: #333;
            font-size: 18px;
        }

        .progress-container {
            width: 100%;
            background-color: #f1f1f1;
        }

        .progress-bar {
            width: 40;
            height: 30px;
            background-color: #4caf50;
            text-align: center;
            line-height: 30px;
            color: white;
        }

        .progress-text {
            margin-top: 10px;
            font-size: 18px;
            text-align: center;
        }
        .text:hover {
        color: black; /* Change text color to blue on hover */
        text-decoration: underline; /* Add underline on hover */
        }
        .accordion-button::after {
        order: -1; /* Memindahkan arrow ke kiri */
        margin-right: 10px;
        margin-left: 0;
        }
        .accordion-button {
            background-color: transparent !important; /* Hapus background default */
            box-shadow: none !important; /* Hapus efek shadow saat aktif */
            border: none; /* Hapus border */
            color: inherit; /* Gunakan warna teks bawaan */
            font-weight: bold !important;
        }

        .accordion-button:not(.collapsed) {
            background-color: transparent !important; /* Tetap transparan saat aktif */
            color: inherit; /* Jaga warna teks */
        }

        .accordion-button:focus {
            box-shadow: none !important; /* Hapus shadow saat diklik */
        }

        .accordion-item {
            border: none; /* Hapus border antar item */
        }

        .list-group-item {
            border: none !important; /* Hapus border antar task */
        }
    </style>
    
    <style>
        @media only screen and (max-width: 600px) {
            #sidebar {
                display: none; /* Hide sidebar on small screens */
            }

            div[style*="max-width: 800px"] {
                max-width: 90%; /* Adjust max-width of container */
            }
        }
    </style>
    <style>
        #progressbar {
            width: @php 
                    $width = session('params');
                    echo $width."%"; 
                   @endphp;
            height: 20px;
            background-color: #4caf50;
            border-radius: 10px;
        }

        
    </style>
</head>
<!-- This is body test -->

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-light bg-light" style="padding: 15px 20px; border-bottom: 1px solid #E4E4E7; font-family: 'Poppins', sans-serif;">
        <a class="navbar-brand" href="{{ route('restapi_student') }}">
            <img src="{{ asset('images/left-arrow.png') }}" style="height: 24px; margin-right: 10px;">
            {{ $row->title }}
        </a>
    </nav> 

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar bg-white" style="width: 250px; border-left: 1px solid #E4E4E7; padding: 20px; width: 100%; max-width: 400px; height: 100%;">
        <div style="display: flex;">
            <img src="{{ asset('images/right.png') }}" style="height: 24px; margin-right: 10px; margin-top: 3px; border:1px solid; border-radius:50%">
            <p class="text-list" style="font-size: 20px; font-weight: 600;">
                Task List
            </p>
        </div>  
        <div style="margin-bottom: 10px;">
            <strong>Waktu Tersisa:</strong>
            <span id="countdown" style="color: red; font-weight: bold;">Memuat...</span>
        </div>
      

        <div class="progress-container">
            <div id="progressbar" style="width: {{ session('progress', 0) }}%;"></div>
        </div>
        <div id="progress">{{ session('progress', 0) }}%</div>
        
        <div class="accordion" id="topicsAccordion" style="margin-top: 20px;">
            @foreach($topics as $topic)
                @php
                    // Ambil task dengan module_type = 1 untuk setiap topic
                    $defaultTask = isset($tasks[$topic->id]) ? $tasks[$topic->id]->where('module_type', 0)->first() : null;
        
                    // Cek apakah topic yang sedang dipilih sesuai dengan yang ada di request
                    $isActive = request()->query('id') == $topic->id;
                    
                    // Ambil ID task yang dipilih dari request
                    $selectedTaskId = request()->query('task_id');
                @endphp
        
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $topic->id }}">
                        <button class="accordion-button {{ $isActive ? '' : 'collapsed' }}" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse{{ $topic->id }}" 
                                aria-expanded="{{ $isActive ? 'true' : 'false' }}" aria-controls="collapse{{ $topic->id }}">
                            {{ $topic->title }}
                        </button>
                    </h2>
                    <div id="collapse{{ $topic->id }}" class="accordion-collapse collapse {{ $isActive ? 'show' : '' }}" 
                         aria-labelledby="heading{{ $topic->id }}" data-bs-parent="#topicsAccordion">
                        <div class="accordion-body">
                            @if(isset($tasks[$topic->id]) && count($tasks[$topic->id]) > 0)
                                <ul class="list-group border-0">
                                    @foreach($tasks[$topic->id] as $taskItem)
                                        @php
                                            // Cek apakah task ini yang sedang dipilih
                                            $isChecked = ($selectedTaskId && $selectedTaskId == $taskItem->id) || 
                                                         (!$selectedTaskId && $defaultTask && $defaultTask->id == $taskItem->id);
                                        @endphp
                                        <li class="list-group-item border-0 d-flex align-items-center">
                                            <!-- Link sebagai pengganti radio button -->
                                            <a href="{{ route('restapi_topic_detail', ['id' => $topic->id, 'task_id' => $taskItem->id]) }}" 
                                               class="d-flex align-items-center text-decoration-none" style="color: inherit;">
                                                <!-- Radio-style effect -->
                                                <span class="form-check me-2">
                                                    <input type="radio" class="form-check-input" disabled {{ $isChecked ? 'checked' : '' }}>
                                                </span>
                                                <!-- Nama Task -->
                                                <span class="form-check-label">
                                                    {{ $taskItem->title }}
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">No tasks available for this topic.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>                
    </div>

    <div style="padding: 20px; max-width: 68%; margin-left:5px;">
        <div style="border: 1px solid #ccc; padding: 20px 10px 10px 30px; border-radius: 5px; margin-bottom:10px">
            @if($pdf_reader == 1 && $activeTask)
            <iframe src="{{ url('storage/' . $activeTask->file_path) }}" style="width: 100%; height: 510px;"></iframe>
            @else
                <p class="text-muted">No PDF available for this topic.</p>
            @endif
        </div>
    </div>
    
    @if($activeTask && $activeTask->flag == 1)

    <div style="padding: 20px; max-width: 68%; margin-left:5px; margin-top: -20px;">
        <div style="border: 1px solid #ccc; padding: 20px 10px 10px 30px; border-radius: 5px;margin-bottom:40px">
            <div style="padding-top: 15px; padding-bottom: 15px">
                <p class='text-list' style='font-size: 24px; font-weight: 600;width: 400px !important;'> Upload File Practicum </p>
                <div class="texts" style="position: relative;">
                    {{-- Upload + Verify Form --}}
                    <form action="{{ Route('restapi_submit_task') }}" method="POST" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <input type="hidden" name="task_id" value="{{ old('task_id', request()->query('task_id')) }}">

                        {{-- Show Uploaded File --}}
                        @if($submission && $submission->submit_path)
                            <div class="form-group mb-3">
                                <label>Uploaded File:</label><br>
                                <a href="{{ request()->fullUrlWithQuery(['view_file' => true]) }}" target="_blank">
                                    {{ basename($submission->submit_path) }}
                                </a>
                            </div>
                        @endif

                        {{-- Upload New File --}}
                        <div class="form-group mb-3">
                            <label for="file">Upload New Evidence</label>
                            <input 
                                type="file" 
                                name="file" 
                                class="form-control" 
                                placeholder="{{ $submission ? basename($submission->submit_path) : '.php file' }}"
                                {{ $submission ? '' : 'required' }}
                            >
                            <small>Enter the work results <code>.php </code> (Max: 2MB)</small>
                        </div>

                        {{-- Comment --}}
                        @if($submission && $submission->submit_comment)
                            <div class="form-group mb-3">
                                <label>Previous Comment:</label>
                                <p class="text-muted">{{ $submission->submit_comment }}</p>
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="comment">New Comment (Optional)</label>
                            <textarea class="form-control" name="comment" placeholder="(Optional) Add a comment..."></textarea>
                        </div>

                        <div class="form-group d-flex" style="gap: 10px; margin-bottom: 20px;">
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>

                        {{-- Success Message --}}
                        @if (session('success'))
                            <div class="alert alert-success mt-3">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Error Message --}}
                        @if ($errors->any())
                            <div class="alert alert-danger mt-3">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </form>

                    {{-- Verify Button --}}

                    <form action="{{ route('restapi_verify') }}" method="POST" style="display: inline;">
                        {{-- Verify Button --}}
                        {{ csrf_field() }}
                        @csrf
                        <input type="hidden" name="task_id" value="{{ old('task_id', request()->query('task_id')) }}">
                        <button type="submit" class="btn btn-success">
                            Verify
                        </button>
                    </form>

                    {{-- Feedback Section --}}
                    @if(session('test_result') || $testResult)
                        <div class="mt-3" style="border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                            <h5><b>Feedback:</b></h5>
                            <pre>{{ session('test_result') ?? $testResult }}</pre>
                        </div>
                    @endif
                    
                    @if (!empty($runOutput))
                        @php
                            $result = ['output' => $runOutput];
                        @endphp

                        <div class="my-3" style="border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                            @if (!empty($result['output']))
                                <strong>Output (JSON):</strong>
                                <pre>{{ json_encode($result['output'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @else
        <div style="padding: 20px; max-width: 68%; margin-left:5px;"></div>
    @endif

    <!-- Footer -->
    <footer class="text-center p-2 fixed-bottom" style="background-color: #EAEAEA; color: #636363; width: 100%;">
        © 2023 Your Website. All rights reserved.
    </footer>
    
    <script src="https://cdn.ckeditor.com/ckeditor5/34.2.0/classic/ckeditor.js"></script>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            let editorElement = document.querySelector('#editor');

            if (editorElement) {
                ClassicEditor
                    .create(editorElement, {
                        ckfinder: {
                            uploadUrl: '{{ route('uploadimage').'?_token='.csrf_token() }}'
                        }
                    })
                    .catch(error => console.error(error));
            }
        });

        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
        }

        function toggleItem(item) {
            const content = item.nextElementSibling;
            const icon = item.querySelector('.list-item-icon');
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
            icon.style.transform = content.style.display === 'block' ? 'rotate(180deg)' : 'none';
        }

        const radioButtons = document.querySelectorAll('input[name="itemSelection"]');
        const textElements = document.querySelectorAll('.text');

        radioButtons.forEach((button, index) => {
            button.addEventListener('change', () => {
                textElements.forEach((textElement, i) => {
                    if (i === index) {
                        textElement.style.fontWeight = 'bold';
                    } else {
                        textElement.style.fontWeight = 'normal';
                    }
                });
            });
        });

        // Fungsi untuk mengupdate progress ke backend
        function updateProgress(topicId) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });

            $.ajax({
                type: "POST",
                url: "{{ Route('session_progress') }}",
                data: { topic_id: topicId },
                success: function(response) {
                    getProgress(); // Refresh progress setelah update
                }
            });
        }

        // Fungsi untuk mengambil dan menampilkan progress dari backend
        function getProgress() {
            $.ajax({
                type: "GET",
                url: "{{ route('restapi_get_progress') }}",
                success: function(response) {
                    let progress = response.progress + "%";
                    $('#progressbar').css('width', progress);
                    $('#progress').text(progress);
                }
            });
        }

        // Panggil saat halaman dimuat untuk mendapatkan progres terbaru
        $(document).ready(function() {
            getProgress();
        });

        const startTime = new Date("{{ \Carbon\Carbon::parse($startTime)->format('Y-m-d H:i:s') }}");
        const endTime = new Date(startTime.getTime() + 2 * 60 * 60 * 1000); // +2 jam
        const uploadBtn = document.querySelector('button[type="submit"]');

        function updateTimer() {
            const now = new Date();
            const remaining = Math.floor((endTime - now) / 1000);

            if (remaining <= 0) {
                document.getElementById('countdown').innerText = "Waktu habis!";
                uploadBtn.disabled = true;
                uploadBtn.innerText = "Waktu habis";
            } else {
                const hours = Math.floor(remaining / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                const seconds = remaining % 60;

                document.getElementById('countdown').innerText = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }

        setInterval(updateTimer, 1000);

    </script>
</body>

</html>