<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit kertas data.');
}

$kertas = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing kertas data
        $stmt = $pdo->prepare("SELECT id, supplier, jenis, warna, gsm, ukuran FROM kertas WHERE id = ?");
        $stmt->execute([$id]);
        $kertas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kertas) {
            $message = "Kertas not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching kertas: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No Kertas ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating kertas
if (isset($_POST['update_kertas']) && $kertas) {
    $supplier = trim($_POST['supplier']);
    $jenis = trim($_POST['jenis']);
    $warna = trim($_POST['warna']);
    $gsm = trim($_POST['gsm']);
    if (!empty($gsm) && substr($gsm, -4) !== ' gsm') {
        $gsm .= ' gsm';
    }
    $ukuran = trim($_POST['ukuran']);

    if (!empty($supplier) && !empty($jenis) && !empty($warna)) {
        try {
            $stmt = $pdo->prepare("UPDATE kertas SET supplier = ?, jenis = ?, warna = ?, gsm = ?, ukuran = ? WHERE id = ?");
            $stmt->execute([$supplier, $jenis, $warna, $gsm, $ukuran, $kertas['id']]);
            $message = "Kertas updated successfully!";
            $message_type = 'success';
            header("Location: daftar_kertas.php");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating kertas: " . htmlspecialchars($e->getMessage());
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
    <title>Edit Kertas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Kertas</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ."\" role=\"alert\">" . $message . "</div>";
    }

    if ($kertas) {
    ?>
        <form action="" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($kertas['id']); ?>">
            <div class="mb-4">
                <label for="supplier" class="block text-gray-800 text-sm font-semibold mb-2">Supplier:</label>
                <input type="text" name="supplier" id="supplier" value="<?php echo htmlspecialchars($kertas['supplier']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="mb-4">
                <label for="jenis" class="block text-gray-800 text-sm font-semibold mb-2">Jenis:</label>
                <input type="text" name="jenis" id="jenis" value="<?php echo htmlspecialchars($kertas['jenis']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="mb-4">
                <label for="warna" class="block text-gray-800 text-sm font-semibold mb-2">Warna:</label>
                <input type="text" name="warna" id="warna" value="<?php echo htmlspecialchars($kertas['warna']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="mb-4">
                <label for="gsm" class="block text-gray-800 text-sm font-semibold mb-2">GSM (Optional):</label>
                <input type="text" name="gsm" id="gsm" value="<?php echo htmlspecialchars($kertas['gsm']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="mb-4">
                <label for="ukuran" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran (Optional):</label>
                <input type="text" name="ukuran" id="ukuran" value="<?php echo htmlspecialchars($kertas['ukuran']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_kertas" value="Update Kertas" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="daftar_kertas.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>