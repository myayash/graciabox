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
    die('Access Denied: You do not have permission to edit karyawan sales data.');
}

$karyawan_sales = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing karyawan sales data
        $stmt = $pdo->prepare("SELECT id, nama FROM empl_sales WHERE id = ?");
        $stmt->execute([$id]);
        $karyawan_sales = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$karyawan_sales) {
            $message = "Karyawan Sales not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching karyawan sales: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No Karyawan Sales ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating karyawan sales
if (isset($_POST['update_karyawan_sales']) && $karyawan_sales) {
    $nama = trim($_POST['nama']);

    if (!empty($nama)) {
        try {
            $stmt = $pdo->prepare("UPDATE empl_sales SET nama = ? WHERE id = ?");
            $stmt->execute([$nama, $karyawan_sales['id']]);
            $message = "Karyawan Sales updated successfully!";
            $message_type = 'success';
            header("Location: " . BASE_URL . "/daftar_karyawan_sales");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating karyawan sales: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in the Karyawan Sales Name.";
        $message_type = 'error';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan Sales</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">


    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Karyawan Sales</h1>

    <?php
    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ."\" role=\"alert\">" . $message . "</div>";
    }

    if ($karyawan_sales) {
    ?>
        <form action="<?php echo BASE_URL; ?>/edit_karyawan_sales?id=<?php echo htmlspecialchars($karyawan_sales['id']); ?>" method="POST" class="bg-white p-8 shadow-lg">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($karyawan_sales['id']); ?>">
            <div class="mb-4">
                <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Karyawan Sales:</label>
                <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($karyawan_sales['nama']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_karyawan_sales" value="Update Karyawan Sales" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="<?php echo BASE_URL; ?>/daftar_karyawan_sales" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>
        </form>
    <?php
    }
    ?>

</body>
</html>