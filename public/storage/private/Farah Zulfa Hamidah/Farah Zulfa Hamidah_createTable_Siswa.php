<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "data_sekolah";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $database);

    // Check connection
    if ($conn) {
        echo "Koneksi dengan MYSQL berhasil <br>";
    }
    else {
        echo "Koneksi dengan MYSQL gagal" . mysqli_connect_error();
    }

    $sql = "CREATE TABLE siswa(
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            nama_depan VARCHAR(50) NOT NULL,
            nama_belakang VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            jenis_kelamin VARCHAR(50) NOT NULL)";
    
    if (mysqli_query($conn, $sql)){
        echo "Tabel siswa berhasil dibuat";
    }
    else {
        echo "Tabel siswa gagal dibuat <br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
?>