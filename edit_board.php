<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

$board = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing board data
        $stmt = $pdo->prepare("SELECT id, jenis FROM board WHERE id = ?");
        $stmt->execute([$id]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$board) {
            $message = "Board not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching board: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No Board ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating board
if (isset($_POST['update_board']) && $board) {
    $jenis = trim($_POST['jenis']);

    if (!empty($jenis)) {
        try {
            $stmt = $pdo->prepare("UPDATE board SET jenis = ? WHERE id = ?");
            $stmt->execute([$jenis, $board['id']]);
            $message = "Board updated successfully!";
            $message_type = 'success';
            header("Location: daftar_board.php");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating board: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in the Board Type.";
        $message_type = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Board</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "\" role=\"alert\">" . $message . "</div>";
    }

    if ($board) {
    ?>
        <form action="" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($board['id']); ?>">
            <div class="mb-4">
                <label for="jenis" class="block text-gray-800 text-sm font-semibold mb-2">Jenis Board:</label>
                <input type="text" name="jenis" id="jenis" value="<?php echo htmlspecialchars($board['jenis']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_board" value="Update Board" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="daftar_board.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>