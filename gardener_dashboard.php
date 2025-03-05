<?php
session_start();

// Database connection
$db = new mysqli("localhost", "root", "1337", "garden_shop");

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fetch total orders, total income, and average spending
$total_orders_query = $db->query("SELECT COUNT(*) AS total_orders FROM orders");
$total_orders = $total_orders_query->fetch_assoc()['total_orders'];
$total_orders_query->close();

$total_income_query = $db->query("
    SELECT SUM(oi.quantity * COALESCE(p.price, s.price)) AS total_income
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN services s ON oi.service_id = s.id
");
$total_income = $total_income_query->fetch_assoc()['total_income'] ?? 0;
$total_income_query->close();

$average_spending_query = $db->query("
    SELECT AVG(order_total) AS average_spending
    FROM (
        SELECT SUM(oi.quantity * COALESCE(p.price, s.price)) AS order_total
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN services s ON oi.service_id = s.id
        GROUP BY oi.order_id
    ) AS order_totals
");
$average_spending = $average_spending_query->fetch_assoc()['average_spending'] ?? 0;
$average_spending_query->close();

// Fetch services booked, sorted by oldest booking first
$services_booked_query = $db->query("
    SELECT o.order_date, s.name AS service_name, oi.quantity
    FROM order_items oi
    JOIN services s ON oi.service_id = s.id
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.service_id IS NOT NULL
    ORDER BY o.order_date ASC
");
$services_booked = $services_booked_query->fetch_all(MYSQLI_ASSOC);
$services_booked_query->close();

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gardener Dashboard</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">
  <?php include 'navbar.php'; ?>
    <div class="container mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Gärtner-Dashboard</h1>

        <!-- Statistics Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <!-- Total Orders -->
            <div class="bg-green-600 hover:bg-green-500 hover:scale-105 transition-all duration-150 text-white p-4 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">Anzahl der Bestellungen</p>
                    <p class="text-xl font-bold"><?php echo htmlspecialchars($total_orders); ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>

            <!-- Total Income -->
            <div class="bg-green-600 hover:bg-green-500 hover:scale-105 transition-all duration-150 text-white p-4 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">Gesamtumsatz</p>
                    <p class="text-xl font-bold"><?php echo number_format($total_income, 2) . ' €'; ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2zm0-8c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2-1.343-2-3-2z" />
                </svg>
            </div>

            <!-- Average Spending -->
            <div class="bg-green-600 hover:bg-green-500 hover:scale-105 transition-all duration-150 text-white p-4 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">Durchschnittliche Ausgaben pro Bestellung</p>
                    <p class="text-xl font-bold"><?php echo number_format($average_spending, 2) . ' €'; ?></p>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Services Booked Section -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Buchungen von Dienstleistungen</h2>
            <?php if (empty($services_booked)): ?>
                <p>Es wurden noch keine Dienstleistungen gebucht.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-green-100">
                            <th class="p-2">Dienstleistung</th>
                            <th class="p-2">Menge</th>
                            <th class="p-2">Bestelldatum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services_booked as $service): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($service['quantity']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($service['order_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>

</body>
</html>
