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
    <title>Kunden-Dashboard</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <main class="bg-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-8">Kunden-Dashboard</h1>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8">
                <!-- Left Side - Product List -->
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Produkte</h2>
                    <div class="space-y-8">
                        <?php foreach ($products as $product): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['price']); ?> €</p>
                                </div>
                                <div>
                                    <form action="customer_dashboard.php" method="GET" class="inline-block">
                                        <input type="hidden" name="add_to_cart" value="true" />
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>" />
                                        <label for="quantity_<?php echo htmlspecialchars($product['id']); ?>" class="sr-only">Menge</label>
                                        <input
                                            type="number"
                                            id="quantity_<?php echo htmlspecialchars($product['id']); ?>"
                                            name="quantity"
                                            min="1"
                                            value="1"
                                            class="w-20 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                        />
                                        <input type="hidden" name="quantity" id="quantity_input_<?php echo htmlspecialchars($product['id']); ?>" value="1" />
                                        <button type="submit" class="ml-2 bg-green-500 text-white px-4 py-2 rounded shadow hover:bg-green-600">In Warenkorb</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Side - Shopping Cart -->
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 mb-4">Warenkorb</h2>
                    <div class="bg-white rounded-2xl shadow-xl p-8">
                        <?php if (empty($cart_products) && empty($cart_services)): ?>
                            <p class="text-gray-600">Ihr Warenkorb ist leer.</p>
                        <?php else: ?>
                            <form action="customer_dashboard.php" method="POST">
                                <div class="flow-root">
                                    <ul role="list" class="-my-6 divide-y divide-gray-200">
                                        <!-- Products in Cart -->
                                        <?php foreach ($cart_products as $item): ?>
                                            <li class="flex py-6">
                                                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-gray-200">
                                                    <img src="./images/<?php echo htmlspecialchars(sanitizeFilename($item['product']['name'])); ?>.jpeg" alt="<?php echo htmlspecialchars($item['product']['name']); ?>" class="h-full w-full object-cover">
                                                </div>

                                                <div class="ml-4 flex flex-1 flex-col">
                                                    <div>
                                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                                            <h3>
                                                                <a href="product_detail.php?id=<?php echo htmlspecialchars($item['product']['id']); ?>"><?php echo htmlspecialchars($item['product']['name']); ?></a>
                                                            </h3>
                                                            <p class="ml-4"><?php echo htmlspecialchars($item['product']['price']); ?> €</p>
                                                        </div>
                                                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item['product']['description']); ?></p>
                                                    </div>
                                                    <div class="flex flex-1 items-end justify-between text-sm">
                                                        <div>
                                                            <label for="quantity_<?php echo htmlspecialchars($item['product']['id']); ?>" class="sr-only">Menge</label>
                                                            <input
                                                                type="number"
                                                                id="quantity_<?php echo htmlspecialchars($item['product']['id']); ?>"
                                                                name="quantity"
                                                                min="1"
                                                                value="<?php echo htmlspecialchars($item['quantity']); ?>"
                                                                class="w-20 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                                            />
                                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product']['id']); ?>" />
                                                        </div>

                                                        <div class="flex">
                                                            <button type="submit" name="update_quantity" class="font-medium text-green-600 hover:text-green-500">Aktualisieren</button>
                                                            <a href="customer_dashboard.php?remove_from_cart=true&product_id=<?php echo htmlspecialchars($item['product']['id']); ?>" class="ml-4 font-medium text-red-600 hover:text-red-500">Entfernen</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>

                                        <!-- Services in Cart -->
                                        <?php foreach ($cart_services as $item): ?>
                                            <li class="flex py-6">
                                                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-gray-200">
                                                    <img src="./images/<?php echo htmlspecialchars(sanitizeFilename($item['service']['name'])); ?>.jpeg" alt="<?php echo htmlspecialchars($item['service']['name']); ?>" class="h-full w-full object-cover">
                                                </div>

                                                <div class="ml-4 flex flex-1 flex-col">
                                                    <div>
                                                        <div class="flex justify-between text-base font-medium text-gray-900">
                                                            <h3>
                                                                <a href="service_detail.php?id=<?php echo htmlspecialchars($item['service']['id']); ?>"><?php echo htmlspecialchars($item['service']['name']); ?></a>
                                                            </h3>
                                                            <p class="ml-4"><?php echo htmlspecialchars($item['service']['price']); ?> €</p>
                                                        </div>
                                                        <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($item['service']['description']); ?></p>
                                                    </div>
                                                    <div class="flex flex-1 items-end justify-between text-sm">
                                                        <div>
                                                            <label for="quantity_<?php echo htmlspecialchars($item['service']['id']); ?>" class="sr-only">Menge</label>
                                                            <input
                                                                type="number"
                                                                id="quantity_<?php echo htmlspecialchars($item['service']['id']); ?>"
                                                                name="quantity"
                                                                min="1"
                                                                value="<?php echo htmlspecialchars($item['quantity']); ?>"
                                                                class="w-20 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                                            />
                                                            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($item['service']['id']); ?>" />
                                                        </div>

                                                        <div class="flex">
                                                            <button type="submit" name="update_quantity" class="font-medium text-green-600 hover:text-green-500">Aktualisieren</button>
                                                            <a href="customer_dashboard.php?remove_from_cart=true&service_id=<?php echo htmlspecialchars($item['service']['id']); ?>" class="ml-4 font-medium text-red-600 hover:text-red-500">Entfernen</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <div class="mt-8">
                                    <div class="flex justify-between text-base font-medium text-gray-900">
                                        <p>Zwischensumme</p>
                                        <p><?php echo number_format($subtotal, 2); ?> €</p>
                                    </div>
                                    <p class="mt-0.5 text-sm text-gray-500">Versand und Steuern werden bei der Kasse berechnet.</p>
                                    <div class="mt-6">
                                        <a href="checkout.php" class="flex items-center justify-center rounded-md border border-transparent bg-green-600 px-6 py-3 text-base font-medium text-white shadow-xs hover:bg-green-700">Zur Kasse</a>
                                    </div>
                                    <div class="mt-6 flex justify-center text-center text-sm text-gray-500">
                                        <p>
                                            oder
                                            <a href="products.php" class="font-medium text-green-600 hover:text-green-500">
                                                Weiter einkaufen
                                                <span aria-hidden="true"> &rarr;</span>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
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
