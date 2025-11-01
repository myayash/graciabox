<main class="container mx-auto mt-10">
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="bikin_fo" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="text-center">
                <i class="fa-solid fa-plus fa-5x mx-auto mb-4 text-blue-600"></i>
                <h2 class="text-xl font-semibold mb-2">bikin FO baru</h2>
            </div>
        </a>

        <a href="daftar_fo" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="text-center">
                <i class="fa-solid fa-file fa-5x mx-auto mb-4 text-blue-600"></i>
                <h2 class="text-xl font-semibold mb-2">daftar FO</h2>
            </div>
        </a>

        <a href="daftar_customer" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="text-center">
                <i class="fa-solid fa-users fa-5x mx-auto mb-4 text-blue-600"></i>
                <h2 class="text-xl font-semibold mb-2">daftar customer</h2>
            </div>
        </a>

        <a href="daftar_spk" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="text-center">
                <i class="fa-solid fa-briefcase fa-5x mx-auto mb-4 text-blue-600"></i>
                <h2 class="text-xl font-semibold mb-2">daftar SPK</h2>
            </div>
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="daftar_fo" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
            <div class="text-center">
                <i class="fa-solid fa-list-ol fa-5x mx-auto mb-4 text-blue-600"></i>
                <h2 class="text-xl font-semibold mb-2">daftar FO</h2>
            </div>
        </a>
    </div>
    <?php endif; ?>
</main>
