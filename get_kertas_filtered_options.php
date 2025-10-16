<?php
include 'config.php';

header('Content-Type: application/json');

$response = [];
$where_clauses = ["is_archived = 0"];
$params = [];

if (isset($_GET['field']) && isset($_GET['value'])) {
    $field = $_GET['field'];
    $value = $_GET['value'];

    // Add the current field and value to the where clause
    $where_clauses[] = "$field = ?";
    $params[] = $value;
}

// Add previous selections to filter
if (isset($_GET['supplier']) && $_GET['supplier'] !== '') {
    $where_clauses[] = "supplier = ?";
    $params[] = $_GET['supplier'];
}
if (isset($_GET['jenis']) && $_GET['jenis'] !== '') {
    $where_clauses[] = "jenis = ?";
    $params[] = $_GET['jenis'];
}
if (isset($_GET['warna']) && $_GET['warna'] !== '') {
    $where_clauses[] = "warna = ?";
    $params[] = $_GET['warna'];
}
if (isset($_GET['gsm']) && $_GET['gsm'] !== '') {
    $where_clauses[] = "gsm = ?";
    $params[] = $_GET['gsm'];
}

$target_field = $_GET['target_field'] ?? '';

if ($target_field !== '') {
    $query = "SELECT DISTINCT $target_field FROM kertas";
    if (!empty($where_clauses)) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $query .= " ORDER BY $target_field ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $response[] = $row[$target_field];
    }
}

echo json_encode($response);
?>