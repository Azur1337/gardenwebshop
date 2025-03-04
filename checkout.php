<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: login_register.php");
    exit;
}

$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$user_id = $_SESSION['user']['id'];

// Fetch products in cart
$cart_products = [];
$cart_services = [];
$subtotal = 0;

// Fetch cart items from the database
$cart_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_items = $cart_query->get_result()->fetch_all(MYSQLI_ASSOC);
$cart_query->close();

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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $zip = $_POST['zip'];
    $city = $_POST['city'];

    // Insert order into the database
    foreach ($cart_items as $item) {
        if (isset($item['product_id'])) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $product_price = $cart_products[array_search($item['product_id'], array_column($cart_products, 'product', 'product'))]['product']['price'];

            $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iddi", $user_id, $product_id, $product_price, $quantity);
            $stmt->execute();
            $stmt->close();
        } elseif (isset($item['service_id'])) {
            $service_id = $item['service_id'];
            $quantity = $item['quantity'];
            $service_price = $cart_services[array_search($item['service_id'], array_column($cart_services, 'service', 'service'))]['service']['price'];

            $stmt = $db->prepare("INSERT INTO orders (user_id, service_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iddi", $user_id, $service_id, $service_price, $quantity);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Clear cart after checkout
    $clear_cart_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $clear_cart_query->bind_param("i", $user_id);
    $clear_cart_query->execute();
    $clear_cart_query->close();

    $_SESSION['cart'] = [];

    header("Location: checkout_success.php");
    exit;
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
    <title>Checkout - Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <main class="bg-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-8">Bestellung bestätigen</h1>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8">
                <!-- Left Side - Order Summary -->
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Bestellübersicht</h2>
                    <div class="space-y-8">
                        <?php foreach ($cart_products as $item): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <a href="product_detail.php?id=<?php echo htmlspecialchars($item['product']['id']); ?>"><?php echo htmlspecialchars($item['product']['name']); ?></a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item['product']['description']); ?></p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product']['price']); ?> € x <?php echo htmlspecialchars($item['quantity']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($cart_services as $item): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <a href="service_detail.php?id=<?php echo htmlspecialchars($item['service']['id']); ?>"><?php echo htmlspecialchars($item['service']['name']); ?></a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item['service']['description']); ?></p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['service']['price']); ?> € x <?php echo htmlspecialchars($item['quantity']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-8">
                        <div class="flex justify-between text-base font-medium text-gray-900">
                            <p>Zwischensumme</p>
                            <p><?php echo number_format($subtotal, 2); ?> €</p>
                        </div>
                        <div class="flex justify-between text-base font-medium text-gray-900 mt-2">
                            <p>Versand</p>
                            <p>0.00 €</p>
                        </div>
                        <div class="flex justify-between text-base font-medium text-gray-900 mt-2">
                            <p>Gesamtsumme</p>
                            <p><?php echo number_format($subtotal, 2); ?> €</p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Shipping Information -->
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Versandinformationen</h2>
                    <form action="checkout.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vollständiger Name</label>
                            <input
                                type="text"
                                name="name"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="John Doe"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                            <input
                                type="text"
                                name="address"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="Musterstraße 123"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">PLZ</label>
                            <input
                                type="text"
                                name="zip"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="12345"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stadt</label>
                            <input
                                type="text"
                                name="city"
                                required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                placeholder="Musterstadt"
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full bg-green-600 text-white text-lg font-medium px-6 py-3 rounded-lg shadow hover:bg-green-700 focus:ring-4 focus:ring-green-600 focus:ring-opacity-50 transition-colors"
                        >
                            Bestellung abschließen
                        </button>
                    </form>
                </div>
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
