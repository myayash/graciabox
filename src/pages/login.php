<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['password']) {
            // Password is correct, start the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to the main page
            header('Location: ' . BASE_URL);
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<div class="bg-white p-8 shadow-md w-full max-w-md mx-auto mt-20 rounded-lg">
    <h3 class="text-2xl font-bold mb-6 text-gray-800 text-center">gracia box portal login</h3>
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 relative mb-4 rounded-md"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="login" method="POST">
        <div class="mb-4">
            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
            <input type="text" class="shadow appearance-none border w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline rounded-md" id="username" name="username" required>
        </div>
        <div class="mb-6">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
            <input type="password" class="shadow appearance-none border w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline rounded-md" id="password" name="password" required>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline rounded-md">Login</button>
        </div>
    </form>
</div>
