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
        // If redirected after successful submission, pass flash data to client via JS globals
        if (isset($_GET['status']) && $_GET['status'] == 'success') {
            $flashMessage = 'Feedback sukses disampaikan.';
            $lastImgs = null;
            if (isset($_SESSION['last_feedback_image']) && $_SESSION['last_feedback_image']) {
                $lastImgs = $_SESSION['last_feedback_image'];
            }
            // unset server session copy so it only shows once
            unset($_SESSION['last_feedback_image']);
            // expose variables to client-side JS
            echo '<script>window.__flashSuccess = ' . json_encode($flashMessage) . '; window.__lastFeedbackImages = ' . json_encode($lastImgs) . ';</script>';
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

        <!-- Success Modal (hidden by default) -->
        <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
            <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
                <h3 class="text-lg font-semibold mb-2">Berhasil</h3>
                <p id="successModalMessage" class="mb-4 text-gray-700">Feedback sukses disampaikan.</p>
                <div id="successModalImages" class="grid grid-cols-3 gap-2 mb-4"></div>
                <div class="flex justify-end">
                    <button id="successModalClose" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">OK</button>
                </div>
            </div>
        </div>
        <form action="process_feedback.php" method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="feedback_text" class="block text-gray-700 text-sm font-bold mb-2">Ada saran atau keluhan?</label>
                <textarea name="feedback_text" id="feedback_text" rows="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
            </div>
            <div class="mb-4">
                <label for="feedback_image" class="block text-gray-700 text-sm font-bold mb-2">Sertakan gambar (opsional)</label>
                <input type="file" name="feedback_image[]" id="feedback_image" accept="image/*" class="block w-full text-sm text-gray-600" multiple />
                <p class="text-xs text-gray-500 mt-1">3 images max (2MB each).</p>
                <!-- Preview container: show thumbnails immediately after selecting files -->
                <div id="feedback_image_preview" class="mt-3 grid grid-cols-3 gap-2"></div>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline">
                    Submit Feedback
                </button>
            </div>
        </form>
        <script>
        // Client-side preview: show thumbnail under the file input as soon as a file is selected
        (function(){
            const input = document.getElementById('feedback_image');
            const preview = document.getElementById('feedback_image_preview');
            if (!input || !preview) return;
            input.addEventListener('change', function(){
                preview.innerHTML = '';
                const files = Array.from(this.files || []);
                if (files.length === 0) return;
                const maxFiles = 3;
                if (files.length > maxFiles) {
                    const p = document.createElement('p');
                    p.className = 'text-sm text-red-600';
                    p.textContent = 'You selected more than ' + maxFiles + ' files. Only the first ' + maxFiles + ' will be shown.';
                    preview.appendChild(p);
                }
                files.slice(0,3).forEach(function(file){
                    if (!file.type.startsWith('image/')) {
                        const p = document.createElement('p');
                        p.className = 'text-sm text-red-600';
                        p.textContent = file.name + ': not an image.';
                        preview.appendChild(p);
                        return;
                    }
                    const maxSize = 2 * 1024 * 1024;
                    if (file.size > maxSize) {
                        const p = document.createElement('p');
                        p.className = 'text-sm text-red-600';
                        p.textContent = file.name + ': too large (max 2MB).';
                        preview.appendChild(p);
                        return;
                    }
                    const wrap = document.createElement('div');
                    const img = document.createElement('img');
                    img.className = 'w-full h-auto rounded border';
                    img.alt = file.name;
                    img.src = URL.createObjectURL(file);
                    img.onload = function(){ URL.revokeObjectURL(this.src); };
                    wrap.appendChild(img);
                    preview.appendChild(wrap);
                });
            });
        })();
        </script>
        <script>
        // Success modal behavior (show when server set window.__flashSuccess)
        (function(){
            const successModal = document.getElementById('successModal');
            const successModalMessage = document.getElementById('successModalMessage');
            const successModalImages = document.getElementById('successModalImages');
            const successModalClose = document.getElementById('successModalClose');

            function showSuccessModal(message, images) {
                if (message) successModalMessage.textContent = message;
                if (images && Array.isArray(images) && images.length) {
                    successModalImages.innerHTML = '';
                    images.slice(0,3).forEach(function(fn){
                        if (!fn) return;
                        const img = document.createElement('img');
                        img.src = 'uploads/feedback_images/' + fn;
                        img.alt = fn;
                        img.className = 'w-full h-auto rounded border';
                        const wrap = document.createElement('div');
                        wrap.appendChild(img);
                        successModalImages.appendChild(wrap);
                    });
                } else {
                    successModalImages.innerHTML = '';
                }
                successModal.classList.remove('hidden');
                successModalClose && successModalClose.focus();
            }

            function hideSuccessModal(){
                successModal.classList.add('hidden');
            }

            // Trigger if server set the global
            if (window.__flashSuccess) {
                showSuccessModal(window.__flashSuccess, window.__lastFeedbackImages || []);
            }

            successModalClose && successModalClose.addEventListener('click', hideSuccessModal);
            successModal && successModal.addEventListener('click', function(event){ if (event.target === successModal) hideSuccessModal(); });
            document.addEventListener('keydown', function(event){ if (event.key === 'Escape' && !successModal.classList.contains('hidden')) hideSuccessModal(); });
        })();
        </script>
    </div>
</body>
</html>