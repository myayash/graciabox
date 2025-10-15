<?php
// Include config if needed for future DB ops (not used yet)
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>menu</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>

    <!-- Main Content: Menu Cards (like Adobe Illustrator welcome screen) -->
    <main class="container mx-auto mt-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: Create New Database -->
            <a href="bikin_fo.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto mb-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <h2 class="text-xl font-semibold mb-2">bikin FO baru</h2>
                </div>
            </a>

            <!-- Card 2: Browse Databases -->
            <a href="daftar_fo.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto mb-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l9-4 9 4M3 7h18" />
                    </svg>
                    <h2 class="text-xl font-semibold mb-2">daftar FO</h2>
                </div>
            </a>

            <!-- Card 3: Quick Query -->
            <a href="daftar_customer.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto mb-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.124-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.124-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h2 class="text-xl font-semibold mb-2">daftar customer</h2>
                </div>
            </a>

            <!-- Add more cards as needed, e.g., Import/Export, Users, etc. -->
        </div>
    </main>

</body>
</html>