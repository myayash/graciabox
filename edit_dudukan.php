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
    die('Access Denied: You do not have permission to edit dudukan data.');
}

$dudukan = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing dudukan data
        $stmt = $pdo->prepare("SELECT id, jenis FROM dudukan WHERE id = ?");
        $stmt->execute([$id]);
        $dudukan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dudukan) {
            $message = "Dudukan not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching dudukan: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No dudukan ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating dudukan
if (isset($_POST['update_dudukan']) && $dudukan) {
    $jenis = trim($_POST['jenis']);

    if (!empty($jenis)) {
        try {
            $stmt = $pdo->prepare("UPDATE dudukan SET jenis = ? WHERE id = ?");
            $stmt->execute([$jenis, $dudukan['id']]);
            $message = "Dudukan updated successfully!";
            $message_type = 'success';
            header("Location: daftar_dudukan.php");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating dudukan: " . htmlspecialchars($e->getMessage());
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
    <title>Edit Dudukan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Dudukan</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ."\" role=\"alert\">" . $message . "</div>";
    }

    if ($dudukan) {
    ?>
        <form action="" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($dudukan['id']); ?>">
            <div class="mb-4">
                <label for="jenis" class="block text-gray-800 text-sm font-semibold mb-2">Jenis:</label>
                <input type="text" name="jenis" id="jenis" value="<?php echo htmlspecialchars($dudukan['jenis']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_dudukan" value="Update Dudukan" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="daftar_dudukan.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>