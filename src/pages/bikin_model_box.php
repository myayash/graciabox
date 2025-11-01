<?php



// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to add new model box.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $box_luar = trim($_POST['box_luar']);
    $box_dlm = trim($_POST['box_dlm']);

    if (!empty($nama)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO model_box (nama, box_luar, box_dlm) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $box_luar, $box_dlm]);
            header("Location: " . BASE_URL . "/daftar_model_box");
            exit;
        } catch (PDOException $e) {
            echo "<p class=\"mt-4 text-red-600\">Error adding model box: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class=\"mt-4 text-red-600\">Please fill in the Model Box Name.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tambah model box</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8">


    <h1 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800">tambah model box</h1>

    <form action="<?php echo BASE_URL; ?>/bikin_model_box" method="post" class="bg-white p-8 shadow-md rounded-lg max-w-md mx-auto">
        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Model Box:</label>
            <input type="text" name="nama" id="nama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md" required>
        </div>

        <div class="mb-4">
            <label for="box_luar" class="block text-gray-800 text-sm font-semibold mb-2">Box Luar:</label>
            <input type="text" name="box_luar" id="box_luar" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
        </div>

        <div class="mb-4">
            <label for="box_dlm" class="block text-gray-800 text-sm font-semibold mb-2">Box Dalam:</label>
            <input type="text" name="box_dlm" id="box_dlm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Save" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out rounded-md">
            <a href="<?php echo BASE_URL; ?>/daftar_model_box" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Cancel
            </a>
        </div>
    </form>

</body>
</html>