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

    $sql = "CREATE TABLE guru(
            id INT PRIMARY KEY AUTO_INCREMENT, 
            nip VARCHAR(30) NOT NULL,
            nama VARCHAR(30) NOT NULL,
            alamat VARCHAR(50) NOT NULL,
            jenis_kelamin VARCHAR(50) NOT NULL)";
    
    if (mysqli_query($conn, $sql)){
        echo "Tabel guru berhasil dibuat";
    }
    else {
        echo "Tabel guru gagal dibuat <br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
?>