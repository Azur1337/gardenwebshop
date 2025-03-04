<?php
session_start();

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add product to cart
if (isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $_SESSION['cart'][$product_id] = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] + 1 : 1;
}

// Remove product from cart
if (isset($_GET['remove_from_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Calculate subtotal
$subtotal = 0;
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $product_query = $db->prepare("SELECT * FROM products WHERE id = ?");
    $product_query->bind_param("i", $product_id);
    $product_query->execute();
    $product = $product_query->get_result()->fetch_assoc();
    $product_query->close();
    if ($product) {
        $subtotal += $product['price'] * $quantity;
    }
}
?>

<div class="relative z-10 hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"></div>

    <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                        <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                            <div class="flex items-start justify-between">
                                <h2 class="text-lg font-medium text-gray-900" id="slide-over-title">Warenkorb</h2>
                                <div class="ml-3 flex h-7 items-center">
                                    <button type="button" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500" onclick="toggleCart()">
                                        <span class="absolute -inset-0.5"></span>
                                        <span class="sr-only">Panel schließen</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-8">
                                <div class="flow-root">
                                    <ul role="list" class="-my-6 divide-y divide-gray-200">
                                        <?php foreach ($_SESSION['cart'] as $product_id => $quantity): ?>
                                            <?php
                                            $product_query = $db->prepare("SELECT * FROM products WHERE id = ?");
                                            $product_query->bind_param("i", $product_id);
                                            $product_query->execute();
                                            $product = $product_query->get_result()->fetch_assoc();
                                            $product_query->close();
                                            ?>
                                            <?php if ($product): ?>
                                                <li class="flex py-6">
                                                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-md border border-gray-200">
                                                        <img src="./images/<?php echo htmlspecialchars(sanitizeFilename($product['name'])); ?>.jpeg" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-full w-full object-cover">
                                                    </div>

                                                    <div class="ml-4 flex flex-1 flex-col">
                                                        <div>
                                                            <div class="flex justify-between text-base font-medium text-gray-900">
                                                                <h3>
                                                                    <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                                                </h3>
                                                                <p class="ml-4"><?php echo htmlspecialchars($product['price']); ?> €</p>
                                                            </div>
                                                            <p class="mt-1 text-sm text-gray-500"><?php echo htmlspecialchars($product['description']); ?></p>
                                                        </div>
                                                        <div class="flex flex-1 items-end justify-between text-sm">
                                                            <p class="text-gray-500">Qty <?php echo htmlspecialchars($quantity); ?></p>

                                                            <div class="flex">
                                                                <a href="customer_dashboard.php?remove_from_cart=true&product_id=<?php echo htmlspecialchars($product['id']); ?>" class="font-medium text-green-600 hover:text-green-500">Entfernen</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 px-4 py-6 sm:px-6">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
