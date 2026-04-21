<?php
// 1. إظهار الأخطاء (Debug Mode) - تنجمي تنحيها كي تكملي السيت
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. إعدادات قاعدة البيانات
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "pfe";

// 3. إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التثبت من الاتصال
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nom    = $conn->real_escape_string(strip_tags(trim($_POST['nom'])));
    $prenom = $conn->real_escape_string(strip_tags(trim($_POST['prenom'])));
    $email  = $conn->real_escape_string(strip_tags(trim($_POST['email'])));
    $titre  = $conn->real_escape_string(strip_tags(trim($_POST['titre_projet'])));
    $desc   = $conn->real_escape_string(strip_tags(trim($_POST['description'])));
    $obj    = $conn->real_escape_string(strip_tags(trim($_POST['objectifs'])));
    $tech   = $conn->real_escape_string(strip_tags(trim($_POST['technologies'])));

    // 5. جملة الـ INSERT (تأكدي أن أسماء الأعمدة مطابقة لجدولك)
    $sql = "INSERT INTO propositions (nom, prenom, email, titre_projet, description, objectifs, technologies)
            VALUES ('$nom', '$prenom', '$email', '$titre', '$desc', '$obj', '$tech')";

    if ($conn->query($sql) === TRUE) {
        // ✅ نجاح: التحويل لصفحة النجاح في مجلد frontend
        header("Location: ../frontend/success.html");
        exit();
    } else {
        // ❌ فشل: إظهار رسالة خطأ بتصميم بسيط
        echo "
        <div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h2 style='color:#e74c3c;'>Erreur Technique</h2>
            <p>Désolé, l'enregistrement a échoué.</p>
            <p style='color:#7f8c8d;'>Debug: " . $conn->error . "</p>
            <a href='../frontend/index.html' style='color:#2c3e50; font-weight:bold;'>Réessayer</a>
        </div>";
    }
}

// غلق الاتصال
$conn->close();
?>