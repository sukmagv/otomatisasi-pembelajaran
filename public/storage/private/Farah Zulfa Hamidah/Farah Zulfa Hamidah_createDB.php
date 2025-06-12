<?php
    $servername = "localhost";
    $username = "root";
    $password = "";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password);

    // Check connection
    if ($conn) {
        echo "Koneksi dengan MYSQL berhasil <br>";
    }
    else {
        echo "Koneksi dengan MYSQL gagal" . mysqli_connect_error();
    }

    $sql = "CREATE DATABASE data_sekolah";
    if (mysqli_query($conn, $sql)){
        echo "Database berhasil dibuat";
    }
    else {
        echo "Database gagal dibuat <br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
?>