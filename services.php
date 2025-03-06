<?php
// HELPER-FUNKTION FÜR DATEINAMENS-SANITIZATION
function sanitizeFilename($string) {
    // Umlaute in ihre ASCII-Äquivalente umwandeln.
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

    // Zeichen im String durch das Transliterationstabelle ersetzen.
    $sanitized = strtr($string, $transliterationTable);

    // Alle nicht erlaubten Zeichen entfernen (nur Buchstaben, Zahlen, Unterstriche und Bindestriche sind erlaubt).
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    // Den bereinigten String in Kleinbuchstaben umwandeln und zurückgeben.
    return strtolower($sanitized);
}

// VERBINDUNG ZUR DATENBANK HERSTELLEN
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    // Falls die Verbindung zur Datenbank fehlschlägt, wird ein Fehler angezeigt und das Skript abgebrochen.
    die("Verbindungsfehler: " . $db->connect_error);
}

// ABFRAGE DER DIENSTLEISTUNGEN AUS DER DATENBANK
$result = $db->query("SELECT * FROM services");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dienstleistungen</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">

    <!-- NAVIGATION-EINSCHLUSS -->
    <?php include 'navbar.php'; ?>

    <!-- HAUPTEINHALT: LISTE DER DIENSTLEISTUNGEN -->
    <main class="bg-white">
        <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dienstleistungen</h2>

            <!-- GITTERLAYOUT FÜR DIE DARSTELLUNG DER DIENSTLEISTUNGEN -->
            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <!-- JEDER EINTRAG IN DER DIENSTLEISTUNGS-LISTE -->
                    <div class="group relative">
                        <!-- BILD DER DIENSTLEISTUNG -->
                        <img src="./images/<?php echo sanitizeFilename($row['name']); ?>.jpeg" alt="<?php echo $row['name']; ?>" class="aspect-square w-full rounded-md bg-green-200 object-cover group-hover:scale-105 group-hover:opacity-75 transition-all duration-150 lg:aspect-auto lg:h-80">
                        <div class="mt-4 flex justify-between">
                            <div>
                                <!-- NAME UND LINK ZUR DETAILANSICHT DER DIENSTLEISTUNG -->
                                <h3 class="text-sm text-gray-700">
                                    <a href="service_detail.php?id=<?php echo $row['id']; ?>">
                                        <span aria-hidden="true" class="absolute inset-0"></span>
                                        <?php echo $row['name']; ?>
                                    </a>
                                </h3>
                                <!-- KURZE BESCHREIBUNG DER DIENSTLEISTUNG -->
                                <p class="mt-1 text-sm text-gray-500"><?php echo $row['description']; ?></p>
                            </div>
                            <!-- PREIS DER DIENSTLEISTUNG -->
                            <p class="text-sm font-medium text-gray-900"><?php echo $row['price']; ?> €</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>