<?php
include 'header.php';
$conn = new mysqli("localhost", "sammakia_it", "IT@9200", "sammakia_cs");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$branch = $_POST['branch'];

$sql = "SELECT * FROM branches WHERE branch_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $branch);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "الفرع موجود:<br>";
    echo "اسم الفرع: " . htmlspecialchars($row['branch_name']) . "<br>";
    echo "العنوان: " . htmlspecialchars($row['address']) . "<br>";
} else {
    echo "الفرع غير موجود.";
}
?>