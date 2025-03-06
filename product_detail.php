<?php
// VERBINDUNG ZUR DATENBANK HERSTELLEN UND PRODUKTDATEN ABFRAGEN

// Produkt-ID aus der URL-Parameter abrufen
$product_id = $_GET['id'];

// Verbindung zur Datenbank herstellen
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Verbindungsfehler: " . $db->connect_error);
}

// Abfrage der Produkt-Daten aus der Datenbank
$product_query = $db->prepare("SELECT * FROM products WHERE id = ?");
$product_query->bind_param("i", $product_id);
$product_query->execute();
$product = $product_query->get_result()->fetch_assoc();
$product_query->close();

// Wenn kein Produkt gefunden wurde, wird der Vorgang abgebrochen
if (!$product) {
    die("Produkt nicht gefunden.");
}

// Datenbankverbindung schließen
$db->close();

// HELPER-FUNKTION FÜR DATEINAMENS-SANITIZATION
function sanitizeFilename($string) {
    // Umlaute in ASCII-Zeichen umwandeln
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

    // Sonderzeichen ersetzen und nur erlaubte Zeichen behalten
    $sanitized = strtr($string, $transliterationTable);
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    return strtolower($sanitized);
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <!-- NAVIGATION EINFÜGEN -->
    <?php include 'navbar.php'; ?>

    <!-- HAUPTINHALT DES PRODUKTSEITENS -->
    <main class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-10 lg:gap-y-0">

                <!-- PRODUKT-BILD -->
                <div class="flex justify-center">
                    <img src="./images/<?php echo htmlspecialchars(sanitizeFilename($product['name'])); ?>.jpeg" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full max-w-2xl rounded-lg object-fit">
                </div>

                <!-- PRODUKT-DETAILS -->
                <div>
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="w-20 h-2 bg-green-700 my-4"></div>
                    <p class="text-xl text-gray-600 mb-4"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($product['price']); ?> €</p>

                    <!-- LAGERBESTAND-INFORMATION -->
                    <div class="mt-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Verfügbarkeit</h3>
                        <?php if ($product['stock'] > 0): ?>
                            <p class="text-green-600"><?php echo htmlspecialchars($product['stock']); ?> verbleibend</p>
                        <?php else: ?>
                            <p class="text-red-600">Ausverkauft</p>
                        <?php endif; ?>
                    </div>

                    <!-- OPTIONEN (MENGE AUSWÄHLEN) -->
                    <div class="mt-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Optionen</h3>
                        <div class="flex flex-col md:flex-row md:space-x-4">
                            <div class="mb-4 md:mb-0">
                                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Menge</label>
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    min="1"
                                    max="<?php echo htmlspecialchars($product['stock']); ?>"
                                    value="1"
                                    <?php if ($product['stock'] == 0): ?>
                                        disabled
                                    <?php endif; ?>
                                    class="w-20 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- IN DEN WARENKORB BUTTON -->
                    <div class="mt-8">
                        <form action="customer_dashboard.php" method="GET">
                            <input type="hidden" name="add_to_cart" value="true" />
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>" />
                            <input type="hidden" name="quantity" id="quantity_input" value="1" />
                            <button 
                                type="submit" 
                                class="bg-green-500 text-white text-lg font-medium px-6 py-3 rounded-lg shadow hover:bg-green-600 <?php if ($product['stock'] == 0): ?>opacity-50 cursor-not-allowed<?php endif; ?>"
                                <?php if ($product['stock'] == 0): ?>
                                    disabled
                                <?php endif; ?>
                            >
                                In Warenkorb
                            </button>
                        </form>
                    </div>

                    <!-- WEITERE PRODUKTDATEN -->
                    <div class="mt-12">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Details</h3>
                        <p class="text-gray-600">
                            Das <?php echo htmlspecialchars($product['name']); ?> enthält zwei schwarze, zwei weiße und zwei graue Basic Tees. Melden Sie sich für unseren Newsletter an, um als Erster neue, aufregende Farben wie unserem kommenden "Kohleschwarz" Limited Edition zu erhalten.
                        </p>
                    </div>
                </div>
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