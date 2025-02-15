<?php
include 'header.php';
session_start();
// إذا لم يكن المستخدم مسجلًا، يتم إعادة توجيهه إلى صفحة تسجيل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'db.php';

// إضافة الأزرار في أعلى الصفحة
echo "<a href='dashboard.php'>الرئيسية</a> | 
      <a href='branches.php'>الفروع</a> | 
      <a href='customers.php'>العملاء</a> | 
      <a href='import_export.php' class='backup'>استيراد و تصدير</a> | 
      <a href='backup.php' class='backup'>نسخ احتياطي</a> | 
      <a href='logout.php' class='logout'>تسجيل الخروج</a>";
      
?>

<h1>حقوق الملكية</h1>

<!-- نص اتفاقية الاستخدام -->
<div style="text-align: center; margin: 20px; font-size: 16px;">
    <p>
        1. هذا البرنامج مصمم خصيصا لشركة امبراطور ولا يجوز نقله او نسخة
    </p>
    <p>
        2. تم استخدام لغات البرمجة PHP . CSS . JAVA Script . AJAX MY SQLdb
    </p>
    <p>
        3. جميع الحقوق محفوظة لدى المبرمجين. محمد عبدربه - صالح نبيل
    </p>
</div>

<footer style="text-align: center; font-size: 14px; margin-top: 18px; color: rgb(0, 0, 0);">
    Developed by 
    <a href="copy_right.php" 
       style="color: inherit; text-decoration: none; background-color: rgb(199, 199, 199); padding: 4px 8px; border-radius: 4px; display: inline-block; height: 16px; line-height: 16px;">
        MMA|SNE
    </a> 
    ©2025.
</footer>
</html>