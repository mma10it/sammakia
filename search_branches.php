<?php
require_once 'db.php'; // تضمين ملف الاتصال بقاعدة البيانات

if (isset($_POST['term'])) {
    $term = $_POST['term'];
    $sql = "SELECT branch_name FROM branches WHERE branch_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $term = '%' . $term . '%';
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $result = $stmt->get_result();

    $branches = [];
    while ($row = $result->fetch_assoc()) {
        $branches[] = ['label' => $row['branch_name'], 'value' => $row['branch_name']];
    }

    echo json_encode($branches);
}
?>