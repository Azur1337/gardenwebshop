<?php
// Assuming you have a service ID passed via URL parameter
$service_id = $_GET['id'];

// Database connection
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Fetch service details
$service_query = $db->prepare("SELECT * FROM services WHERE id = ?");
$service_query->bind_param("i", $service_id);
$service_query->execute();
$service = $service_query->get_result()->fetch_assoc();
$service_query->close();

if (!$service) {
    die("Dienstleistung nicht gefunden.");
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
    <title><?php echo htmlspecialchars($service['name']); ?> - Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <main class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-10 lg:gap-y-0">
                <!-- Service Image -->
                <div class="flex justify-center">
                    <img src="./images/<?php echo htmlspecialchars(sanitizeFilename($service['name'])); ?>.jpeg" alt="<?php echo htmlspecialchars($service['name']); ?>" class="w-full max-w-2xl rounded-lg object-cover">
                </div>

                <!-- Service Details -->
                <div>
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars($service['name']); ?></h1>
                    <div class="w-20 h-2 bg-green-700 my-4"></div>
                    <p class="text-xl text-gray-600 mb-4"><?php echo htmlspecialchars($service['description']); ?></p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($service['price']); ?> €</p>

                    <!-- Service Options -->
                    <div class="mt-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Optionen</h3>
                        <div class="flex flex-col md:flex-row md:space-x-4">
                            <!-- Quantity Picker -->
                            <div class="mb-4 md:mb-0">
                                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Menge</label>
                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    min="1"
                                    value="1"
                                    class="w-20 px-3 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Add to Cart Button -->
                    <div class="mt-8">
                        <form action="customer_dashboard.php" method="GET">
                            <input type="hidden" name="add_to_cart" value="true" />
                            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service['id']); ?>" />
                            <input type="hidden" name="quantity" id="quantity_input" value="1" />
                            <button type="submit" class="bg-green-500 text-white text-lg font-medium px-6 py-3 rounded-lg shadow hover:bg-green-600">In Warenkorb</button>
                        </form>
                    </div>

                    <!-- Service Highlights -->
                    <div class="mt-12">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Highlights</h3>
                        <ul class="list-disc list-inside">
                            <li>Professionelle Dienstleistungen</li>
                            <li>Schnelle und zuverlässige Bearbeitung</li>
                            <li>Bester Kundenservice</li>
                        </ul>
                    </div>

                    <!-- Service Details -->
                    <div class="mt-12">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Details</h3>
                        <p class="text-gray-600">
                            Die <?php echo htmlspecialchars($service['name']); ?> bietet Ihnen hochwertige Dienstleistungen für Ihren Garten. Melden Sie sich für unseren Newsletter an, um als Erster über neue Angebote und Termine informiert zu werden.
                        </p>
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
