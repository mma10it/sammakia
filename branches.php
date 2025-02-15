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

$message = ""; // متغير لتخزين الرسالة

// تعامل مع إضافة فرع جديد
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_branch'])) {
    $branch_name = $_POST['branch_name'];
    $address = $_POST['address'];
    $notes = $_POST['notes'];
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    $check_sql = "SELECT * FROM branches WHERE branch_name = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $branch_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $message = "الفرع مسجل بالفعل.";
    } else {
        $insert_sql = "INSERT INTO branches (branch_name, address, notes) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sss", $branch_name, $address, $notes);
        $stmt->execute();
        $message = "تمت إضافة الفرع بنجاح.";
    }
    $stmt->close();
    $conn->close();
}

// تعامل مع طلب البحث
$search_results = [];
$show_all_branches = true; // عرض كل الفروع كافتراضي
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_branch'])) {
    $search_term = "%" . $_POST['search_term'] . "%";
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    $search_sql = "SELECT * FROM branches WHERE branch_name LIKE ?";
    $stmt = $conn->prepare($search_sql);
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        $show_all_branches = false; // عرض نتائج البحث بدلاً من كل الفروع
    } else {
        $message = "لم يتم العثور على فروع تطابق البحث.";
    }
    $stmt->close();
    $conn->close();
}
?>

<h2>إضافة فرع جديد</h2>
<!-- عرض الرسائل -->
<?php if (!empty($message)): ?>
    <p style="color: <?php echo strpos($message, 'نجاح') !== false ? 'green' : 'red'; ?>; font-weight: bold;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<!-- إضافة فرع جديد -->
<form method="post" action="">
    اسم الفرع: <input type="text" name="branch_name" required><br>
    العنوان: <input type="text" name="address"><br>
    ملاحظات: <textarea name="notes"></textarea><br>
    <button type="submit" name="add_branch">إضافة فرع</button>
</form>

<!-- إضافة الخط الأفقي -->
<hr>

<!-- نموذج البحث -->
<h2>بحث عن فرع</h2>
<form method="post" action="">
    اكتب اسم الفرع أو جزء منه: <input type="text" name="search_term" required>
    <button type="submit" name="search_branch">بحث</button>
    <!-- زر كل الفروع كرابط -->
    <a href="branches.php">كل الفروع</a>
</form>

<!-- عرض النتائج أو كل الفروع -->
<?php
if (!empty($search_results)) { // إذا كانت هناك نتائج للبحث
    echo "<h3>نتائج البحث:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>اسم الفرع</th><th>العنوان</th><th>ملاحظات</th><th>تفاصيل الشكاوى</th></tr>";
    foreach ($search_results as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['address']) . "</td>";
        echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
        echo "<td><a href='branch_complaints.php?branch=" . htmlspecialchars($row['branch_name']) . "'>عرض الشكاوى</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} elseif ($show_all_branches) { // إذا لم يكن هناك بحث، عرض كل الفروع
    $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
    $sql = "SELECT * FROM branches";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo "<h3>كل الفروع:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>اسم الفرع</th><th>العنوان</th><th>ملاحظات</th><th>تفاصيل الشكاوى</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['address']) . "</td>";
            echo "<td>" . htmlspecialchars($row['notes']) . "</td>";
            echo "<td><a href='branch_complaints.php?branch=" . htmlspecialchars($row['branch_name']) . "'>عرض الشكاوى</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا يوجد فروع حتى الآن.</p>";
    }
    $conn->close();
} elseif (isset($_POST['search_branch']) && empty($search_results)) { // إذا لم توجد نتائج للبحث
    echo "<p>لا توجد نتائج مطابقة للبحث.</p>";
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