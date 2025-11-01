<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Gracia Box Internal'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gray-100 text-gray-900 font-mono">
    <?php include 'navbar.php'; ?>
    <div class="container mx-auto pt-24 px-8 pb-8">
        <?php echo $content; ?>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/scripts.js"></script>
</body>
</html>