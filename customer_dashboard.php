<?php
// SESSION STARTEN
session_start();

// VERBINDUNG ZUR DATENBANK HERSTELLEN
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    // Falls die Verbindung zur Datenbank fehlschlägt, wird ein Fehler angezeigt.
    die("Verbindungsfehler: " . $db->connect_error);
}

// BENUTZER-ID AUS DER SESSION LADEN
$user_id = $_SESSION['user']['id'];

// PRODUKTE ABFRAGEN
$products_query = $db->query("SELECT * FROM products");
$products = $products_query->fetch_all(MYSQLI_ASSOC); // Alle Produkte als assoziatives Array speichern.
$products_query->close();

// DIENSTLEISTUNGEN ABFRAGEN
$services_query = $db->query("SELECT * FROM services");
$services = $services_query->fetch_all(MYSQLI_ASSOC); // Alle Dienstleistungen als assoziatives Array speichern.
$services_query->close();

// WARENKORB-PRODUKTE UND -DIENSTLEISTUNGEN ABFRAGEN
$cart_products = [];
$cart_services = [];
$subtotal = 0; // Variable für die Zwischensumme.

// WARENKORB-ITEMS ABFRAGEN
$cart_items_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
$cart_items_query->bind_param("i", $user_id); // Platzhalter (?) durch die Benutzer-ID ersetzen.
$cart_items_query->execute();
$cart_items = $cart_items_query->get_result()->fetch_all(MYSQLI_ASSOC); // Warenkorb-Items als assoziatives Array speichern.
$cart_items_query->close();

// DURCH DIE WARENKORB-ITEMS ITERIEREN
foreach ($cart_items as $item) {
    if (isset($item['product_id'])) {
        // FALL: ITEM IST EIN PRODUKT
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        // PRODUKTDETAILS ABFRAGEN
        $product_query = $db->prepare("SELECT * FROM products WHERE id = ?");
        $product_query->bind_param("i", $product_id); // Platzhalter (?) durch die Produkt-ID ersetzen.
        $product_query->execute();
        $product = $product_query->get_result()->fetch_assoc(); // Produkt-Daten abrufen.
        $product_query->close();

        if ($product) {
            // PRODUKT ZUM WARENKORB HINZUFÜGEN
            $cart_products[] = [
                'product' => $product,
                'quantity' => $quantity
            ];
            $subtotal += $product['price'] * $quantity; // Preis des Produkts zur Zwischensumme addieren.
        }
    } elseif (isset($item['service_id'])) {
        // FALL: ITEM IST EINE DIENSTLEISTUNG
        $service_id = $item['service_id'];
        $quantity = $item['quantity'];

        // DIENSTLEISTUNGSDETAILS ABFRAGEN
        $service_query = $db->prepare("SELECT * FROM services WHERE id = ?");
        $service_query->bind_param("i", $service_id); // Platzhalter (?) durch die Dienstleistungs-ID ersetzen.
        $service_query->execute();
        $service = $service_query->get_result()->fetch_assoc(); // Dienstleistungs-Daten abrufen.
        $service_query->close();

        if ($service) {
            // DIENSTLEISTUNG ZUM WARENKORB HINZUFÜGEN
            $cart_services[] = [
                'service' => $service,
                'quantity' => $quantity
            ];
            $subtotal += $service['price'] * $quantity; // Preis der Dienstleistung zur Zwischensumme addieren.
        }
    }
}

// PRODUKT ZUM WARENKORB HINZUFÜGEN
if (isset($_GET['add_to_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1; // Standardmenge ist 1.

    // PRÜFEN, OB DAS PRODUKT SCHON IM WARENKORB IST
    $check_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
    $check_query->bind_param("ii", $user_id, $product_id); // Platzhalter (?) durch Benutzer-ID und Produkt-ID ersetzen.
    $check_query->execute();
    $existing_item = $check_query->get_result()->fetch_assoc(); // Prüfen, ob das Produkt bereits im Warenkorb vorhanden ist.
    $check_query->close();

    if ($existing_item) {
        // MENGE AKTUALISIEREN, WENN DAS PRODUKT SCHON IM WARENKORB IST
        $new_quantity = $existing_item['quantity'] + $quantity;
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $product_id); // Neue Menge setzen.
        $update_query->execute();
        $update_query->close();
    } else {
        // NEUES PRODUKT ZUM WARENKORB HINZUFÜGEN
        $insert_query = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $insert_query->bind_param("iii", $user_id, $product_id, $quantity); // Neues Item in den Warenkorb einfügen.
        $insert_query->execute();
        $insert_query->close();
    }

    header("Location: customer_dashboard.php"); // Zurück zum Kunden-Dashboard weiterleiten.
    exit;
}

// DIENSTLEISTUNG ZUM WARENKORB HINZUFÜGEN
if (isset($_GET['add_to_cart']) && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1; // Standardmenge ist 1.

    // PRÜFEN, OB DIE DIENSTLEISTUNG SCHON IM WARENKORB IST
    $check_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? AND service_id = ?");
    $check_query->bind_param("ii", $user_id, $service_id); // Platzhalter (?) durch Benutzer-ID und Dienstleistungs-ID ersetzen.
    $check_query->execute();
    $existing_item = $check_query->get_result()->fetch_assoc(); // Prüfen, ob die Dienstleistung bereits im Warenkorb vorhanden ist.
    $check_query->close();

    if ($existing_item) {
        // MENGE AKTUALISIEREN, WENN DIE DIENSTLEISTUNG SCHON IM WARENKORB IST
        $new_quantity = $existing_item['quantity'] + $quantity;
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND service_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $service_id); // Neue Menge setzen.
        $update_query->execute();
        $update_query->close();
    } else {
        // NEUE DIENSTLEISTUNG ZUM WARENKORB HINZUFÜGEN
        $insert_query = $db->prepare("INSERT INTO cart_items (user_id, service_id, quantity) VALUES (?, ?, ?)");
        $insert_query->bind_param("iii", $user_id, $service_id, $quantity); // Neues Item in den Warenkorb einfügen.
        $insert_query->execute();
        $insert_query->close();
    }

    header("Location: customer_dashboard.php"); // Zurück zum Kunden-Dashboard weiterleiten.
    exit;
}

