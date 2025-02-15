<?php
include 'header.php'; 
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
}

// إضافة الأزرار
echo "<a href='dashboard.php'>الرئيسية</a> | 
      <a href='branches.php'>الفروع</a> | 
      <a href='customers.php'>العملاء</a> | 
      <a href='import_export.php' class='backup'>استيراد و تصدير</a> | 
      <a href='backup.php' class='backup'>نسخ احتياطي</a> | 
      <a href='logout.php' class='logout'>تسجيل الخروج</a>";

?>

<h1>شكاوى العميل</h1>

<?php
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على رقم الهاتف من الرابط
$phone_number = isset($_GET['phone']) ? $_GET['phone'] : '';

// التحقق من وجود العميل
$sql_customer = "SELECT * FROM customers WHERE phone_number = ?";
$stmt_customer = $conn->prepare($sql_customer);
$stmt_customer->bind_param("s", $phone_number);
$stmt_customer->execute();
$result_customer = $stmt_customer->get_result();

if ($result_customer->num_rows == 0) {
    echo "العميل غير موجود.";
    exit();
}

$row_customer = $result_customer->fetch_assoc();

// عرض بيانات العميل
echo "<h2>بيانات العميل:</h2>";
echo "اسم العميل: " . htmlspecialchars($row_customer['customer_name']) . "<br>";
echo "رقم الهاتف: " . htmlspecialchars($row_customer['phone_number']) . "<br>";
echo "ملاحظات: " . htmlspecialchars($row_customer['notes']) . "<br>";

// عرض الشكاوى المرتبة حسب التاريخ والوقت (الأحدث أولاً)
$sql_complaints = "SELECT c.*, b.branch_name 
                   FROM complaints c 
                   JOIN branches b ON c.branch_id = b.branch_id 
                   WHERE c.customer_phone = ? 
                   ORDER BY c.date_time DESC"; // 
$stmt_complaints = $conn->prepare($sql_complaints);
$stmt_complaints->bind_param("s", $phone_number);
$stmt_complaints->execute();
$result_complaints = $stmt_complaints->get_result();

if ($result_complaints->num_rows > 0) {
    echo "<h2>جدول الشكاوى:</h2>";
    echo "<table border='1'>";
    echo "<tr>
            <th>التاريخ والوقت</th>
            <th>اسم الفرع</th>
            <th>مصدر الشكوى</th>
            <th>نوع الشكوى</th>
            <th>سبب الشكوى</th>
            <th>البراند</th>
            <th>كود الصنف</th>
            <th>رقم أوردر الفاتورة</th>
            <th>رقم أوردر الاستبدال</th>
            <th>تفاصيل</th>
          </tr>";

    while ($row_complaint = $result_complaints->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row_complaint['date_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['branch_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['complaint_source'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['complaint_type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['complaint_reason']) . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['brand'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['item_code'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['invoice_order_number'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['replacement_order_number'] ?? '') . "</td>";
        echo "<td><a href='complaint_details.php?id=" . htmlspecialchars($row_complaint['complaint_id']) . "'>عرض التفاصيل</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "لا توجد شكاوى مرتبطة بهذا العميل.";
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