<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* Additional styles */

        .custom-button:hover {
            background-color: #007BFF !important; /* Warna latar belakang saat hover */
        }

        .custom-button:hover a {
            color: white !important; /* Warna teks saat hover */
        }

        .nav-tabs .nav-link {
            color: #939596; /* Warna abu-abu default */
        }

        .nav-tabs .nav-link.active {
            color: #000; /* Warna hitam untuk tab aktif */
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }


        .nav-link:hover {
            color: blue !important;
        }

        .text {
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
        }

        .sidebar-right-shadow {
            box-shadow: 1px 0px 8px rgba(0, 0, 0, 0.1);
            /* Menambahkan bayangan ke sisi kanan */
        }

        .dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
            transition: 0.3s;
            opacity: 0;
            transform: translateY(-10px);
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .dropdown.active .dropdown-content {
        display: block;
        opacity: 1;
        transform: translateY(0);
        }

    </style>

    <title>Tab Example</title>

    <!-- CSS Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- <link href="style.css" rel="stylesheet"> --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <!-- JavaScript Bootstrap -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="icon" href="./images/logo.png" type="image/png">
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg" style="background-color: #FEFEFE;">
        <div class="container-fluid">
            <!-- <a class="navbar-brand" href="#">Navbar</a> -->
            <img src={{asset("./images/logo.png")}} alt="logo" width="104" height="65">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <div class="mx-auto">
                    <ul class="navbar-nav mb-2 mb-lg-0 justify-content-center">
                        <li class="nav-item">
                        <a class="nav-link active flex items-center" aria-current="page" href="/dashboard-student">Dashboard Teacher</a>
                        </li>
                    </ul>
                </div>
                <div class="dropdown">
                    <p style="margin-top: 10px; margin-right: 10px;">{{auth()->user()->name}}
                    <img src="{{ asset('./images/Group.png') }}" alt="Group" style="height: 50px; margin-right: 10px;">
                    <i class="fas fa-chevron-down" style="color: #0079FF;"></i>
                    <div class="dropdown-content" id="dropdownContent">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                        </form>
                        
                    </div>
                </p>
                </div>
            </div>
        </div>
    </nav>
    <!-- ------------------------------------------------------------------------------------------ -->

    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <nav class="col-md-2 d-none d-md-block sidebar sidebar-right-shadow" style="background-color: #FFFFFF; width: 245px;">
                <div class="sidebar-sticky" style="margin-top: 20px;">
                    <ul class="nav flex-column flex-">
                        <li class="nav-item" style="margin-bottom: 30px;">
                            <div class="row align-items-start">
                                <div class="row">
                                    <p style="font-weight: 600; font-size: 14px; color: #34364A; margin-left: 15px;">STUDENT WEBAPPS</p>
                                </div>
                                <div class="row">
                                    <img src="{{asset("./images/php/php.png")}}" alt="learning-logo" style="width: 150px; margin-left: 15px;">
                                </div>
                            </div>
                        </li>
                        <li class="nav-item">
                            <div class="row align-items-start">
                                <div class="col-2">
                                    <i class="fas fa-book" style="margin-top: 12px; margin-left: 15px; color: #676767;" id="learningIcon"></i>
                                </div>
                                <div class="col">
                                    <a class="nav-link active" href="#" onclick="showContent('start-learning')" style="color: #34364A;" id="learningLink">Start Learning</a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- ------------------------------------------------------------------------------------------ -->
           
            <!-- CONTENT -->
            <main class="col-md-9">
                <div class="p-20 min-h-96 bg-white" id="start-learning" style="padding-bottom: 30px">
                    <p style="font-size: 24px; font-weight: 500; color: #34364A;">Start Learning</p>
                    <div>
                        <div class="container mt-4">
                            <!-- NAV TAB -->
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" id="learning-tab" data-bs-toggle="tab" href="#topics">Learning Topic</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="task-tab" data-bs-toggle="tab" href="#task">Task</a>
                                </li>
                            </ul>
            
                            <!-- TAB CONTENT -->
                            <div class="tab-content mt-3">
                                <!-- Learning Topic Tab -->
                                <div class="tab-pane fade show active" id="topics">
                                    <button class="btn btn-primary my-3" style="width: 180px" onclick="addTopic()">
                                        <i class="fas fa-plus mr-2"></i>Add topic
                                    </button>
                                    <div class="table-responsive">
                                        <table id="topicsTable" class="table table-striped table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Title</th>
                                                    <th>Description</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topics as $index => $topic)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $topic->title }}</td>
                                                    <td>{{ $topic->description }}</td>
                                                    <td class="text-center">
                                                        <!-- Tombol Edit -->
                                                        <button class="btn btn-warning btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#editModal" 
                                                                onclick="editTopic('{{ $topic->id }}', '{{ $topic->title }}', '{{ $topic->description }}')">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                    
                                                        <!-- Tombol Delete -->
                                                        <button class="btn btn-danger btn-sm" 
                                                                onclick="deleteTopic('{{ $topic->id }}')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>  
            
                                <!-- Task Tab -->
                                <div class="tab-pane fade" id="task">
                                    <button class="btn btn-primary my-3" style="width: 180px" onclick="addTask()">
                                        <i class="fas fa-plus mr-2"></i>Add task
                                    </button>
                                    <div class="table-responsive">
                                        <table id="taskTable" class="table table-striped table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Topic ID</th>
                                                    <th>Title</th>
                                                    <th>Order Number</th>
                                                    <th>Need Submission</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tasks as $index => $task)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $task->topic_id }}</td>
                                                    <td>{{ $task->title }}</td>
                                                    <td>{{ $task->order_number }}</td>
                                                    <td>{{ $task->flag }}</td>
                                                    <td class="text-center">
                                                        <!-- Tombol Edit -->
                                                        <button class="btn btn-warning btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#editModal" 
                                                                onclick="editTask('{{ $task->id }}', '{{ $task->topic_id }}', '{{ $task->title }}', '{{ (int) $task->order_number }}', '{{ $task->flag }}')">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                    
                                                        <!-- Tombol Delete -->
                                                        <button class="btn btn-danger btn-sm" 
                                                                onclick="deleteTask('{{ $task->id }}')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>  
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
 <!-- The Topic Modal -->
    <!-- Modal Add Topic -->
    <div class="modal fade" id="addTopicModal" tabindex="-1" aria-labelledby="addTopicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTopicModalLabel">Add New Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTopicForm" method="POST" action="{{ route('restapi_add_topic') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="addTopicTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="addTopicTitle" name="title" required>
                            <label for="addTopicDesc" class="form-label">Description</label>
                            <textarea class="form-control" id="addTopicDesc" name="description" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Add Topic</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editTopicModal" tabindex="-1" aria-labelledby="editTopicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTopicModalLabel">Edit Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTopicForm" method="POST" action="{{ route('restapi_update_topic') }}">
                        @csrf
                        <input type="hidden" name="id" id="editTopicId">
                        <div class="mb-3">
                            <label for="editTopicTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTopicTitle" name="title" required>
                            <label for="editTopicDesc" class="form-label">Description</label>
                            <textarea class="form-control" id="editTopicDesc" name="description" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal fade" id="deleteTopicModal" tabindex="-1" aria-labelledby="deleteTopicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTopicModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this topic?</p>
                    <form id="deleteTopicForm" method="POST" action="{{ route('restapi_delete_topic') }}">
                        @csrf
                        <input type="hidden" name="id" id="deleteTopicId">
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- The Task Modal -->
    <!-- Modal Add -->
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addTaskForm" method="POST" action="{{ route('restapi_add_task') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="addTaskTopic" class="form-label">Select Topic</label>
                            <select class="form-control" id="addTaskTopic" name="topic_id" required>
                                <option value="">-- Select Topic --</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                                @endforeach
                            </select>
                            <label for="addTaskTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="addTaskTitle" name="title" required>
                            <label for="addTaskOrder" class="form-label">Order Number</label>
                            <input type="number" class="form-control" id="addTaskOrder" name="order_number" required>  
                            <label for="addTaskFlag" class="form-label">Need Submission?</label>
                            <select class="form-control" id="addTaskFlag" name="flag" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                            <!-- Input File -->
                            <label for="addTaskFile" class="form-label">Upload PDF</label>
                            <input type="file" class="form-control" id="addTaskFile" name="file_path" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-success">Add Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Topic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editTaskForm" method="POST" action="{{ route('restapi_update_task') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="editTaskId">
                        <div class="mb-3">
                            <label for="editTaskTopic" class="form-label">Select Topic</label>
                            <select class="form-control" id="editTaskTopic" name="topic_id" required>
                                <option value="">-- Select Topic --</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                                @endforeach
                            </select>
                            <label for="editTaskTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTaskTitle" name="title" required>
                            <label for="editTaskOrder" class="form-label">Order Number</label>
                            <input type="number" class="form-control" id="editTaskOrder" name="order_number" required>  
                            <label for="editTaskFlag" class="form-label">Need Submission?</label>
                            <select class="form-control" id="editTaskFlag" name="flag" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Delete Confirmation -->
    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTaskModalLabel">Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this task?</p>
                    <form id="deleteTaskForm" method="POST" action="{{ route('restapi_delete_task') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" id="deleteTaskId">
                        <button type="submit" class="btn btn-danger">Yes, Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript untuk mengubah konten tab -->
    <script>
        function materialModal(id,title,controller){
            $("#id").val(id);
            $("#title").val(title);
            $("#controller").val(controller);
            $("#span_title").text(title);
        }

        function materialDetailPage() {
            var csrfToken = "{{ csrf_token() }}";
            let id = $("#id").val();
            let title = $("#title").val();
            let controller = $("#controller").val();
            window.location.href = "{{ route('restapi_topic_detail') }}?id=" + encodeURIComponent(id);
        }

        // Fungsi untuk mengubah warna ikon, teks, dan link menjadi biru
        function changeColor(id) {
            var icon = document.getElementById(id + 'Icon');
            var link = document.getElementById(id + 'Link');
            var text = document.getElementById(id + 'Text');

            // Mengembalikan warna ikon, teks, dan link ke warna awal
            var icons = document.getElementsByClassName('fas');
            var links = document.getElementsByClassName('nav-link');
            var texts = document.getElementsByClassName('nav-link-text');
            for (var i = 0; i < icons.length; i++) {
                icons[i].style.color = '#676767';
            }
            for (var j = 0; j < links.length; j++) {
                links[j].style.color = '#34364A';
            }
            for (var k = 0; k < texts.length; k++) {
                texts[k].style.color = '#34364A';
            }

            // Mengubah warna ikon, teks, dan link menjadi biru
            icon.style.color = '#1A79E3';
            link.style.color = '#1A79E3';
            text.style.color = '#1A79E3';
        }

        // Menambahkan event listener pada setiap link
        var startLearningLink = document.getElementById('learningLink');
        startLearningLink.addEventListener('click', function() {
            changeColor('learning');
        });

        // Function to show the selected content based on sidebar link click
        function showContent(contentId) {
            // Hide all content divs
            var contentDivs = document.getElementsByClassName('content');
            for (var i = 0; i < contentDivs.length; i++) {
                contentDivs[i].style.display = 'none';
            }

            // Show the selected content div
            var selectedContent = document.getElementById(contentId);
            if (selectedContent) {
                selectedContent.style.display = 'block';
            }
        }

        //  Change TAB
        $(document).ready(function() {
            $('#learning-tab').on('click', function(e) {
                e.preventDefault();
                $('#finished-tab').removeClass('active');
                $(this).tab('show');
            });

            $('#finished-tab').on('click', function(e) {
                e.preventDefault();
                $('#learning-tab').removeClass('active');
                $(this).tab('show');
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#dropdownContainer").click(function() {
                $("#dropdownContainer").toggleClass("active");
            });
            $("#dropdownContent").click(function(e) {
                e.stopPropagation();
            });
            $(document).click(function() {
                $("#dropdownContainer").removeClass("active");
            });
        });
    </script>
    <script>
        // Function untuk menampilkan modal Add Topic
        function addTopic() {
            $('#addTopicTitle').val('');
            $('#addTopicDesc').val('');
            $('#addTopicModal').modal('show');
        }
    
        // Function untuk menampilkan modal Edit Topic
        function editTopic(id, title, description) {
            $('#editTopicId').val(id);
            $('#editTopicTitle').val(title);
            $('#editTopicDesc').val(description);
            $('#editTopicModal').modal('show');
        }
    
        // Function untuk menampilkan modal Delete Topic
        function deleteTopic(id) {
            $('#deleteTopicId').val(id);
            $('#deleteTopicModal').modal('show');
        }


        // Function untuk menampilkan modal Add Task
        function addTask() {
            $('#addTaskTopic').val('');
            $('#addTaskTitle').val('');
            $('#addTaskOrder').val('');
            $('#addTaskFlag').val('');
            $('#addTaskFile').val('');
            $('#addTaskModal').modal('show');
        }
    
        // Function untuk menampilkan modal Edit Task
        function editTask(id, topic_id, title, order_number, flag) {
            $('#editTaskId').val(id);
            $('#editTaskTopic').val(topic_id);
            $('#editTaskTitle').val(title);
            $('#editTaskOrder').val(order_number);
            $('#editTaskModal').modal('show');
        }
    
        // Function untuk menampilkan modal Delete Task
        function deleteTask(id) {
            $('#deleteTaskId').val(id);
            $('#deleteTaskModal').modal('show');
        }

        $(document).on('hidden.bs.modal', '.modal', function () {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });
        
        document.addEventListener("DOMContentLoaded", function () {
        // Cek apakah ada tab yang disimpan di localStorage
        let activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            let tabElement = document.querySelector(`a[href="${activeTab}"]`);
            if (tabElement) {
                new bootstrap.Tab(tabElement).show();
            }
        }

        // Simpan tab yang diklik ke localStorage
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('click', function (e) {
                let tabId = this.getAttribute('href');
                localStorage.setItem('activeTab', tabId);
            });
        });
    });

    </script>
    

    <!-- Footer -->
    <footer class="footer" style="background-color: #EAEAEA; color: #636363; text-align: center; padding: 10px 0; position: fixed; bottom: 0; width: 100%;">
        © 2023 Your Website. All rights reserved.
    </footer>

</body>


</html>