<?php
session_start();

// Database connection
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle Login
$login_error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows == 1 && password_verify($password, $hashed_password)) {
        $_SESSION['user'] = [
            'id' => $user_id,
            'role' => $role
        ];
        if ($role === 'customer') {
            header("Location: customer_dashboard.php");
        } elseif ($role === 'gardener') {
            header("Location: gardener_dashboard.php");
        }
    } else {
        $login_error = "Ungültiger Benutzername oder Passwort!";
    }

    $stmt->close();
}

// Handle Registration
$registration_error = '';
$registration_success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    if ($password !== $confirm_password) {
        $registration_error = "Passwörter stimmen nicht überein";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $registration_success = "Registrierung erfolgreich! Bitte melden Sie sich an.";
        } else {
            $registration_error = "Registrierung fehlgeschlagen: " . $stmt->error;
        }

        $stmt->close();
    }
}

$db->close();

function sanitizeFilename($string) {
    // Convert umlauts to their ASCII equivalents
    $transliterationTable = array(
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
        'ß' => 'ss',
        ' ' => '_'
    );

    // Replace special characters
    $sanitized = strtr($string, $transliterationTable);

    // Remove any remaining special characters
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    return strtolower($sanitized);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register Page</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="min-h-screen flex">
        <!-- Left Side - Auth Forms -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Form Container -->
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <!-- Logo -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <i class="fas fa-sign-in-alt text-green-600 fa-lg"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            Willkommen bei unserem Garten-Webshop
                        </h2>
                        <p class="text-gray-600 mt-2">
                            Melden Sie sich an oder registrieren Sie sich.
                        </p>
                    </div>

                    <!-- Login Form -->
                    <form action="login_register.php" method="POST" class="space-y-6" id="loginForm">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Benutzername</label>
                            <input
                                type="text"
                                name="username"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="Benutzername"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Passwort</label>
                            <div class="relative">
                                <input
                                    type="password"
                                    name="password"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                    placeholder="••••••••"
                                />
                            </div>
                            <?php if (isset($login_error)): ?>
                                <p class="mt-2 text-sm text-red-600"><?php echo $login_error; ?></p>
                            <?php endif; ?>
                        </div>

                        <button
                            type="submit"
                            name="login"
                            class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 focus:ring-4 focus:ring-green-600 focus:ring-opacity-50 transition-colors"
                        >
                            Anmelden
                        </button>

                        <p class="mt-6 text-center text-gray-600">
                            Noch kein Konto?
                            <button
                                type="button"
                                class="ml-1 text-green-600 hover:text-green-700 font-semibold focus:outline-none"
                                onclick="toggleForm('register')"
                            >
                                Registrieren
                            </button>
                        </p>
                    </form>

                    <!-- Registration Form -->
                    <form action="login_register.php" method="POST" class="space-y-6 hidden" id="registerForm">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Benutzername</label>
                            <input
                                type="text"
                                name="username"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="Benutzername"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Passwort</label>
                            <input
                                type="password"
                                name="password"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="••••••••"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Passwort bestätigen</label>
                            <input
                                type="password"
                                name="confirm_password"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="••••••••"
                            />
                            <?php if (isset($registration_error) && strpos($registration_error, 'Passwörter stimmen nicht überein') !== false): ?>
                                <p class="mt-2 text-sm text-red-600">Passwörter stimmen nicht überein</p>
                            <?php endif; ?>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rolle</label>
                            <select name="role" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                                <option value="customer">Kunde</option>
                                <option value="gardener">Gärtner</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            name="register"
                            class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 focus:ring-4 focus:ring-green-600 focus:ring-opacity-50 transition-colors"
                        >
                            Registrieren
                        </button>

                        <p class="mt-6 text-center text-gray-600">
                            Bereits ein Konto?
                            <button
                                type="button"
                                class="ml-1 text-green-600 hover:text-green-700 font-semibold focus:outline-none"
                                onclick="toggleForm('login')"
                            >
                                Anmelden
                            </button>
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side - Image -->
        <div
            class="hidden lg:block lg:w-1/2 bg-cover bg-center"
            style="background-image: url('https://images.unsplash.com/photo-1520412099551-62b6bafeb5bb?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')"
        >
            <div class="h-full bg-transparent bg-opacity-50 flex items-center justify-center">
                <div class="text-center text-white px-12">
                    <h2 class="text-4xl font-bold mb-6">Dein grüner Raum, unsere Auswahl.</h2>
                    <p class="text-xl">Entdecke unsere Auswahl an Gartenutensilien und Dienstleistungen.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>

    <script>
        function toggleForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            if (formType === 'register') {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
            } else if (formType === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
