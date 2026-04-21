<?php
// إعدادات السيرفر
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pfe";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التثبت من الاتصال
if ($conn->connect_error) {
    die("❌ Erreur de connexion à la base : " . $conn->connect_error);
}

// دعم اللغة العربية والفرنسية
$conn->set_charset("utf8mb4");
?>