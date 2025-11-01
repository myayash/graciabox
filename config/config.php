<?php
define('BASE_URL', '/gbox-deploy');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Request id for correlating logs (optional)
if (empty($_SERVER['REQUEST_ID'])) {
    $_SERVER['REQUEST_ID'] = bin2hex(random_bytes(8));
}

// initialize logger (Monolog). lib/logger.php will require vendor/autoload.php
if (file_exists(__DIR__ . '/../lib/logger.php')) {
    require_once __DIR__ . '/../lib/logger.php';
    try {
        $logger = get_logger('gbox');
        $logger->info('Config loaded', ['script' => $script_name ?? null]);
        // Expose request id for external correlation
        if (!headers_sent()) {
            header('X-Request-Id: ' . ($_SERVER['REQUEST_ID'] ?? ''));
        }
        try {
            $logger->info('Request started', [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
            ]);
        } catch (Throwable $inner) {
            // Don't allow logging here to break the app
            error_log('Request start log failed: ' . $inner->getMessage());
        }
    } catch (Throwable $e) {
        // if logger can't be initialized, fail gracefully (don't break the app)
        error_log('Logger init failed: ' . $e->getMessage());
    }
}

// Register global error and exception handlers to capture uncaught issues in logs
if (isset($logger) && $logger instanceof Psr\Log\LoggerInterface) {
    set_exception_handler(function ($e) use ($logger) {
        try {
            $logger->critical('Uncaught exception', ['exception' => (string)$e, 'trace' => $e->getTraceAsString()]);
        } catch (Throwable $inner) {
            error_log('Logging exception failed: ' . $inner->getMessage());
        }
        // Show a minimal error page for normal users but more details for the internal tester user
        if (!headers_sent()) http_response_code(500);
        $isTester = isset($_SESSION['username']) && $_SESSION['username'] === 'tester';
        if ($isTester) {
            // Detailed error for the tester account to speed up debugging in alpha
            echo '<h1>Internal error (debug)</h1>';
            echo '<pre>' . htmlspecialchars((string)$e) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        else {
            echo '<h1>Internal error</h1><p>An internal error occurred. The team has been notified.</p>';
        }
        exit(1);
    });

    set_error_handler(function ($severity, $message, $file, $line) use ($logger) {
        // Respect current error_reporting levels
        if (!(error_reporting() & $severity)) {
            return false;
        }
        try {
            $logger->error('PHP error', ['severity' => $severity, 'message' => $message, 'file' => $file, 'line' => $line]);
        } catch (Throwable $inner) {
            error_log('Logging error failed: ' . $inner->getMessage());
        }
        // Let PHP's internal handler run as well
        return false;
    });

    register_shutdown_function(function () use ($logger) {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            try {
                $logger->critical('Fatal shutdown', $err);
            } catch (Throwable $inner) {
                error_log('Logging shutdown failed: ' . $inner->getMessage());
            }
        }
    });
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