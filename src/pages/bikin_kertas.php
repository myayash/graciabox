<?php
require_once __DIR__ . '/../../config/config.php';
session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to add new kertas.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier = trim($_POST['supplier']);
    $jenis = trim($_POST['jenis']);
    $warna = trim($_POST['warna']);
    $gsm = trim($_POST['gsm']);
    if (!empty($gsm)) {
        $gsm .= ' gsm';
    }
    $ukuran = trim($_POST['ukuran']);

    if (!empty($supplier) && !empty($jenis) && !empty($warna)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kertas (supplier, jenis, warna, gsm, ukuran) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$supplier, $jenis, $warna, $gsm, $ukuran]);
            header("Location: daftar_kertas");
            exit;
        } catch (PDOException $e) {
            echo "<p class=\"mt-4 text-red-600\">Error adding kertas: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class=\"mt-4 text-red-600\">Please fill in all required fields (Supplier, Jenis, Warna).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tambah jenis kertas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8">

    <?php include __DIR__ . '/../Views/partials/navbar.php'; ?>
    <h1 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800">tambah jenis kertas</h1>

    <form action="bikin_kertas" method="post" class="bg-white p-8 shadow-md rounded-lg max-w-md mx-auto">
        <div class="mb-4">
            <label for="supplier" class="block text-gray-800 text-sm font-semibold mb-2">Supplier:</label>
            <input type="text" name="supplier" id="supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md" required>
        </div>

        <div class="mb-4">
            <label for="jenis" class="block text-gray-800 text-sm font-semibold mb-2">Jenis:</label>
            <input type="text" name="jenis" id="jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md" required>
        </div>

        <div class="mb-4">
            <label for="warna" class="block text-gray-800 text-sm font-semibold mb-2">Warna:</label>
            <input type="text" name="warna" id="warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md" required>
        </div>

        <div class="mb-4">
            <label for="gsm" class="block text-gray-800 text-sm font-semibold mb-2">GSM (Optional):</label>
            <input type="text" name="gsm" id="gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
        </div>

        <div class="mb-4">
            <label for="ukuran" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran (Optional):</label>
            <input type="text" name="ukuran" id="ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Save" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
            <a href="daftar_kertas" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</body>
</html>
