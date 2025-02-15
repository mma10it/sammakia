<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// تضمين ملف الرأس
include 'header.php';
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// إضافة الأزرار
echo "<a href='dashboard.php'>الرئيسية</a> | 
      <a href='branches.php'>الفروع</a> | 
      <a href='customers.php'>العملاء</a> | 
      <a href='import_export.php' class='backup'>استيراد و تصدير</a> | 
      <a href='backup.php' class='backup'>نسخ احتياطي</a> | 
      <a href='logout.php' class='logout'>تسجيل الخروج</a>";

?>
<h1>إنشاء نسخة احتياطية</h1>

<!-- إنشاء نسخة احتياطية -->
<form method="post">
    <button type="submit" name="backup">إنشاء نسخة احتياطية</button>
</form>

<?php

$host = "localhost";
$dbname = "sammakia_cs";
$username = "sammakia_it";
$password = "IT@9200";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<p style='color:red;'>فشل الاتصال بقاعدة البيانات: " . $e->getMessage() . "</p>");
}

/**
 * دالة لإنشاء نسخة احتياطية لقاعدة البيانات
 */
function backupDatabase($pdo) {
    $timestamp = date('Y-m-d_H-i-s');
    $backupSQL = "";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $createTableStmt = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
        $backupSQL .= $createTableStmt['Create Table'] . ";\n\n"; // إلغاء DROP TABLE

        $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map([$pdo, 'quote'], array_values($row));
            $backupSQL .= "INSERT INTO `$table` VALUES(" . implode(", ", $values) . ");\n";
        }
        $backupSQL .= "\n";
    }

    // تحويل النص إلى ملف وتحميله مباشرة
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="backup_' . $timestamp . '.sql"');
    echo $backupSQL;
    exit;
}

// معالجة الطلبات
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['backup'])) {
        backupDatabase($pdo);
    }
}

?>

<footer style="text-align: center; font-size: 14px; margin-top: 18px; color: rgb(0, 0, 0);">
    Developed by 
    <a href="copy_right.php" 
       style="color: inherit; text-decoration: none; background-color: rgb(199, 199, 199); padding: 4px 8px; border-radius: 4px; display: inline-block; height: 16px; line-height: 16px;">
        MMA|SNE
    </a> 
    ©2025.
</footer>
</html>