// PRODUKT AUS DEM WARENKORB ENTFERNEN
if (isset($_GET['remove_from_cart']) && isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // PRODUKT AUS DEM WARENKORB LÖSCHEN
    $delete_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
    $delete_query->bind_param("ii", $user_id, $product_id); // Platzhalter (?) durch Benutzer-ID und Produkt-ID ersetzen.
    $delete_query->execute();
    $delete_query->close();

    header("Location: customer_dashboard.php"); // Zurück zum Kunden-Dashboard weiterleiten.
    exit;
}

// DIENSTLEISTUNG AUS DEM WARENKORB ENTFERNEN
if (isset($_GET['remove_from_cart']) && isset($_GET['service_id'])) {
    $service_id = $_GET['service_id'];

    // DIENSTLEISTUNG AUS DEM WARENKORB LÖSCHEN
    $delete_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND service_id = ?");
    $delete_query->bind_param("ii", $user_id, $service_id); // Platzhalter (?) durch Benutzer-ID und Dienstleistungs-ID ersetzen.
    $delete_query->execute();
    $delete_query->close();

    header("Location: customer_dashboard.php"); // Zurück zum Kunden-Dashboard weiterleiten.
    exit;
}

// MENGE IM WARENKORB AKTUALISIEREN
if (isset($_POST['update_quantity'])) {
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $service_id = isset($_POST['service_id']) ? $_POST['service_id'] : null;
    $new_quantity = intval($_POST['quantity']); // Neue Menge aus dem Formular abrufen.

    if ($product_id) {
        // MENGE FÜR PRODUKT AKTUALISIEREN
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $product_id); // Neue Menge setzen.
        $update_query->execute();
        $update_query->close();
    } elseif ($service_id) {
        // MENGE FÜR DIENSTLEISTUNG AKTUALISIEREN
        $update_query = $db->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND service_id = ?");
        $update_query->bind_param("iii", $new_quantity, $user_id, $service_id); // Neue Menge setzen.
        $update_query->execute();
        $update_query->close();
    }

    header("Location: customer_dashboard.php"); // Zurück zum Kunden-Dashboard weiterleiten.
    exit;
}

// BESTELLHISTORIE DES BENUTZERS ABFRAGEN
$order_history = [];
$order_query = $db->prepare("SELECT o.id AS order_id, o.order_date FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC");
$order_query->bind_param("i", $user_id); // Platzhalter (?) durch die Benutzer-ID ersetzen.
$order_query->execute();
$orders = $order_query->get_result()->fetch_all(MYSQLI_ASSOC); // Bestellungen des Benutzers abrufen.
$order_query->close();

