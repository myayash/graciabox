<?php
require_once 'config.php';

try {
    // Check if the users table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        die("Setup has already been completed. The 'users' table already exists. Please delete setup.php for security.");
    }

    // SQL to create users table
    $sql_create_table = "
    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";

    $pdo->exec($sql_create_table);
    echo "<p>Table 'users' created successfully.</p>";

    // Create a default admin user
    $admin_user = 'admin';
    // Generate a secure, random password
    $admin_password = bin2hex(random_bytes(8)); // Creates a 16-character hex password
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $admin_role = 'admin';

    $sql_insert_admin = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
    $stmt = $pdo->prepare($sql_insert_admin);

    $stmt->bindParam(':username', $admin_user);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $admin_role);

    $stmt->execute();

    echo "<h1>Setup Complete!</h1>";
    echo "<p>A default admin user has been created.</p>";
    echo "<p><b>Username:</b> " . htmlspecialchars($admin_user) . "</p>";
    echo "<p><b>Password:</b> <strong style='color:red;'>" . htmlspecialchars($admin_password) . "</strong></p>";
    echo "<p><strong>IMPORTANT:</strong> Please save this password somewhere safe. You will need it to log in.</p>";
    echo "<p>For security, please delete this file (setup.php) after you have saved the password.</p>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
