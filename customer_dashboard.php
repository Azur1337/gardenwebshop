<?php
session_start();

// Database connection
$db = new mysqli("localhost", "root", "1337", "garden_shop");

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$user_id = $_SESSION['user']['id'];

// Fetch products
$products_query = $db->query("SELECT * FROM products");
$products = $products_query->fetch_all(MYSQLI_ASSOC);
$products_query->close();

// Fetch services
$services_query = $db->query("SELECT * FROM services");
$services = $services_query->fetch_all(MYSQLI_ASSOC);
$services_query->close();

// Fetch products in cart
$cart_products = [];
$cart_services = [];
$subtotal = 0;

// Fetch cart items from the database
$cart_items_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
$cart_items_query->bind_param("i", $user_id);
$cart_items_query->execute();
$cart_items = $cart_items_query->get_result()->fetch_all(MYSQLI_ASSOC);
$cart_items_query->close();

foreach ($cart_items as $item) {
    if (isset($item['product_id'])) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $product_query = $db->prepare("SELECT * FROM products WHERE id = ?");
        $product_query->bind_param("i", $product_id);
        $product_query->execute();
        $product = $product_query->get_result()->fetch_assoc();
        $product_query->close();

        if ($product) {
            $cart_products[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
            $subtotal += $product['price'] * $quantity;
        }
    } elseif (isset($item['service_id'])) {
        $service_id = $item['service_id'];
        $quantity = $item['quantity'];

        $service_query = $db->prepare("SELECT * FROM services WHERE id = ?");
        $service_query->bind_param("i", $service_id);
        $service_query->execute();
        $service = $service_query->get_result()->fetch_assoc();
        $service_query->close();

        if ($service) {
            $cart_services[] = [
                'service' => $service,
                'quantity' => $quantity
            ];
            $subtotal += $service['price'] * $quantity;
        }
    }
}

// Add product to cart
if (isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

    // Check if the product is already in the cart
    $check_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
    $check_query->bind_param("ii", $user_id, $product_id);
    $check_query->execute();
    $existing_item = $check_query->get_result()->fetch_assoc();
    $check_query->close();

    if ($existing_item) {
        // Update the quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $product_id);
        $update_query->execute();
        $update_query->close();
    } else {
        // Insert the new item
        $insert_query = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert_query->bind_param("iii", $user_id, $product_id, $quantity);
        $insert_query->execute();
        $insert_query->close();
    }

    header("Location: customer_dashboard.php");
    exit;
}

// Add service to cart
if (isset($_GET['add_to_cart']) && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

    // Check if the service is already in the cart
    $check_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? AND service_id = ?");
    $check_query->bind_param("ii", $user_id, $service_id);
    $check_query->execute();
    $existing_item = $check_query->get_result()->fetch_assoc();
    $check_query->close();

    if ($existing_item) {
        // Update the quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND service_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $service_id);
        $update_query->execute();
        $update_query->close();
    } else {
        // Insert the new item
        $insert_query = $db->prepare("INSERT INTO cart_items (user_id, service_id, quantity) VALUES (?, ?, ?)");
        $insert_query->bind_param("iii", $user_id, $service_id, $quantity);
        $insert_query->execute();
        $insert_query->close();
    }

    header("Location: customer_dashboard.php");
    exit;
}

// Remove product from cart
if (isset($_GET['remove_from_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    $delete_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
    $delete_query->bind_param("ii", $user_id, $product_id);
    $delete_query->execute();
    $delete_query->close();

    header("Location: customer_dashboard.php");
    exit;
}

// Remove service from cart
if (isset($_GET['remove_from_cart']) && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];

    $delete_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND service_id = ?");
    $delete_query->bind_param("ii", $user_id, $service_id);
    $delete_query->execute();
    $delete_query->close();

    header("Location: customer_dashboard.php");
    exit;
}

// Update quantity in cart
if (isset($_POST['update_quantity'])) {
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $service_id = isset($_POST['service_id']) ? $_POST['service_id'] : null;
    $new_quantity = intval($_POST['quantity']);

    if ($product_id) {
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $product_id);
        $update_query->execute();
        $update_query->close();
    } elseif ($service_id) {
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND service_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $service_id);
        $update_query->execute();
        $update_query->close();
    }

    header("Location: customer_dashboard.php");
    exit;
}

