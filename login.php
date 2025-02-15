<?php
include 'header.php';
session_start();
$error_message = ""; // متغير لتخزين رسالة الخطأ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['اسم_المستخدم'];
    $password = $_POST['كلمة_المرور'];
    // الاتصال بقاعدة البيانات
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['user'] = $username;
        header("Location: dashboard.php");
        exit(); // توقف النصipt بعد إعادة التوجيه
    } else {
        $error_message = "اسم المستخدم أو كلمة المرور غير صحيحة."; // تخزين رسالة الخطأ
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
        <!-- إضافة الصورة هنا -->
<div style="text-align: center; margin: 20px 0;">
    <img src="logo.png" alt="شعار الموقع" style="max-width: 100%; height: auto; max-height: 98px;">
</div>
    <title>تسجيل الدخول</title>
</head>
<body>
    <h1>ادارة شكاوى العملاء</h1>
    <!-- عرض رسالة الخطأ فوق النموذج -->
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form method="post">
        اسم المستخدم: <input type="text" name="اسم_المستخدم" required><br><br>
        كلمة المرور: <input type="password" name="كلمة_المرور" required><br><br>
        <button type="submit">تسجيل الدخول</button>
    </form>

    <footer style="text-align: center; font-size: 14px; margin-top: 18px; color: rgb(0, 0, 0);">
    Developed by 
    <a href="copy_right.php" 
       style="color: inherit; text-decoration: none; background-color: rgb(199, 199, 199); padding: 4px 8px; border-radius: 4px; display: inline-block; height: 16px; line-height: 16px;">
        MMA|SNE
    </a> 
    ©2025.
</footer>
</html>    
    
