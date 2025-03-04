<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: login_register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellung erfolgreich - Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <main class="bg-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-8">Bestellung erfolgreich!</h1>
            <p class="text-gray-600">Vielen Dank für Ihre Bestellung. Sie erhalten bald eine Bestellbestätigung per E-Mail.</p>
            <div class="mt-8">
                <a href="index.php" class="bg-green-500 text-white text-lg font-medium px-6 py-3 rounded-lg shadow hover:bg-green-600 focus:ring-4 focus:ring-green-600 focus:ring-opacity-50 transition-colors">
                    Zurück zur Startseite
                </a>
            </div>
        </div>
    </main>

    <footer class="bg-green-700 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>