// Fetch order history for the user
$order_history = [];
$order_query = $db->prepare("SELECT o.id AS order_id, o.order_date FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC");
$order_query->bind_param("i", $user_id);
$order_query->execute();
$orders = $order_query->get_result()->fetch_all(MYSQLI_ASSOC);
$order_query->close();

foreach ($orders as $order) {
    $order_id = $order['order_id'];
    $order_date = $order['order_date'];

    // Fetch items for each order
    $items_query = $db->prepare("SELECT oi.product_id, oi.service_id, oi.quantity, p.name AS product_name, s.name AS service_name, p.price AS product_price, s.price AS service_price FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id LEFT JOIN services s ON oi.service_id = s.id WHERE oi.order_id = ?");
    $items_query->bind_param("i", $order_id);
    $items_query->execute();
    $items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC);
    $items_query->close();

    $order_history[] = [
        'order_id' => $order_id,
        'order_date' => $order_date,
        'items' => $items
    ];
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunden-Dashboard</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">
<?php include 'navbar.php'; ?>
    <div class="container mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Kunden-Dashboard</h1>

        <!-- Products Section -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Produkte</h2>
            <ul>
                <?php foreach ($products as $product): ?>
                    <li class="flex justify-between py-2 border-b">
                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                        <span><?php echo number_format($product['price'], 2) . ' €'; ?></span>
                        <form action="?add_to_cart=1&product_id=<?php echo $product['id']; ?>" method="post" class="flex space-x-2">
                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">In Warenkorb</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Services Section -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Dienstleistungen</h2>
            <ul>
                <?php foreach ($services as $service): ?>
                    <li class="flex justify-between py-2 border-b">
                        <span><?php echo htmlspecialchars($service['name']); ?></span>
                        <span><?php echo number_format($service['price'], 2) . ' €'; ?></span>
                        <form action="?add_to_cart=1&service_id=<?php echo $service['id']; ?>" method="post" class="flex space-x-2">
                            <input type="number" name="quantity" value="1" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md">
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">In Warenkorb</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Cart Section -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Warenkorb</h2>
            <?php if (empty($cart_products) && empty($cart_services)): ?>
                <p>Ihr Warenkorb ist leer.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">Name</th>
                            <th class="p-2">Preis</th>
                            <th class="p-2">Menge</th>
                            <th class="p-2">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_products as $item): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($item['product']['name']); ?></td>
                                <td class="p-2"><?php echo number_format($item['product']['price'], 2) . ' €'; ?></td>
                                <td class="p-2">
                                    <form action="update_quantity.php" method="post" class="flex space-x-2">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md">
                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Aktualisieren</button>
                                    </form>
                                </td>
                                <td class="p-2">
                                    <a href="?remove_from_cart=1&product_id=<?php echo $item['product']['id']; ?>" class="text-red-600 hover:text-red-700">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($cart_services as $item): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($item['service']['name']); ?></td>
                                <td class="p-2"><?php echo number_format($item['service']['price'], 2) . ' €'; ?></td>
                                <td class="p-2">
                                    <form action="update_quantity.php" method="post" class="flex space-x-2">
                                        <input type="hidden" name="service_id" value="<?php echo $item['service']['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="w-16 px-2 py-1 border border-gray-300 rounded-md">
                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Aktualisieren</button>
                                    </form>
                                </td>
                                <td class="p-2">
                                    <a href="?remove_from_cart=1&service_id=<?php echo $item['service']['id']; ?>" class="text-red-600 hover:text-red-700">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-between font-bold">
                    <span>Zwischensumme</span>
                    <span><?php echo number_format($subtotal, 2) . ' €'; ?></span>
                </div>
                <a href="checkout.php" class="block mt-4 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 w-fit">Zur Kasse</a>
            <?php endif; ?>
        </div>

        <!-- Order History Section -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Bestellhistorie</h2>
            <?php if (empty($order_history)): ?>
                <p>Sie haben noch keine Bestellungen abgeschlossen.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2">Bestellnummer</th>
                            <th class="p-2">Datum</th>
                            <th class="p-2">Artikel</th>
                            <th class="p-2">Menge</th>
                            <th class="p-2">Preis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_history as $order): ?>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr class="border-b">
                                    <td class="p-2"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($item['product_name'] ?? $item['service_name']); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="p-2"><?php echo number_format(($item['product_price'] ?? $item['service_price']) * $item['quantity'], 2) . ' €'; ?></td>
                                </tr>
                            <?php endforeach; ?>
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
