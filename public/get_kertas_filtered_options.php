<?php
include '../config/config.php';

header('Content-Type: application/json');

$response = [
    'jenis' => [],
    'warna' => [],
    'gsm' => [],
    'ukuran' => []
];

try {
    $supplier = $_GET['supplier'] ?? null;

    $base_where_clauses = ["is_archived = 0"];
    $base_params = [];

    if ($supplier) {
        $base_where_clauses[] = "supplier = ?";
        $base_params[] = $supplier;
    }

    // Fetch distinct 'jenis' based only on supplier
    $stmt = $pdo->prepare("SELECT DISTINCT jenis FROM kertas WHERE " . implode(" AND ", $base_where_clauses) . " ORDER BY jenis ASC");
    $stmt->execute($base_params);
    $response['jenis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch distinct 'warna' based only on supplier
    $stmt = $pdo->prepare("SELECT DISTINCT warna FROM kertas WHERE " . implode(" AND ", $base_where_clauses) . " ORDER BY warna ASC");
    $stmt->execute($base_params);
    $response['warna'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch distinct 'gsm' based only on supplier
    $stmt = $pdo->prepare("SELECT DISTINCT gsm FROM kertas WHERE " . implode(" AND ", $base_where_clauses) . " ORDER BY gsm ASC");
    $stmt->execute($base_params);
    $response['gsm'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch distinct 'ukuran' based only on supplier
    $stmt = $pdo->prepare("SELECT DISTINCT ukuran FROM kertas WHERE " . implode(" AND " , $base_where_clauses) . " ORDER BY ukuran ASC");
    $stmt->execute($base_params);
    $response['ukuran'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Error fetching filtered kertas options: " . $e->getMessage());
    // Return empty response on error
    $response = [
        'jenis' => [],
        'warna' => [],
        'gsm' => [],
        'ukuran' => []
    ];
}

echo json_encode($response);
?>