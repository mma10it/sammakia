<?php
include 'header.php';
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$phone = $_POST['phone'];

$sql = "SELECT * FROM customers WHERE phone_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "العميل موجود:<br>";
    echo "اسم العميل: " . htmlspecialchars($row['customer_name']) . "<br>";
    echo "رقم الهاتف: " . htmlspecialchars($row['phone_number']) . "<br>";
} else {
    echo "العميل غير موجود.";
}
?>