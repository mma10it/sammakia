<?php
// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>