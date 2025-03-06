<?php
// SESSION STARTEN UND ZUGRIFFSPRÜFUNG
// Startet die PHP-Session und prüft, ob der Benutzer angemeldet ist und die Rolle "Kunde" hat.
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    // Falls der Benutzer nicht angemeldet ist oder keine "Kunde"-Rolle hat, wird er zur Anmeldeseite weitergeleitet.
    header("Location: login_register.php");
    exit;
}

// CHECKOUT-PROZESS BEI FORMULARABSENDUNG
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user']['id']; // Holt die Benutzer-ID aus der Session.
    $name = $_POST['name']; // Vollständiger Name des Kunden.
    $address = $_POST['address']; // Adresse des Kunden.
    $zip = $_POST['zip']; // PLZ des Kunden.
    $city = $_POST['city']; // Stadt des Kunden.

    // VERBINDUNG ZUR DATENBANK HERSTELLEN
    $db = new mysqli("localhost", "root", "1337", "garden_shop");
    if ($db->connect_error) {
        // Falls die Verbindung zur Datenbank fehlschlägt, wird ein Fehler angezeigt.
        die("Verbindungsfehler: " . $db->connect_error);
    }

    // BESTELLUNGEN IN DIE DATENBANK EINFÜGEN
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        // Für jedes Produkt im Warenkorb wird die Preisinformation abgerufen.
        $product_query = $db->prepare("SELECT price FROM products WHERE id = ?");
        $product_query->bind_param("i", $product_id); // Bindet die Produkt-ID an den Platzhalter.
        $product_query->execute(); // Führt die Abfrage aus.
        $product = $product_query->get_result()->fetch_assoc(); // Holt das Ergebnis als assoziatives Array.
        $product_query->close(); // Schließt die Abfrage.

        if ($product) {
            $price = $product['price']; // Holt den Preis des Produkts.
            // Bestellung in die Tabelle "orders" einfügen.
            $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, price) VALUES (?, ?, ?)");
            $stmt->bind_param("idd", $user_id, $product_id, $price); // Bindet die Parameter an die Platzhalter.
            $stmt->execute(); // Führt die Einfügung aus.
            $stmt->close(); // Schließt die Prepared Statement.
        }
    }

    // WARENKORB LEEREN NACH DEM CHECKOUT
    $_SESSION['cart'] = []; // Leert den Warenkorb in der Session.

    $db->close(); // Schließt die Datenbankverbindung.

    // WEITERLEITUNG ZUR KUNDENDASHBOARD-SEITE
    header("Location: customer_dashboard.php"); // Leitet den Benutzer zur Kunden-Dashboard-Seite weiter.
    exit; // Beendet das Skript.
}

// HELPER-FUNKTION FÜR DATEINAMENS-SANITIZATION
function sanitizeFilename($string) {
    // Umlaute in ASCII-Zeichen umwandeln.
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

    // Sonderzeichen ersetzen und nur erlaubte Zeichen behalten.
    $sanitized = strtr($string, $transliterationTable);
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    return strtolower($sanitized); // Gibt den bereinigten String in Kleinbuchstaben zurück.
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

    <!-- NAVIGATION-EINSCHLUSS -->
    <?php include 'navbar.php'; ?>

    <!-- CHECKOUT-BESTÄTIGUNG -->
    <main class="bg-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-8">Bestellung erfolgreich!</h1>
            <p class="text-gray-600">Vielen Dank für Ihre Bestellung. Sie erhalten bald eine Bestellbestätigung per E-Mail.</p>
            <div class="mt-8">
                <!-- BUTTON ZURÜCK ZUR STARTSEITE -->
                <a href="index.php" class="bg-green-500 text-white text-lg font-medium px-6 py-3 rounded-lg shadow hover:bg-green-600 focus:ring-4 focus:ring-green-600 focus:ring-opacity-50 transition-colors">
                    Zurück zur Startseite
                </a>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-green-700 text-white p-4 mt-8">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>