// DURCH DIE BESTELLUNGEN ITERIEREN
foreach ($orders as $order) {
    $order_id = $order['order_id'];
    $order_date = $order['order_date'];

    // ITEMS FÜR JEDERE BESTELLUNG ABFRAGEN
    $items_query = $db->prepare("
        SELECT oi.product_id, oi.service_id, oi.quantity, 
               p.name AS product_name, s.name AS service_name, 
               p.price AS product_price, s.price AS service_price 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        LEFT JOIN services s ON oi.service_id = s.id 
        WHERE oi.order_id = ?
    ");
    $items_query->bind_param("i", $order_id); // Platzhalter (?) durch die Bestellungs-ID ersetzen.
    $items_query->execute();
    $items = $items_query->get_result()->fetch_all(MYSQLI_ASSOC); // Items der Bestellung abrufen.
    $items_query->close();

    // BESTELLUNG ZUR BESTELLHISTORIE HINZUFÜGEN
    $order_history[] = [
        'order_id' => $order_id,
        'order_date' => $order_date,
        'items' => $items
    ];
}

$db->close(); // Datenbankverbindung schließen.

// HELPER-FUNKTION FÜR DATEINAMENS-SANITIZATION
function sanitizeFilename($string) {
    // UMLAUTE IN IHRE ASCII-ÄQUIVALENTE UMWANDLN
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

    // SPEZIELLE ZEICHEN ERSETZEN
    $sanitized = strtr($string, $transliterationTable);

    // ALLE NICHT ERLAUBTEN ZEICHEN ENTFERNEN
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    return strtolower($sanitized); // Den bereinigten String in Kleinbuchstaben zurückgeben.
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kunden-Dashboard</title>
    <!-- Tailwind CSS EINSCHLUSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">
    <?php include 'navbar.php'; ?>

    <!-- HAUPTEINHALT: KUNDEN-DASHBOARD -->
    <div class="container mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-4">Kunden-Dashboard</h1>

        <!-- PRODUKTELISTE -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Produkte</h2>
            <ul>
                <?php foreach ($products as $product): ?>
                    <li>
                        <a href="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>">
                            <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['price']); ?> €)
                        </a>
                        <form action="" method="GET" class="inline">
                            <input type="hidden" name="add_to_cart" value="true">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded">In Warenkorb</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- DIENSTLEISTUNGENSLISTE -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Dienstleistungen</h2>
            <ul>
                <?php foreach ($services as $service): ?>
                    <li>
                        <a href="service_detail.php?id=<?php echo htmlspecialchars($service['id']); ?>">
                            <?php echo htmlspecialchars($service['name']); ?> (<?php echo htmlspecialchars($service['price']); ?> €)
                        </a>
                        <form action="" method="GET" class="inline">
                            <input type="hidden" name="add_to_cart" value="true">
                            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service['id']); ?>">
                            <button type="submit" class="bg-green-500 text-white px-2 py-1 rounded">In Warenkorb</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- WARENKORB -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Warenkorb</h2>
            <?php if (empty($cart_products) && empty($cart_services)): ?>
                <p>Ihr Warenkorb ist leer.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="p-2">Name</th>
                            <th class="p-2">Preis</th>
                            <th class="p-2">Menge</th>
                            <th class="p-2">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- PRODUKTE IM WARENKORB ANZEIGEN -->
                        <?php foreach ($cart_products as $cart_product): ?>
                            <tr>
                                <td class="p-2"><?php echo htmlspecialchars($cart_product['product']['name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($cart_product['product']['price']); ?> €</td>
                                <td class="p-2">
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($cart_product['product']['id']); ?>">
                                        <input type="number" name="quantity" value="<?php echo htmlspecialchars($cart_product['quantity']); ?>" min="1" class="w-16">
                                        <button type="submit" name="update_quantity" class="bg-green-500 text-white px-2 py-1 rounded">Aktualisieren</button>
                                    </form>
                                </td>
                                <td class="p-2">
                                    <a href="?remove_from_cart=true&product_id=<?php echo htmlspecialchars($cart_product['product']['id']); ?>" class="text-red-500 hover:underline">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- DIENSTLEISTUNGEN IM WARENKORB ANZEIGEN -->
                        <?php foreach ($cart_services as $cart_service): ?>
                            <tr>
                                <td class="p-2"><?php echo htmlspecialchars($cart_service['service']['name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($cart_service['service']['price']); ?> €</td>
                                <td class="p-2">
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($cart_service['service']['id']); ?>">
                                        <input type="number" name="quantity" value="<?php echo htmlspecialchars($cart_service['quantity']); ?>" min="1" class="w-16">
                                        <button type="submit" name="update_quantity" class="bg-green-500 text-white px-2 py-1 rounded">Aktualisieren</button>
                                    </form>
                                </td>
                                <td class="p-2">
                                    <a href="?remove_from_cart=true&service_id=<?php echo htmlspecialchars($cart_service['service']['id']); ?>" class="text-red-500 hover:underline">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="font-bold mt-4">Zwischensumme: <?php echo number_format($subtotal, 2); ?> €</p>
                <a href="checkout.php" class="block bg-green-500 text-white px-4 py-2 rounded mt-4">Zur Kasse</a>
            <?php endif; ?>
        </section>

        <!-- BESTELLHISTORIE -->
        <section class="mb-6">
            <h2 class="text-xl font-semibold mb-2">Bestellhistorie</h2>
            <?php if (empty($order_history)): ?>
                <p>Sie haben noch keine Bestellungen abgeschlossen.</p>
            <?php else: ?>
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="p-2">Bestellnummer</th>
                            <th class="p-2">Datum</th>
                            <th class="p-2">Artikel</th>
                            <th class="p-2">Menge</th>
                            <th class="p-2">Preis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_history as $order): ?>
                            <tr>
                                <td class="p-2"><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td class="p-2">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php if ($item['product_name']): ?>
                                            <?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['product_price']); ?> €)<br>
                                        <?php elseif ($item['service_name']): ?>
                                            <?php echo htmlspecialchars($item['service_name']); ?> (<?php echo htmlspecialchars($item['service_price']); ?> €)<br>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td class="p-2">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php echo htmlspecialchars($item['quantity']); ?><br>
                                    <?php endforeach; ?>
                                </td>
                                <td class="p-2">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <?php echo htmlspecialchars($item['product_price'] ?? $item['service_price']); ?> €<br>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

    <!-- FOOTER -->
    <footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>