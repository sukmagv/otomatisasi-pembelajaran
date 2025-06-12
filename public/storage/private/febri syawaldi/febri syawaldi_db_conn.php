<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "data_sekolah";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Check connection
    if ($conn) {
        echo "Koneksi berhasil";
    }
    else {
        echo "Koneksi dengan MYSQL gagal" . mysqli_connect_error();
    }
?>