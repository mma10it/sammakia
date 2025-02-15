<?php
include 'header.php';
session_start();
// التحقق من تسجيل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// إضافة الأزرار الرئيسية
echo "<a href='dashboard.php'>الرئيسية</a> | 
      <a href='branches.php'>الفروع</a> | 
      <a href='customers.php'>العملاء</a> | 
      <a href='import_export.php' class='backup'>استيراد و تصدير</a> | 
      <a href='backup.php' class='backup'>نسخ احتياطي</a> | 
      <a href='logout.php' class='logout'>تسجيل الخروج</a>";


// استدعاء مكتبة PhpSpreadsheet
require 'vendor/autoload.php';

// استخدام IOFactory و Spreadsheet هنا في بداية الملف
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// تعريف متغيرات الرسائل
$error_message = "";
$success_message = "";
$duplicate_customers = []; // لحفظ العملاء المكررين

// 1. معالجة استيراد العملاء من ملف Excel
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['import_file']) && isset($_POST['import_customers'])) {
    $file = $_FILES['import_file'];

    // التحقق من صيغة الملف
    $allowed_extensions = ['xlsx', 'xls'];
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

    if (!in_array($file_extension, $allowed_extensions)) {
        $error_message = "يجب اختيار ملف بصيغة XLSX أو XLS.";
    } else {
        try {
            $inputFileName = $file['tmp_name'];
            $spreadsheet = IOFactory::load($inputFileName); // استخدام IOFactory هنا
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // الاتصال بقاعدة البيانات
            $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

            foreach ($rows as $row) {
                if (!empty($row[0]) && !empty($row[1])) { // فحص عدم وجود صفوف فارغة
                    $customer_name = trim($row[0]);
                    $phone_number = trim($row[1]);
                    $notes = isset($row[2]) ? trim($row[2]) : '';

                    // التحقق من وجود الرقم في قاعدة البيانات
                    $check_sql = "SELECT * FROM customers WHERE phone_number = ?";
                    $stmt = $conn->prepare($check_sql);
                    $stmt->bind_param("s", $phone_number);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // إضافة العميل إلى قائمة العملاء المكررين
                        $duplicate_customers[] = $customer_name . " (" . $phone_number . ")";
                        continue; // تخطي العميل إذا كان موجودًا
                    }

                    // إضافة العميل الجديد
                    $insert_sql = "INSERT INTO customers (customer_name, phone_number, notes) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($insert_sql);
                    $stmt->bind_param("sss", $customer_name, $phone_number, $notes);
                    $stmt->execute();
                }
            }

            if (!empty($duplicate_customers)) {
                $error_message = "العملاء التاليون مسجلون بالفعل: " . implode(", ", $duplicate_customers);
            } else {
                $success_message = "تم استيراد العملاء بنجاح.";
            }

            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            $error_message = "حدث خطأ أثناء استيراد الملف: " . $e->getMessage();
        }
    }
}

// 2. تصدير العملاء إلى Excel
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_customers'])) {
    try {
        // الاتصال بقاعدة البيانات
        $conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");
        if ($conn->connect_error) {
            die("خطأ في الاتصال بقاعدة البيانات: " . $conn->connect_error);
        }

        // استرجاع بيانات العملاء
        $sql = "SELECT customer_name, phone_number, notes FROM customers";
        $result = $conn->query($sql);

        if ($result->num_rows === 0) {
            die("لا يوجد عملاء لتصديرهم.");
        }

        // إنشاء ملف Excel جديد
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // إضافة رأس الجدول
        $sheet->setCellValue('A1', 'اسم العميل');
        $sheet->setCellValue('B1', 'رقم الهاتف');
        $sheet->setCellValue('C1', 'ملاحظات');

        // إدراج البيانات في الملف
        $row = 2;
        while ($data = $result->fetch_assoc()) {
            $sheet->setCellValue('A' . $row, $data['customer_name']);
            $sheet->setCellValue('B' . $row, $data['phone_number']);
            $sheet->setCellValue('C' . $row, $data['notes']);
            $row++;
        }

        // ضبط عرض الأعمدة تلقائيًا
        foreach (range('A', 'C') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // إغلاق الاتصال بقاعدة البيانات
        $conn->close();

        // تنظيف المخرجات
        ob_end_clean();
        ob_start();

        // ضبط هيدرات الملف
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Customers_Export.xlsx"');
        header('Cache-Control: max-age=0');

        // إنشاء كاتب الملف
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        // إنهاء السكريبت
        exit;

    } catch (Exception $e) {
        die("حدث خطأ أثناء تصدير الملف: " . $e->getMessage());
    }
}

?>

<!-- عرض رسائل الخطأ أو النجاح -->
<?php if (!empty($error_message)): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
<?php endif; ?>
<?php if (!empty($success_message)): ?>
    <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
<?php endif; ?>

<!-- زر استيراد العملاء -->
<h2>استيراد عملاء من ملف اكسل</h2>
<form method="post" action="" enctype="multipart/form-data">
    استيراد من ملف اكسل:
    <input type="file" name="import_file" accept=".xlsx, .xls" required>
    <button type="submit" name="import_customers">استيراد</button>
</form>

<!-- زر تصدير العملاء -->
<h2>تصدير عملاء إلى ملف اكسل</h2>
<form method="post" action="">
    <button type="submit" name="export_customers" formnovalidate>تصدير وحفظ ملف اكسل</button>
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