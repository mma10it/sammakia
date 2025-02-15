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
// إضافة الأزرار
echo "<a href='dashboard.php'>الرئيسية</a> | 
      <a href='branches.php'>الفروع</a> | 
      <a href='customers.php'>العملاء</a> | 
      <a href='import_export.php' class='backup'>استيراد و تصدير</a> | 
      <a href='backup.php' class='backup'>نسخ احتياطي</a> | 
      <a href='logout.php' class='logout'>تسجيل الخروج</a>";
?>

<h1>تسجيل شكوى</h1>
<!-- حاوية لعرض رسائل النجاح والخطأ -->
<div id="messages" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; max-width: 600px; display: none;">
    <!-- الرسائل ستظهر هنا -->
</div>
<!-- نموذج تسجيل الشكوى -->
<form method="post" action="" class="complaint-form">
    <div class="complaint-fields">
        <!-- رقم الهاتف واسم الفرع في سطر واحد -->
        <div class="field-group">
            <label for="phone_number">رقم الهاتف (مُلزم):</label>
            <input type="text" name="phone_number" id="phone_number" required>
        </div>

        <div class="field-group">
            <label for="branch_name">اسم الفرع (مُلزم):</label>
            <input type="text" name="branch_name" id="branch_name" required>
        </div>

        <!-- عرض بيانات العميل والفرع -->
        <div id="customer_details"></div>
        <div id="branch_details"></div>

        <!-- مصدر الشكوى ونوع الشكوى -->
        <div class="field-group">
            <label for="complaint_source">مصدر الشكوى:</label>
            <input type="text" name="complaint_source">
        </div>

        <div class="field-group">
            <label for="complaint_type">نوع الشكوى:</label>
            <input type="text" name="complaint_type">
        </div>

        <!-- البراند وكود الصنف -->
        <div class="field-group">
            <label for="brand">البراند:</label>
            <input type="text" name="brand">
        </div>

        <div class="field-group">
            <label for="item_code">كود الصنف:</label>
            <input type="text" name="item_code">
        </div>

        <!-- رقم أوردر الفاتورة ورقم أوردر الاستبدال -->
        <div class="field-group">
            <label for="invoice_order_number">رقم أوردر الفاتورة:</label>
            <input type="text" name="invoice_order_number">
        </div>

        <div class="field-group">
            <label for="replacement_order_number">رقم أوردر الاستبدال:</label>
            <input type="text" name="replacement_order_number">
        </div>
    </div>

    <!-- حقل سبب الشكوى بمفرده -->
    <div class="complaint-reason">
        <label for="complaint_reason">سبب الشكوى (مُلزم):</label>
        <textarea name="complaint_reason" id="complaint_reason" required></textarea>
    </div>

    <button type="submit" name="submit_complaint">تسجيل الشكوى</button>
</form>

<!-- JavaScript للبحث عن العميل والفرع -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function () {
    // autocomplete لحقل اسم الفرع
    $("#branch_name").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "search_branches.php",
                type: "POST",
                dataType: "json",
                data: { term: request.term },
                success: function (data) {
                    response(data);
                }
            });
        },
        minLength: 3, // يتم عرض القائمة بعد كتابة 3 حروف
        select: function (event, ui) {
            $("#branch_name").val(ui.item.value); // تعيين القيمة المختارة
            return false;
        }
    });

    // autocomplete لحقل رقم الهاتف
    $("#phone_number").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "search_customers.php",
                type: "POST",
                dataType: "json",
                data: { term: request.term },
                success: function (data) {
                    response(data);
                }
            });
        },
        minLength: 3, // يتم عرض القائمة بعد كتابة 3 أرقام
        select: function (event, ui) {
            $("#phone_number").val(ui.item.value); // تعيين القيمة المختارة
            $("#customer_details").html("العميل: " + ui.item.label); // عرض بيانات العميل
            return false;
        }
    });
});
</script>

<?php
// معالجة تسجيل الشكوى
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_complaint'])) {
    $phone_number = $_POST['phone_number'];
    $branch_name = $_POST['branch_name'];
    $complaint_reason = $_POST['complaint_reason'];
    $complaint_source = $_POST['complaint_source'] ?? null;
    $complaint_type = $_POST['complaint_type'] ?? null;
    $brand = $_POST['brand'] ?? null;
    $item_code = $_POST['item_code'] ?? null;
    $invoice_order_number = $_POST['invoice_order_number'] ?? null;
    $replacement_order_number = $_POST['replacement_order_number'] ?? null;

    // التحقق من وجود العميل
    $sql_check_customer = "SELECT * FROM customers WHERE phone_number = ?";
    $stmt = $conn->prepare($sql_check_customer);
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        // عرض رسالة خطأ في الحاوية
        echo '<script>document.getElementById("messages").style.display = "block"; document.getElementById("messages").innerHTML = "<span style=\'color: red;\'>العميل غير موجود!</span>";</script>';
        exit(); // إيقاف التنفيذ إذا لم يكن العميل موجودًا
    }

    // التحقق من وجود الفرع والحصول على معرف الفرع
    $sql_check_branch = "SELECT branch_id FROM branches WHERE branch_name = ?";
    $stmt = $conn->prepare($sql_check_branch);
    $stmt->bind_param("s", $branch_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        // عرض رسالة خطأ في الحاوية
        echo '<script>document.getElementById("messages").style.display = "block"; document.getElementById("messages").innerHTML = "<span style=\'color: red;\'>الفرع غير موجود!</span>";</script>';
        exit(); // إيقاف التنفيذ إذا لم يكن الفرع موجودًا
    }
    $branch_id = $result->fetch_assoc()['branch_id'];

    // إضافة الشكوى
    $sql_add_complaint = "INSERT INTO complaints (
        customer_phone, branch_id, complaint_source, complaint_type, complaint_reason,
        brand, item_code, invoice_order_number, replacement_order_number
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_add_complaint);
    $stmt->bind_param(
        "sisssssss",
        $phone_number,
        $branch_id,
        $complaint_source,
        $complaint_type,
        $complaint_reason,
        $brand,
        $item_code,
        $invoice_order_number,
        $replacement_order_number
    );
    if ($stmt->execute()) {
        // عرض رسالة نجاح في الحاوية
        echo '<script>document.getElementById("messages").style.display = "block"; document.getElementById("messages").innerHTML = "<span style=\'color: green;\'>تم تسجيل الشكوى بنجاح.</span>";</script>';
    } else {
        // عرض رسالة خطأ في الحاوية
        echo '<script>document.getElementById("messages").style.display = "block"; document.getElementById("messages").innerHTML = "<span style=\'color: red;\'>حدث خطأ أثناء تسجيل الشكوى.</span>";</script>';
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