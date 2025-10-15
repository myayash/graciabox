<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $perusahaan = trim($_POST['perusahaan']);
    $no_telp = trim($_POST['no_telp']);

    if (!empty($nama) && !empty($no_telp)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO customer (nama, perusahaan, no_telp) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $perusahaan, $no_telp]);
            header("Location: daftar_customer.php");
            exit;
        } catch (PDOException $e) {
            echo "<p class=\"mt-4 text-red-600\">Error adding customer: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class=\"mt-4 text-red-600\">Please fill in all required fields (Nama and No. Telepon).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tambah customer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">tambah customer</h1>

    <form action="bikin_customer.php" method="post" class="bg-white p-8 shadow-lg">
        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Customer:</label>
            <input type="text" name="nama" id="nama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="mb-4">
            <label for="perusahaan" class="block text-gray-800 text-sm font-semibold mb-2">Perusahaan (Optional):</label>
            <input type="text" name="perusahaan" id="perusahaan" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        </div>

        <div class="mb-4">
            <label for="no_telp" class="block text-gray-800 text-sm font-semibold mb-2">No. Telepon:</label>
            <input type="text" name="no_telp" id="no_telp" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Save" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="daftar_customer.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</body>
</html>