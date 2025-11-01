<?php
include 'config.php';

header('Content-Type: application/json');

$jenis = $_GET['jenis'] ?? '';

if (empty($jenis)) {
    echo json_encode(['ukuran' => [], 'warna' => []]);
    exit;
}

$stmt_ukuran = $pdo->prepare("SELECT DISTINCT ukuran FROM aksesoris WHERE jenis = ? AND ukuran IS NOT NULL AND ukuran != '' ORDER BY ukuran ASC");
$stmt_ukuran->execute([$jenis]);
$ukuran_options = $stmt_ukuran->fetchAll(PDO::FETCH_COLUMN);

$stmt_warna = $pdo->prepare("SELECT DISTINCT warna FROM aksesoris WHERE jenis = ? AND warna IS NOT NULL AND warna != '' ORDER BY warna ASC");
$stmt_warna->execute([$jenis]);
$warna_options = $stmt_warna->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['ukuran' => $ukuran_options, 'warna' => $warna_options]);
?>
