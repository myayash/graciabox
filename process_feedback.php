<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'tester') {
    header("Location: index.php");
    exit();
}

// Load config with a protective wrapper so config-time errors are reported back to the feedback page
try {
    require 'config.php';
} catch (Throwable $e) {
    // If config fails to load, write to PHP error_log and to a fallback file in logs/ for diagnosis
    $msg = "config.php include failed: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    error_log($msg);
    @file_put_contents(__DIR__ . '/logs/config-failure.log', date('c') . " " . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    // Provide a user-visible error via session and redirect back to feedback form
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['feedback_error'] = 'Internal config error. Please contact the dev team.';
    header('Location: feedback.php?status=error');
    exit();
}

// optional logger from config
$logger = $logger ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback_text = $_POST['feedback_text'];
    if ($logger) {
        try {
            $logger->info('Received feedback POST', ['user_id' => $_SESSION['user_id'] ?? null, 'username' => $_SESSION['username'] ?? null, 'text_snippet' => substr($feedback_text,0,120)]);
        } catch (Throwable $logEx) {
            error_log('Logger->info failed: ' . $logEx->getMessage());
        }
    }
    // Handle optional image upload
    $upload_dir = __DIR__ . '/uploads/feedback_images';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // make sure directory is writable; if not, try to make it writable
    if (!is_writable($upload_dir)) {
        @chmod($upload_dir, 0777);
    }

    $image_filenames = [];
    $image_path = null;
    // Prepare a place to store any upload error to show to the user
    if (isset($_FILES['feedback_image'])) {
        // support multiple files (feedback_image[])
        $names = $_FILES['feedback_image']['name'];
        if (is_array($names)) {
            $count = count($names);
            if ($count > 3) {
                // Only allow up to 3 files
                $_SESSION['feedback_error'] = 'Maximum 3 files allowed. You selected ' . $count . '.';
            }
            $processCount = min(3, $count);
            for ($i = 0; $i < $processCount; $i++) {
                $uploadError = $_FILES['feedback_image']['error'][$i];
                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $_SESSION['feedback_error'] = 'Upload error code: ' . $uploadError;
                    continue;
                }
                $tmpName = $_FILES['feedback_image']['tmp_name'][$i];
                $size = $_FILES['feedback_image']['size'][$i];
                // Basic validation: max 2MB and allowed image mime types
                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($size > $maxSize) {
                    $_SESSION['feedback_error'] = 'File too large. Max 2MB allowed.';
                    continue;
                }
                // use finfo if available
                if (function_exists('finfo_open')) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($tmpName);
                } else {
                    $mime = mime_content_type($tmpName);
                }
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp'
                ];
                if (!isset($allowed[$mime])) {
                    $_SESSION['feedback_error'] = 'Invalid image type. Allowed: jpg, png, gif, webp.';
                    continue;
                }
                $ext = $allowed[$mime];
                try {
                    $basename = bin2hex(random_bytes(8));
                } catch (Exception $e) {
                    $basename = uniqid();
                }
                $filename = $basename . '.' . $ext;
                $target = $upload_dir . '/' . $filename;
                if (move_uploaded_file($tmpName, $target)) {
                    $image_filenames[] = $filename;
                    // last set image_path for possible use/display (not stored in DB)
                    $image_path = 'uploads/feedback_images/' . $filename;
                    if ($logger) {
                        try {
                            $logger->info('Saved uploaded feedback image', ['filename' => $filename, 'target' => $target]);
                        } catch (Throwable $logEx) {
                            error_log('Logger->info failed: ' . $logEx->getMessage());
                        }
                    }
                } else {
                    $last = error_get_last();
                    $errMsg = 'Failed to move uploaded file.' . ($last ? ' ' . $last['message'] : '');
                    $_SESSION['feedback_error'] = $errMsg;
                    if ($logger) {
                        try {
                            $logger->error('move_uploaded_file failed', ['tmp' => $tmpName, 'target' => $target, 'error' => $last]);
                        } catch (Throwable $logEx) {
                            error_log('Logger->error failed: ' . $logEx->getMessage());
                        }
                    }
                }
            }
        } else {
            // fallback: single file (older behavior)
            $uploadError = $_FILES['feedback_image']['error'];
            if ($uploadError !== UPLOAD_ERR_NO_FILE) {
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $_SESSION['feedback_error'] = 'Upload error code: ' . $uploadError;
                } else {
                    $file = $_FILES['feedback_image'];
                    $maxSize = 2 * 1024 * 1024;
                    if ($file['size'] > $maxSize) {
                        $_SESSION['feedback_error'] = 'File too large. Max 2MB allowed.';
                    } else {
                        if (function_exists('finfo_open')) {
                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->file($file['tmp_name']);
                        } else {
                            $mime = mime_content_type($file['tmp_name']);
                        }
                        $allowed = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/gif' => 'gif',
                            'image/webp' => 'webp'
                        ];
                        if (!isset($allowed[$mime])) {
                            $_SESSION['feedback_error'] = 'Invalid image type. Allowed: jpg, png, gif, webp.';
                        } else {
                            $ext = $allowed[$mime];
                            try {
                                $basename = bin2hex(random_bytes(8));
                            } catch (Exception $e) {
                                $basename = uniqid();
                            }
                            $filename = $basename . '.' . $ext;
                            $target = $upload_dir . '/' . $filename;
                                        if (move_uploaded_file($file['tmp_name'], $target)) {
                                            $image_filenames[] = $filename;
                                            $image_path = 'uploads/feedback_images/' . $filename;
                                            if ($logger) {
                                                try {
                                                    $logger->info('Saved uploaded feedback image (single)', ['filename' => $filename, 'target' => $target]);
                                                } catch (Throwable $logEx) {
                                                    error_log('Logger->info failed: ' . $logEx->getMessage());
                                                }
                                            }
                                        } else {
                                            $last = error_get_last();
                                            $errMsg = 'Failed to move uploaded file.' . ($last ? ' ' . $last['message'] : '');
                                            $_SESSION['feedback_error'] = $errMsg;
                                            if ($logger) {
                                                try {
                                                    $logger->error('move_uploaded_file failed (single)', ['tmp' => $file['tmp_name'], 'target' => $target, 'error' => $last]);
                                                } catch (Throwable $logEx) {
                                                    error_log('Logger->error failed: ' . $logEx->getMessage());
                                                }
                                            }
                                        }
                        }
                    }
                }
            }
        }
    }

    // Insert feedback text and image filename(s) (if any) into DB
    $stmt = $pdo->prepare("INSERT INTO feedback_test (text, fb_img) VALUES (?, ?)");

    // Store comma-separated filenames (or null) in fb_img
    $fb_img = count($image_filenames) ? implode(',', $image_filenames) : null;

    if ($stmt->execute([$feedback_text, $fb_img])) {
        // expose the uploaded image filenames briefly via session so feedback.php can display them
        if (count($image_filenames)) {
            $_SESSION['last_feedback_image'] = $image_filenames;
        } else {
            unset($_SESSION['last_feedback_image']);
        }
        // redirect back to feedback page; any upload errors will still be in session
        header("Location: feedback.php?status=success");
    } else {
        // DB insert failed; store error and show
        $_SESSION['feedback_error'] = 'DB error: ' . $stmt->errorInfo()[2];
        header("Location: feedback.php?status=error");
    }
} else {
    header("Location: index.php");
    exit();
}
?>