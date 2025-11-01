<?php
require_once 'config.php';

$supplier = $_GET['supplier'] ?? null;
$jenis = $_GET['jenis'] ?? null;
$warna = $_GET['warna'] ?? null;
$gsm = $_GET['gsm'] ?? null;

$response = [];

$query = "SELECT DISTINCT jenis FROM kertas WHERE is_archived = 0";
if ($supplier) {
    $query .= " AND supplier = :supplier";
}
$stmt = $pdo->prepare($query);
if ($supplier) {
    $stmt->bindParam(':supplier', $supplier);
}
$stmt->execute();
$response['jenis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT warna FROM kertas WHERE is_archived = 0";
if ($supplier) {
    $query .= " AND supplier = :supplier";
}
if ($jenis) {
    $query .= " AND jenis = :jenis";
}
$stmt = $pdo->prepare($query);
if ($supplier) {
    $stmt->bindParam(':supplier', $supplier);
}
if ($jenis) {
    $stmt->bindParam(':jenis', $jenis);
}
$stmt->execute();
$response['warna'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT gsm FROM kertas WHERE is_archived = 0";
if ($supplier) {
    $query .= " AND supplier = :supplier";
}
if ($jenis) {
    $query .= " AND jenis = :jenis";
}
if ($warna) {
    $query .= " AND warna = :warna";
}
$stmt = $pdo->prepare($query);
if ($supplier) {
    $stmt->bindParam(':supplier', $supplier);
}
if ($jenis) {
    $stmt->bindParam(':jenis', $jenis);
}
if ($warna) {
    $stmt->bindParam(':warna', $warna);
}
$stmt->execute();
$response['gsm'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT ukuran FROM kertas WHERE is_archived = 0";
if ($supplier) {
    $query .= " AND supplier = :supplier";
}
if ($jenis) {
    $query .= " AND jenis = :jenis";
}
if ($warna) {
    $query .= " AND warna = :warna";
}
if ($gsm) {
    $query .= " AND gsm = :gsm";
}
$stmt = $pdo->prepare($query);
if ($supplier) {
    $stmt->bindParam(':supplier', $supplier);
}
if ($jenis) {
    $stmt->bindParam(':jenis', $jenis);
}
if ($warna) {
    $stmt->bindParam(':warna', $warna);
}
if ($gsm) {
    $stmt->bindParam(':gsm', $gsm);
}
$stmt->execute();
$response['ukuran'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

header('Content-Type: application/json');
echo json_encode($response);
?>