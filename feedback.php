<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'tester') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js" defer></script>
</head>
<body class="bg-gray-100 font-mono">
    <?php include 'navbar.php'; ?>
    <div class="container mx-auto p-8 mt-20">
        <h1 class="text-sm font-bold mb-4 opacity-25">> feedback</h1>
        <?php
        if (isset($_GET['status']) && $_GET['status'] == 'success') {
            // show success message
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">';
            echo '<strong class="font-bold">Berhasil!</strong>';
            echo '<span class="block sm:inline">Feedback sukses disampaikan.</span>';

            // if an image was uploaded, display a small thumbnail
            if (isset($_SESSION['last_feedback_image']) && $_SESSION['last_feedback_image']) {
                $filename = htmlspecialchars($_SESSION['last_feedback_image'], ENT_QUOTES, 'UTF-8');
                $img = 'uploads/feedback_images/' . $filename;
                echo '<div class="mt-3">';
                echo '<img src="' . $img . '" alt="Uploaded image" class="max-w-xs max-h-48 rounded border" />';
                echo '</div>';
                // remove it from session so it only shows once
                unset($_SESSION['last_feedback_image']);
            }

            echo '</div>';
            echo '<script>
                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete("status");
                    window.history.replaceState({path: url.href}, "", url.href);
                }
            </script>';
        }
        // show upload or db error (kept in session by process_feedback.php)
        if (isset($_SESSION['feedback_error']) && $_SESSION['feedback_error']) {
            $err = htmlspecialchars($_SESSION['feedback_error'], ENT_QUOTES, 'UTF-8');
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">';
            echo '<strong class="font-bold">Error:</strong> <span class="block sm:inline">' . $err . '</span>';
            echo '</div>';
            unset($_SESSION['feedback_error']);
        }
        ?>
        <form action="process_feedback.php" method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="feedback_text" class="block text-gray-700 text-sm font-bold mb-2">Apa yang bisa diperbaiki?</label>
                <textarea name="feedback_text" id="feedback_text" rows="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
            </div>
            <div class="mb-4">
                <label for="feedback_image" class="block text-gray-700 text-sm font-bold mb-2">Sertakan gambar (opsional)</label>
                <input type="file" name="feedback_image" id="feedback_image" accept="image/*" class="block w-full text-sm text-gray-600" />
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline">
                    Submit Feedback
                </button>
            </div>
        </form>
    </div>
</body>
</html>