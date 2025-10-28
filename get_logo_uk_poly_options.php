<?php
include 'config.php';

header('Content-Type: application/json');

$logo = $_GET['logo'] ?? '';

if (empty($logo)) {
    echo json_encode(['uk_poly' => []]);
    exit;
}

$stmt = $pdo->prepare("SELECT DISTINCT uk_poly FROM logo WHERE jenis = ? AND uk_poly IS NOT NULL AND uk_poly != '' ORDER BY uk_poly ASC");
$stmt->execute([$logo]);
$uk_poly_options = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['uk_poly' => $uk_poly_options]);

?>