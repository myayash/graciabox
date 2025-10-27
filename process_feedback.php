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

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback_text = $_POST['feedback_text'];
    // Handle optional image upload
    $upload_dir = __DIR__ . '/uploads/feedback_images';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // make sure directory is writable; if not, try to make it writable
    if (!is_writable($upload_dir)) {
        @chmod($upload_dir, 0777);
    }

    $image_filename = null;
    $image_path = null;
    // Prepare a place to store any upload error to show to the user
    if (isset($_FILES['feedback_image'])) {
        $uploadError = $_FILES['feedback_image']['error'];
        if ($uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK) {
                $_SESSION['feedback_error'] = 'Upload error code: ' . $uploadError;
            } else {
                $file = $_FILES['feedback_image'];
                // Basic validation: max 2MB and allowed image mime types
                $maxSize = 2 * 1024 * 1024; // 2MB
                if ($file['size'] > $maxSize) {
                    $_SESSION['feedback_error'] = 'File too large. Max 2MB allowed.';
                } else {
                    // use finfo if available
                    $mime = null;
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
                            // store filename for DB and session, path for display
                            $image_filename = $filename;
                            $image_path = 'uploads/feedback_images/' . $filename;
                        } else {
                            $last = error_get_last();
                            $_SESSION['feedback_error'] = 'Failed to move uploaded file.' . ($last ? ' ' . $last['message'] : '');
                        }
                    }
                }
            }
        }
    }

    // Insert feedback text and image filename (if any) into DB
    $stmt = $pdo->prepare("INSERT INTO feedback_test (text, fb_img) VALUES (?, ?)");

    // Use the filename (or null) for the fb_img column
    $fb_img = $image_filename ? $image_filename : null;

    if ($stmt->execute([$feedback_text, $fb_img])) {
        // expose the uploaded image filename briefly via session so feedback.php can display it
        if ($image_filename) {
            $_SESSION['last_feedback_image'] = $image_filename;
        } else {
            unset($_SESSION['last_feedback_image']);
        }
        // if there was an upload error, keep it in session to show to user on the feedback page
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