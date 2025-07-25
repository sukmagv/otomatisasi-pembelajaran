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
                        <a class="nav-link active flex items-center" aria-current="page" href="/dashboard-student">Dashboard Student</a>
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
                <div class="p-20 min-h-96 bg-white" id="start-learning" style="padding-bottom: 30px; margin-bottom: 50px">
                    <p style="font-size: 24px; font-weight: 500; color: #34364A;">Start Learning</p>
                    <div>
                        <div class="container mt-4">
                            <!-- NAV TAB -->
                            <ul class="nav nav-tabs">
                                <li class="nav-item">
                                    <a class="nav-link active" id="learning-tab" data-toggle="tab" href="#learning">Learning Topic</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="finished-tab" data-toggle="tab" href="#finished">Topic Finished</a>
                                </li>
                            </ul>

                            <!-- TAB CONTENT -->
                            <div class="tab-content mt-3">
                                <div class="tab-pane fade show active" id="learning">
                                    @foreach($topics as $topic)
                                    @php
                                        $task = $topic->tasks->first();
                                    @endphp
                                    <div class="row">
                                        <div class="col">
                                            <div class="p-3">{{ $topic->title }}</div>
                                            
                                        </div>
                                        <div class="col" style="text-align: right; margin-top: 0px">
                                            <div class="d-flex justify-content-center align-items-center mt-3 pl-3 ms-auto custom-button"
                                                 style="width: 180px; height: 45px; border-radius: 10px; background-color: #EAEAEA; transition: background-color 0.3s, color 0.3s;">
                                                <a class="d-flex align-items-center text-muted text-decoration-none p-2 w-100 text-center" 
                                                   data-toggle="modal" data-target="#exampleModal" 
                                                   data-task-id="{{ $task->id ?? '' }}" 
                                                   data-topic-id="{{ $topic->id }}"
                                                   data-title="{{ $topic->title }}"
                                                   onclick="openMaterialModal(this)">
                                                    <i class="fas fa-key" style="margin-right: 5px;"></i> <!-- Ikon kunci -->
                                                    Material Details
                                                </a>                                              
                                            </div>
                                        </div>                                                                                
                                        
                                    </div>
                                    @endforeach
                                </div>
                                <div class="tab-pane fade" id="finished">
                                    <h3>Topic Finished</h3>
                                    <p>This is the content for the Topic Finished.</p>
                                    <div class="table-responsive">
                                        <table id="topicsTable" class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Title</th>
                                                    <th>Submitted Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topicFinished as $index => $finished)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $finished->title }}</td>
                                                    <td>
                                                        @if($finished->updated_at)
                                                            {{ $finished->updated_at->format('Y-m-d H:i:s') }}
                                                        @elseif($finished->created_at)
                                                            {{ $finished->created_at->format('Y-m-d H:i:s') }}
                                                        @else
                                                            Not Submitted
                                                        @endif
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
    
 <!-- The Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width: 80%;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><span id="span_title"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="text-center">
                                <img src={{asset("./images/online_virtual_machine.png")}} alt="logo" width="400" height="300">
                            </div>
                            <h5>Material PHP</h5>
                                <input type="hidden" id="id" />
                                <input type="hidden" id="title" />
                                <input type="hidden" id="controller" />
                            </div>

                            <div class="col-md-7">
                                <b>Prerequisite knowledge : </b>
                                <div class="text-sm" style="margin-bottom: 20px">
                                    <p style="margin-bottom: 5px !important">Before starting to learn this material, you should have basic knowledge of <b>PHP</b>.</p>
                                    PHP functions are used to process data, manage strings, arrays, numbers, and interact with the server.
                                </div>

                                <b>Requirement : </b>
                                <div class="text-sm mb-10" style="margin-bottom: 20px">
                                    1. Intel Core 2 duo processor or equivalent.<br>
                                    2. Have at least 4 GB RAM or more<br>
                                    3. 120 GB HDD hard disk with minimum available storage of 20 GB<br>
                                    4. Ethernet connection and Wi-Fi capabilities
                                </div>

                                <b>Tools : </b><br>
                                <div class="row">
                                    <div class="col-md-4 text-center text-sm p-2">
                                        <a href="https://code.visualstudio.com/" target="_blank">
                                            <img style="width: 90px; height: 80px;" src="{{asset("./images/php/vscode.png")}}" alt="">
                                        </a>
                                        <br>
                                        VISUAL STUDIO CODE
                                    </div>
                                    <div class="col-md-4 text-center text-sm p-2">
                                        <a href="https://code.visualstudio.com/" target="_blank">
                                            <img style="width: 90px; height: 80px;" src="{{asset("./images/php/xampp.png")}}" alt="">
                                        </a>
                                        <br>
                                        XAMPP
                                    </div>
                                    <div class="col-md-4 text-center text-sm p-2">
                                        <a href="https://code.visualstudio.com/" target="_blank">
                                            <img style="width: 90px; height: 80px;" src="{{asset("./images/php/postman.png")}}" alt="">
                                        </a>
                                        <br>
                                        POSTMAN
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" style="margin-left: 10px; width: 160px;" onclick="redirectToDetail()">
                            <i class="fas fa-key" style="margin-right: 5px;"></i>Enroll Material
                        </button>
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

        // Variabel global untuk menyimpan topic_id & task_id
        var selectedTopicId = null;
        var selectedTaskId = null;

        function openMaterialModal(element) {
            // Simpan ID yang dipilih
            selectedTopicId = element.getAttribute("data-topic-id");
            selectedTaskId = element.getAttribute("data-task-id");

            // Tampilkan modal
            $("#exampleModal").modal("show");
        }

        function redirectToDetail() {
            if (selectedTopicId && selectedTaskId) {
                window.location.href = "{{ route('restapi_topic_detail') }}" +
                    "?id=" + encodeURIComponent(selectedTopicId) +
                    "&task_id=" + encodeURIComponent(selectedTaskId);
            } else {
                alert("Terjadi kesalahan, silakan coba lagi.");
            }
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

        // var validationLink = document.getElementById('validationLink');
        // validationLink.addEventListener('click', function() {
        //     changeColor('validation');
        // });

        // var rankLink = document.getElementById('rankLink');
        // rankLink.addEventListener('click', function() {
        //     changeColor('rank');
        // });

        // var settingsLink = document.getElementById('settingsLink');
        // settingsLink.addEventListener('click', function() {
        //     changeColor('settings');
        // });


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

    <!-- Footer -->
    <footer class="footer" style="background-color: #EAEAEA; color: #636363; text-align: center; padding: 10px 0; position: fixed; bottom: 0; width: 100%;">
        © 2023 Your Website. All rights reserved.
    </footer>

</body>


</html>