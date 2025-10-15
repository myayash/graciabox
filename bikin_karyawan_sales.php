<?php
include 'config.php';
session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to add new karyawan sales.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);

    if (!empty($nama)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO empl_sales (nama) VALUES (?)");
            $stmt->execute([$nama]);
            header("Location: daftar_karyawan_sales.php");
            exit;
        } catch (PDOException $e) {
            echo "<p class=\"mt-4 text-red-600\">Error adding karyawan sales: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class=\"mt-4 text-red-600\">Please fill in the Karyawan Sales Name.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Karyawan Sales</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Add New Karyawan Sales</h1>

    <form action="bikin_karyawan_sales.php" method="post" class="bg-white p-8 shadow-lg">
        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Karyawan Sales:</label>
            <input type="text" name="nama" id="nama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Add Karyawan Sales" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="daftar_karyawan_sales.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</body>
</html>
