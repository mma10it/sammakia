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

<h1>تفاصيل الشكوى</h1>

<?php
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// الحصول على معرف الشكوى من الرابط
$complaint_id = isset($_GET['id']) ? $_GET['id'] : '';

// التحقق من وجود الشكوى
$sql_complaint = "SELECT c.*, cu.customer_name, cu.phone_number, b.branch_name 
                  FROM complaints c 
                  JOIN customers cu ON c.customer_phone = cu.phone_number 
                  JOIN branches b ON c.branch_id = b.branch_id 
                  WHERE c.complaint_id = ?";
$stmt_complaint = $conn->prepare($sql_complaint);
$stmt_complaint->bind_param("i", $complaint_id);
$stmt_complaint->execute();
$result_complaint = $stmt_complaint->get_result();

if ($result_complaint->num_rows == 0) {
    echo "<div class='error'>الشكوى غير موجودة.</div>";
    exit();
}

$row_complaint = $result_complaint->fetch_assoc();

// عرض تفاصيل الشكوى
echo "<table border='1' style='margin: 20px auto; width: 80%; max-width: 600px; text-align: center;'>";
echo "<tr><th colspan='2'>تفاصيل الشكوى</th></tr>";

// بيانات العميل
echo "<tr><td>اسم العميل</td><td>" . htmlspecialchars($row_complaint['customer_name']) . "</td></tr>";
echo "<tr><td>رقم الهاتف</td><td>" . htmlspecialchars($row_complaint['phone_number']) . "</td></tr>";

// بيانات الفرع
echo "<tr><td>اسم الفرع</td><td>" . htmlspecialchars($row_complaint['branch_name']) . "</td></tr>";

// تفاصيل الشكوى
echo "<tr><td>التاريخ والوقت</td><td>" . htmlspecialchars($row_complaint['date_time']) . "</td></tr>";
echo "<tr><td>مصدر الشكوى</td><td>" . htmlspecialchars($row_complaint['complaint_source'] ?? '') . "</td></tr>";
echo "<tr><td>نوع الشكوى</td><td>" . htmlspecialchars($row_complaint['complaint_type'] ?? '') . "</td></tr>";
echo "<tr><td>سبب الشكوى</td><td>" . htmlspecialchars($row_complaint['complaint_reason']) . "</td></tr>";
echo "<tr><td>البراند</td><td>" . htmlspecialchars($row_complaint['brand'] ?? '') . "</td></tr>";
echo "<tr><td>كود الصنف</td><td>" . htmlspecialchars($row_complaint['item_code'] ?? '') . "</td></tr>";
echo "<tr><td>رقم أوردر الفاتورة</td><td>" . htmlspecialchars($row_complaint['invoice_order_number'] ?? '') . "</td></tr>";
echo "<tr><td>رقم أوردر الاستبدال</td><td>" . htmlspecialchars($row_complaint['replacement_order_number'] ?? '') . "</td></tr>";
echo "</table>";

// عرض جميع الردود على الشكوى (الأحدث أولاً)
echo "<h2>الردود على الشكوى:</h2>";
$sql_responses = "SELECT * FROM complaint_responses WHERE complaint_id = ? ORDER BY response_date_time DESC"; // ترتيب الأحدث أولاً
$stmt_responses = $conn->prepare($sql_responses);
$stmt_responses->bind_param("i", $complaint_id);
$stmt_responses->execute();
$result_responses = $stmt_responses->get_result();

if ($result_responses->num_rows > 0) {
    echo "<table border='1' style='margin: 20px auto; width: 80%; max-width: 600px; text-align: center;'>";
    echo "<tr>
            <th>التاريخ والوقت</th>
            <th>الرد</th>
            <th>الملاحظات</th>
          </tr>";

    while ($row_response = $result_responses->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row_response['response_date_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row_response['response']) . "</td>";
        echo "<td>" . htmlspecialchars($row_response['notes'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>لا توجد ردود على هذه الشكوى بعد.</div>";
}

// نموذج لإضافة رد جديد
echo "<h2>إضافة رد جديد:</h2>";
echo "<form method='post' action=''>";
echo "<label for='response'>الرد (مُلزم):</label><br>";
echo "<textarea name='response' id='response' required></textarea><br><br>";

echo "<label for='notes'>الملاحظات:</label><br>";
echo "<textarea name='notes' id='notes'></textarea><br><br>";

echo "<button type='submit' name='submit_response'>إرسال الرد</button>";
echo "</form>";

// معالجة إضافة رد جديد
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_response'])) {
    $response = $_POST['response'];
    $notes = $_POST['notes'] ?? null;
    // إضافة رد جديد إلى جدول complaint_responses
    $sql_add_response = "INSERT INTO complaint_responses (complaint_id, response, notes) VALUES (?, ?, ?)";
    $stmt_add_response = $conn->prepare($sql_add_response);
    $stmt_add_response->bind_param("iss", $complaint_id, $response, $notes);

    if ($stmt_add_response->execute()) {
        $_SESSION['success_message'] = "تم إضافة الرد بنجاح.";
    } else {
        $_SESSION['error_message'] = "حدث خطأ أثناء إضافة الرد.";
    }

    header("Location: complaint_details.php?id=" . $complaint_id);
    exit();
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