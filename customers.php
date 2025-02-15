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

<!-- نموذج إضافة عميل جديد -->
<h2>إضافة عميل جديد</h2>
<?php
// تعريف متغير لرسائل الخطأ أو النجاح
$error_message = "";
$success_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customer'])) {
    $customer_name = $_POST['customer_name'];
    $phone_number = $_POST['phone_number'];
    $notes = $_POST['notes'];
    // الاتصال بقاعدة البيانات
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    // التحقق من وجود رقم الهاتف في قاعدة البيانات
    $check_sql = "SELECT * FROM customers WHERE phone_number = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $error_message = "العميل مسجل بالفعل.";
    } else {
        // إذا لم يكن الرقم موجودًا، قم بإدخال العميل الجديد
        $insert_sql = "INSERT INTO customers (customer_name, phone_number, notes) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $customer_name, $phone_number, $notes);
        if ($stmt->execute()) {
            $success_message = "تمت إضافة العميل بنجاح.";
        } else {
            $error_message = "حدث خطأ أثناء إضافة العميل.";
        }
    }
    $stmt->close();
    $conn->close();
}

// تعامل مع طلب البحث
$search_results = [];
$show_all_customers = true; // عرض كل العملاء كافتراضي
$search_error_message = ""; // متغير لرسالة الخطأ عند البحث
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_customer'])) {
    $search_term = "%" . $_POST['search_term'] . "%";
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    $search_sql = "SELECT * FROM customers WHERE customer_name LIKE ? OR phone_number LIKE ?";
    $stmt = $conn->prepare($search_sql);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        $show_all_customers = false; // عرض نتائج البحث بدلاً من كل العملاء
    } else {
        $search_error_message = "لم يتم العثور على عملاء تطابق البحث."; // رسالة الخطأ
    }
    $stmt->close();
    $conn->close();
}
?>
<!-- عرض رسائل الخطأ أو النجاح -->
<?php if (!empty($error_message)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
<?php endif; ?>
<?php if (!empty($success_message)): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
<?php endif; ?>

<!-- نموذج إضافة عميل جديد -->
<form method="post" action="">
    اسم العميل: <input type="text" name="customer_name" required><br>
    رقم الهاتف: <input type="text" name="phone_number" required><br>
    ملاحظات: <textarea name="notes"></textarea><br>
    <button type="submit" name="add_customer">إضافة عميل</button>
</form>

<!-- إضافة الخط الأفقي -->
<hr>

<!-- نموذج البحث -->
<h2>بحث عن عميل</h2>
<!-- عرض رسالة الخطأ فوق نموذج البحث -->
<?php if (!empty($search_error_message)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($search_error_message); ?></p>
<?php endif; ?>
<form method="post" action="">
    اكتب اسم العميل أو رقم الهاتف: <input type="text" name="search_term" required>
    <button type="submit" name="search_customer">بحث</button>
    <!-- زر كل العملاء كرابط -->
    <a href="customers.php">كل العملاء</a>
</form>

<!-- عرض النتائج أو كل العملاء -->
<?php
if (!empty($search_results)) { // إذا كانت هناك نتائج للبحث
    echo "<h3>نتائج البحث:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>اسم العميل</th><th>رقم الهاتف</th><th>ملاحظات</th><th>تفاصيل الشكاوى</th></tr>";
    foreach ($search_results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
        echo "<td><a href='customer_complaints.php?phone=" . htmlspecialchars($row['phone_number']) . "'>عرض الشكاوى</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} elseif ($show_all_customers) { // إذا لم يكن هناك بحث، عرض كل العملاء
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    $sql = "SELECT * FROM customers";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo "<h3>كل العملاء:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>اسم العميل</th><th>رقم الهاتف</th><th>ملاحظات</th><th>تفاصيل الشكاوى</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
            echo "<td><a href='customer_complaints.php?phone=" . htmlspecialchars($row['phone_number']) . "'>عرض الشكاوى</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا يوجد عملاء حتى الآن.</p>";
    }
    $conn->close();
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