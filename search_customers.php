<?php
require_once 'db.php';

if (isset($_POST['term'])) {
    $term = $_POST['term'];
    $query = "SELECT phone_number FROM customers WHERE phone_number LIKE ? LIMIT 10";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$term%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = ["value" => $row['phone_number'], "label" => $row['phone_number']];
    }

    echo json_encode($data);
}
?>
