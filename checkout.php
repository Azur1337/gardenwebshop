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

// VARIABLEN INITIALISIEREN
$cart_products = []; // Array für Produkte im Warenkorb.
$cart_services = []; // Array für Dienstleistungen im Warenkorb.
$subtotal = 0; // Variable für die Zwischensumme.

// ABFRAGE DER WARENKORB-ITEMS
$cart_query = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
$cart_query->bind_param("i", $user_id); // Platzhalter (?) durch die Benutzer-ID ersetzen.
$cart_query->execute();
$cart_items = $cart_query->get_result()->fetch_all(MYSQLI_ASSOC); // Alle Warenkorb-Items abrufen.
$cart_query->close();

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

// CHECKOUT-PROZESS BEI FORMULARABSENDUNG
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // VERSANDADRESSE AUS DEM FORMULAR AUSLESEN
    $name = $_POST['name'];
    $address = $_POST['address'];
    $zip = $_POST['zip'];
    $city = $_POST['city'];

    // SCHritt 1: NEUE BESTELLUNG IN DER TABELLE "ORDERS" ERSTELLEN
    $order_stmt = $db->prepare("INSERT INTO orders (user_id, order_date) VALUES (?, NOW())");
    $order_stmt->bind_param("i", $user_id); // Platzhalter (?) durch die Benutzer-ID ersetzen.
    if (!$order_stmt->execute()) {
        // Falls die Bestellung nicht erstellt werden kann, wird ein Fehler angezeigt.
        die("Fehler beim Erstellen der Bestellung: " . $db->error);
    }
    $order_id = $db->insert_id; // Holt die automatisch generierte Bestellungs-ID.

    // SCHritt 2: JEDES ITEM IM WARENKORB IN DIE TABELLE "ORDER_ITEMS" EINFÜGEN
    foreach ($cart_items as $item) {
        if (isset($item['product_id'])) {
            // FALL: ITEM IST EIN PRODUKT
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            // PRÜFEN, OB DAS PRODUKT IM WARENKORB ARRAY VORHANDEN IST
            if (isset($cart_products[array_search($product_id, array_column($cart_products, 'product', 'id'))])) {
                $product_price = $cart_products[array_search($product_id, array_column($cart_products, 'product', 'id'))]['product']['price'];

                // NEUES ORDER_ITEM FÜR DAS PRODUKT ERSTELLEN
                $item_stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
                $item_stmt->bind_param("iii", $order_id, $product_id, $quantity); // Bindet die Parameter an die Platzhalter.
                if (!$item_stmt->execute()) {
                    // Falls das Order_Item nicht erstellt werden kann, wird ein Fehler angezeigt.
                    die("Fehler beim Erstellen des Order_Items: " . $db->error);
                }
                $item_stmt->close();

                // LAGERBESTAND DES PRODUKTS AKTUALISIEREN
                $update_stock_stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $update_stock_stmt->bind_param("ii", $quantity, $product_id); // Reduziert den Lagerbestand um die bestellte Menge.
                if (!$update_stock_stmt->execute()) {
                    // Falls der Lagerbestand nicht aktualisiert werden kann, wird ein Fehler angezeigt.
                    die("Fehler beim Aktualisieren des Lagerbestands: " . $db->error);
                }
                $update_stock_stmt->close();
            }
        } elseif (isset($item['service_id'])) {
            // FALL: ITEM IST EINE DIENSTLEISTUNG
            $service_id = $item['service_id'];
            $quantity = $item['quantity'];

            // NEUES ORDER_ITEM FÜR DIE DIENSTLEISTUNG ERSTELLEN
            $item_stmt = $db->prepare("INSERT INTO order_items (order_id, service_id, quantity) VALUES (?, ?, ?)");
            $item_stmt->bind_param("iii", $order_id, $service_id, $quantity); // Bindet die Parameter an die Platzhalter.
            if (!$item_stmt->execute()) {
                // Falls das Order_Item nicht erstellt werden kann, wird ein Fehler angezeigt.
                die("Fehler beim Erstellen des Order_Items: " . $db->error);
            }
            $item_stmt->close();
        }
    }

    // SCHritt 3: WARENKORB LEEREN NACH DEM CHECKOUT
    $clear_cart_query = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $clear_cart_query->bind_param("i", $user_id); // Löscht alle Items des Benutzers aus dem Warenkorb.
    $clear_cart_query->execute();
    $clear_cart_query->close();

    // SESSION-VARIABLE FÜR DEN WARENKORB LEEREN
    $_SESSION['cart'] = [];

    // WEITERLEITUNG ZUR CHECKOUT-ERFOLGSSITE
    header("Location: checkout_success.php");
    exit;
}

$db->close(); // Datenbankverbindung schließen.
?>

<!-- HTML-TEIL: CHECKOUT-FORMULAR -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bestellung bestätigen</title>
</head>
<body>
    <h1>Bestellung bestätigen</h1>

    <!-- BESTELLÜBERSICHT -->
    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Preis</th>
                <th>Menge</th>
                <th>Gesamtpreis</th>
            </tr>
        </thead>
        <tbody>
            <!-- PRODUKTE IM WARENKORB ANZEIGEN -->
            <?php foreach ($cart_products as $cart_product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cart_product['product']['name']); ?></td>
                    <td><?php echo htmlspecialchars($cart_product['product']['price']); ?> €</td>
                    <td><?php echo htmlspecialchars($cart_product['quantity']); ?></td>
                    <td><?php echo number_format($cart_product['product']['price'] * $cart_product['quantity'], 2); ?> €</td>
                </tr>
            <?php endforeach; ?>

            <!-- DIENSTLEISTUNGEN IM WARENKORB ANZEIGEN -->
            <?php foreach ($cart_services as $cart_service): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cart_service['service']['name']); ?></td>
                    <td><?php echo htmlspecialchars($cart_service['service']['price']); ?> €</td>
                    <td><?php echo htmlspecialchars($cart_service['quantity']); ?></td>
                    <td><?php echo number_format($cart_service['service']['price'] * $cart_service['quantity'], 2); ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ZWISCHENSUMME -->
    <p>Zwischensumme: <?php echo number_format($subtotal, 2); ?> €</p>
    <p>Versand: 0.00 €</p>
    <p>Gesamtsumme: <?php echo number_format($subtotal, 2); ?> €</p>

    <!-- VERSANDINFORMATIONEN -->
    <form action="" method="POST">
        <label for="name">Vollständiger Name:</label>
        <input type="text" id="name" name="name" required><br>

        <label for="address">Adresse:</label>
        <input type="text" id="address" name="address" required><br>

        <label for="zip">PLZ:</label>
        <input type="text" id="zip" name="zip" required><br>

        <label for="city">Stadt:</label>
        <input type="text" id="city" name="city" required><br>

        <button type="submit">Bestellung abschließen</button>
    </form>

    <!-- FOOTER -->
    <footer>
        <p>&copy; 2025 Garten-Webshop</p>
    </footer>
</body>
</html>