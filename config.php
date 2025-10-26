<?php
$script_name = basename($_SERVER['PHP_SELF']);
if ($script_name !== 'login.php' && $script_name !== 'setup.php') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

$host = '127.0.0.1';
$dbname = 'project_form'; // Create this in phpMyAdmin if needed
$username = 'root';
$password = ''; // Default for XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if 'is_archived' column exists in 'customer' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM customer LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customer ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if 'is_archived' column exists in 'barang' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM barang LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE barang ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if 'is_archived' column exists in 'model_box' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM model_box LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE model_box ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if 'is_archived' column exists in 'board' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM board LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE board ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if 'is_archived' column exists in 'kertas' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM kertas LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE kertas ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }

    // Check if 'is_archived' column exists in 'empl_sales' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM empl_sales LIKE 'is_archived'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE empl_sales ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    }


    // Check if 'lokasi' column exists in 'orders' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM orders LIKE 'lokasi'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN lokasi VARCHAR(255) NOT NULL");
    }

    // Check if 'quantity' column exists in 'orders' table, add if not
    $stmt = $pdo->prepare("SHOW COLUMNS FROM orders LIKE 'quantity'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN quantity VARCHAR(255) NOT NULL");
    } else {
        $pdo->exec("ALTER TABLE orders MODIFY COLUMN quantity VARCHAR(255) NOT NULL");
    }

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>