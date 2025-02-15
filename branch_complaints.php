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

<h1>شكاوى الفرع</h1>

<?php
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على اسم الفرع من الرابط
$branch_name = isset($_GET['branch']) ? $_GET['branch'] : '';

// التحقق من وجود الفرع
$sql_branch = "SELECT * FROM branches WHERE branch_name = ?";
$stmt_branch = $conn->prepare($sql_branch);
$stmt_branch->bind_param("s", $branch_name);
$stmt_branch->execute();
$result_branch = $stmt_branch->get_result();

if ($result_branch->num_rows == 0) {
    echo "الفرع غير موجود.";
    exit();
}

$row_branch = $result_branch->fetch_assoc();

// عرض بيانات الفرع
echo "<h2>بيانات الفرع:</h2>";
echo "اسم الفرع: " . htmlspecialchars($row_branch['branch_name']) . "<br>";
echo "العنوان: " . htmlspecialchars($row_branch['address']) . "<br>";
echo "ملاحظات: " . htmlspecialchars($row_branch['notes']) . "<br>";

// الحصول على معرف الفرع (branch_id)
$branch_id = $row_branch['branch_id'];

// عرض الشكاوى المرتبطة بالفرع مع ترتيب الأحدث أولاً
$sql_complaints = "SELECT c.*, cu.customer_name 
                   FROM complaints c 
                   JOIN customers cu ON c.customer_phone = cu.phone_number 
                   WHERE c.branch_id = ? 
                   ORDER BY c.date_time DESC"; // 
$stmt_complaints = $conn->prepare($sql_complaints);
$stmt_complaints->bind_param("i", $branch_id);
$stmt_complaints->execute();
$result_complaints = $stmt_complaints->get_result();

if ($result_complaints->num_rows > 0) {
    echo "<h2>جدول الشكاوى:</h2>";
    echo "<table border='1'>";
    echo "<tr>
            <th>التاريخ والوقت</th>
            <th>اسم العميل</th>
            <th>رقم الهاتف</th>
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
        echo "<td>" . htmlspecialchars($row_complaint['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row_complaint['customer_phone']) . "</td>";
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
    echo "لا توجد شكاوى مرتبطة بهذا الفرع.";
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