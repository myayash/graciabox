<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit barang data.');
}

$barang = null;
$message = '';
$message_type = '';

// Fetch data for dropdowns
$model_boxes = $pdo->query("SELECT nama FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing barang data
        $stmt = $pdo->prepare("SELECT id, model_box, ukuran, nama FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$barang) {
            $message = "Barang not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching barang: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No barang ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating barang
if (isset($_POST['update_barang']) && $barang) {
    $model_box = trim($_POST['model_box']);
    $ukuran = trim($_POST['ukuran']);
    $nama = trim($_POST['nama']);

    if (!empty($model_box) && !empty($ukuran)) {
        try {
            $stmt = $pdo->prepare("UPDATE barang SET model_box = ?, ukuran = ?, nama = ? WHERE id = ?");
            $stmt->execute([$model_box, $ukuran, $nama, $barang['id']]);
            $message = "Barang updated successfully!";
            $message_type = 'success';
            header("Location: daftar_barang.php");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating barang: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Barang</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "\" role=\"alert\">" . $message . "</div>";
    }

    if ($barang) {
    ?>
        <form action="" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($barang['id']); ?>">
            <div class="mb-4">
                <label for="model_box" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
                <select name="model_box" id="model_box" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled>Select Model Box</option>
                    <?php foreach ($model_boxes as $mb): ?>
                        <option value="<?= htmlspecialchars($mb['nama']) ?>" <?= ($barang['model_box'] == $mb['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($mb['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="ukuran" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran:</label>
                <input type="text" name="ukuran" id="ukuran" value="<?php echo htmlspecialchars($barang['ukuran']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="mb-4">
                <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Barang (Optional):</label>
                <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($barang['nama']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_barang" value="Update Barang" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="daftar_barang.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>