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
    die('Access Denied: You do not have permission to add new barang.');
}

// Fetch data for dropdowns
$model_boxes = $pdo->query("SELECT nama FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model_box = trim($_POST['model_box']);
    $length = trim($_POST['length']);
    $width = trim($_POST['width']);
    $height = trim($_POST['height']);
    $nama = trim($_POST['nama']);

    if (!empty($model_box) && !empty($length) && !empty($width) && !empty($height)) {
        try {
            $ukuran = $length . ' x ' . $width . ' x ' . $height;
            $stmt = $pdo->prepare("INSERT INTO barang (model_box, ukuran, nama) VALUES (?, ?, ?)");
            $stmt->execute([$model_box, $ukuran, $nama]);
            header("Location: daftar_barang.php");
            exit;
        } catch (PDOException $e) {
            echo "<p class=\"mt-4 text-red-600\">Error adding barang: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class=\"mt-4 text-red-600\">Please fill in all required fields (Model Box, Length, Width, and Height).</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tambah barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">tambah barang</h1>

    <form action="bikin_barang.php" method="post" class="bg-white p-8 shadow-lg">
        <div class="mb-4">
            <label for="model_box" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
            <select name="model_box" id="model_box" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <option value="" disabled selected>Select Model Box</option>
                <?php foreach ($model_boxes as $mb): ?>
                    <option value="<?= htmlspecialchars($mb['nama']) ?>"><?= htmlspecialchars($mb['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Ukuran:</label>
            <div class="flex space-x-2">
                <input type="number" step="0.01" name="length" placeholder="Length" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <input type="number" step="0.01" name="width" placeholder="Width" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <input type="number" step="0.01" name="height" placeholder="Height" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Barang (Optional):</label>
            <input type="text" name="nama" id="nama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Save" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="daftar_barang.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</body>
</html>
