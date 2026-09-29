<?php

$host = "localhost";
$username = "root";
$password = ""; // Agar password set hai toh yahan likhein (e.g. "root"), nahi toh khali "" rehne dein
$database = "kharido_db";
#$port = 3307; // 👈 Port 3307 specify karein

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

?>