<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit model box data.');
}

$model_box = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing model box data
        $stmt = $pdo->prepare("SELECT id, nama, box_luar, box_dlm FROM model_box WHERE id = ?");
        $stmt->execute([$id]);
        $model_box = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$model_box) {
            $message = "Model Box not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching model box: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No Model Box ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating model box
if (isset($_POST['update_model_box']) && $model_box) {
    $nama = trim($_POST['nama']);
    $box_luar = trim($_POST['box_luar']);
    $box_dlm = trim($_POST['box_dlm']);

    if (!empty($nama)) {
        try {
            $stmt = $pdo->prepare("UPDATE model_box SET nama = ?, box_luar = ?, box_dlm = ? WHERE id = ?");
            $stmt->execute([$nama, $box_luar, $box_dlm, $model_box['id']]);
            $message = "Model Box updated successfully!";
            $message_type = 'success';
            header("Location: " . BASE_URL . "/daftar_model_box");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating model box: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in the Model Box Name.";
        $message_type = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Model Box</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">


    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Model Box</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ."\" role=\"alert\">" . $message . "</div>";
    }

    if ($model_box) {
    ?>
        <form action="<?php echo BASE_URL; ?>/edit_model_box?id=<?php echo htmlspecialchars($model_box['id']); ?>" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($model_box['id']); ?>">
            <div class="mb-4">
                <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Model Box:</label>
                <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($model_box['nama']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="mb-4">
                <label for="box_luar" class="block text-gray-800 text-sm font-semibold mb-2">Box Luar:</label>
                <input type="text" name="box_luar" id="box_luar" value="<?php echo htmlspecialchars($model_box['box_luar']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="mb-4">
                <label for="box_dlm" class="block text-gray-800 text-sm font-semibold mb-2">Box Dalam:</label>
                <input type="text" name="box_dlm" id="box_dlm" value="<?php echo htmlspecialchars($model_box['box_dlm']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_model_box" value="Update Model Box" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="<?php echo BASE_URL; ?>/daftar_model_box" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>