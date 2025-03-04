<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'gardener') {
    header("Location: login_register.php");
    exit;
}

$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fetch sales statistics
$sales_stats = $db->query("
    SELECT 
        COUNT(*) AS total_sales,
        SUM(CASE WHEN product_id IS NOT NULL THEN price ELSE 0 END) AS total_product_sales,
        SUM(CASE WHEN service_id IS NOT NULL THEN price ELSE 0 END) AS total_service_sales,
        AVG(price) AS avg_spending_per_transaction
    FROM orders
    LEFT JOIN products ON orders.product_id = products.id
    LEFT JOIN services ON orders.service_id = services.id
")->fetch_assoc();

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
    <title>Gärtner-Dashboard</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <main class="bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-8">Gärtner-Dashboard</h1>
            <dl class="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-3">
                <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                    <dt class="text-base text-gray-600">Gesamtumsatz Produkte</dt>
                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl"><?php echo number_format($sales_stats['total_product_sales'], 2); ?> €</dd>
                </div>
                <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                    <dt class="text-base text-gray-600">Gesamtumsatz Dienstleistungen</dt>
                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl"><?php echo number_format($sales_stats['total_service_sales'], 2); ?> €</dd>
                </div>
                <div class="mx-auto flex max-w-xs flex-col gap-y-4">
                    <dt class="text-base text-gray-600">Durchschnittlicher Umsatz pro Transaktion</dt>
                    <dd class="order-first text-3xl font-semibold tracking-tight text-gray-900 sm:text-5xl"><?php echo number_format($sales_stats['avg_spending_per_transaction'], 2); ?> €</dd>
                </div>
            </dl>
        </div>
    </main>

    <footer class="bg-green-700 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>
