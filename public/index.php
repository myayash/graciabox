<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Basic routing
$url = 'dashboard'; // Default page

if (isset($_GET['page'])) {
    $url = $_GET['page'];
} elseif (isset($_GET['url'])) {
    $url = rtrim($_GET['url'], '/');
}

// Access control
$allowed_for_viewer = [
    'daftar_fo',
    'login',
    'logout',
    'get_aksesoris_options',
    'get_kertas_filtered_options',
    'get_kertas_options',
    'get_model_box_details',
    'view_fo_pdf',
];

if ($url !== 'login' && $url !== 'setup' && !isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'viewer' && !in_array($url, $allowed_for_viewer)) {
    header('Location: ' . BASE_URL . '/daftar_fo');
    exit();
}

// Prevent double .php and set page path
$url = str_replace('.php', '', $url);
$page = $url . '.php';
$page_path = __DIR__ . '/../src/pages/' . $page;

// A list of pages that should be served raw, without the HTML layout
$raw_pages = [
    'view_fo_pdf',
    'export_fo_excel',
    'view_spk_logo_pdf',
    'view_spk_pdf'
];

if (in_array($url, $raw_pages)) {
    if (file_exists($page_path)) {
        include $page_path;
        exit;
    } else {
        // Handle 404 for raw pages
        http_response_code(404);
        echo '404 - Page Not Found';
        exit;
    }
}

// HTML head
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/scripts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

<?php
if ($url !== 'login') {
    include __DIR__ . '/../src/Views/partials/navbar.php';
}

// Load the page content
if (file_exists($page_path)) {
    include $page_path;
} else {
    // Simple 404 page
    echo '<div class="container mx-auto mt-10 text-center"><h1 class="text-2xl font-bold">404 - Page Not Found</h1></div>';
}
?>

</body>
</html>