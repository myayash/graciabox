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
</head>
<body class="bg-gray-100 font-mono">
    <?php include 'navbar.php'; ?>
    <div class="container mx-auto p-8 mt-20">
        <h1 class="text-sm font-bold mb-4 opacity-25">> feedback</h1>
        <?php
        if (isset($_GET['status']) && $_GET['status'] == 'success') {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Berhasil!</strong>
                <span class="block sm:inline">Feedback sukses disampaikan.</span>
            </div>';
        }
        ?>
        <form action="process_feedback.php" method="post">
            <div class="mb-4">
                <label for="feedback_text" class="block text-gray-700 text-sm font-bold mb-2">Apa yang bisa diperbaiki?</label>
                <textarea name="feedback_text" id="feedback_text" rows="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
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