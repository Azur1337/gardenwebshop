<?php
// DATENBANKVERBINDUNG HERSTELLEN
$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    // Falls die Verbindung zur Datenbank fehlschlägt, wird ein Fehler angezeigt.
    die("Verbindungsfehler: " . $db->connect_error);
}

// ANMELDUNGSHANDLING
$login_error = ''; // Variable für Anmeldefehlermeldungen.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Wenn das Formular zur Anmeldung über POST gesendet wurde:
    $username = $_POST['username']; // Benutzername aus dem Formular abrufen.
    $password = $_POST['password']; // Passwort aus dem Formular abrufen.

    // SQL-Abfrage vorbereiten, um den Benutzer anhand des Benutzernamens zu finden.
    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // Platzhalter (?) durch den Benutzernamen ersetzen.
    $stmt->execute(); // Statement ausführen.
    $stmt->store_result(); // Ergebnis zwischenspeichern.
    $stmt->bind_result($user_id, $hashed_password, $role); // Ergebniswerte in Variablen binden.
    $stmt->fetch(); // Ergebniszeile abrufen.

    // Überprüfen, ob genau ein Benutzer gefunden wurde und das Passwort korrekt ist.
    if ($stmt->num_rows == 1 && password_verify($password, $hashed_password)) {
        // Wenn die Anmeldung erfolgreich ist, speichert der Code die Benutzer-ID und Rolle in der Session.
        $_SESSION['user'] = [
            'id' => $user_id,
            'role' => $role
        ];

        // Weiterleitung basierend auf der Benutzerrolle.
        if ($role === 'customer') {
            header("Location: customer_dashboard.php"); // Kunde wird zur Kunden-Dashboard-Seite weitergeleitet.
        } elseif ($role === 'gardener') {
            header("Location: gardener_dashboard.php"); // Gärtner wird zur Gärtner-Dashboard-Seite weitergeleitet.
        }
    } else {
        // Falls die Anmeldeinformationen ungültig sind, wird eine Fehlermeldung gesetzt.
        $login_error = "Ungültiger Benutzername oder Passwort!";
    }

    $stmt->close(); // Prepared Statement schließen.
}

// REGISTRIERUNGS-HANDLING
$registration_error = ''; // Variable für Registrierungsfehlermeldungen.
$registration_success = ''; // Variable für Erfolgsnachricht bei erfolgreicher Registrierung.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    // Wenn das Formular zur Registrierung über POST gesendet wurde:
    $username = $_POST['username']; // Benutzername aus dem Formular abrufen.
    $password = $_POST['password']; // Passwort aus dem Formular abrufen.
    $confirm_password = $_POST['confirm_password']; // Bestätigung des Passworts aus dem Formular abrufen.
    $role = $_POST['role']; // Benutzerrolle (Kunde/Gärtner) aus dem Formular abrufen.

    // Prüfen, ob die Passwörter übereinstimmen.
    if ($password !== $confirm_password) {
        $registration_error = "Passwörter stimmen nicht überein"; // Setzt einen Fehler, wenn die Passwörter nicht übereinstimmen.
    } else {
        // Passwort hashen für Sicherheit.
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // SQL-Abfrage vorbereiten, um einen neuen Benutzer einzufügen.
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role); // Bindet die Parameter an die Platzhalter.

        if ($stmt->execute()) {
            // Wenn die Einfügung erfolgreich war, wird eine Erfolgsmeldung gesetzt.
            $registration_success = "Registrierung erfolgreich! Bitte melden Sie sich an.";
        } else {
            // Wenn die Einfügung fehlschlägt, wird ein Fehler angezeigt.
            $registration_error = "Registrierung fehlgeschlagen: " . $stmt->error;
        }

        $stmt->close(); // Prepared Statement schließen.
    }
}

$db->close(); // Datenbankverbindung schließen.

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
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmelden / Registrieren - Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <!-- ÜBERSCHRIFT -->
    <header class="text-center mb-8">
        <h1 class="text-3xl font-bold text-green-700">Willkommen bei unserem Garten-Webshop</h1>
        <p class="text-lg text-gray-600">Melden Sie sich an oder registrieren Sie sich.</p>
    </header>

    <!-- ANMELDEFORMULAR -->
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Anmelden</h2>
        <?php if (!empty($login_error)): ?>
            <!-- ANZEIGE VON FEHLERN BEIM ANMELDEN -->
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form action="" method="POST">
            <!-- BENUTZERNAME -->
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Benutzername</label>
                <input type="text" id="username" name="username" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
            </div>
            <!-- PASSWORT -->
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Passwort</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
            </div>
            <!-- ANMELDEN-BUTTON -->
            <button type="submit" name="login" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                Anmelden
            </button>
        </form>
        <!-- LINK ZUR REGISTRIERUNG -->
        <p class="text-center text-sm mt-4">
            Noch kein Konto? <a href="#register" class="text-green-500 hover:underline">Registrieren</a>
        </p>
    </div>

    <!-- REGISTRIERUNGSFORMULAR -->
    <div id="register" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mt-4 w-full max-w-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Registrieren</h2>
        <!-- ANZEIGE VON REGISTRIERUNGSFEHLERN ODER ERFOLGSNACHRICHTEN -->
        <?php if (!empty($registration_error)): ?>
            <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($registration_error); ?></p>
        <?php elseif (!empty($registration_success)): ?>
            <p class="text-green-500 text-sm mb-4"><?php echo htmlspecialchars($registration_success); ?></p>
        <?php endif; ?>
        <form action="" method="POST">
            <!-- BENUTZERNAME -->
            <div class="mb-4">
                <label for="register_username" class="block text-gray-700 text-sm font-bold mb-2">Benutzername</label>
                <input type="text" id="register_username" name="username" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
            </div>
            <!-- PASSWORT -->
            <div class="mb-4">
                <label for="register_password" class="block text-gray-700 text-sm font-bold mb-2">Passwort</label>
                <input type="password" id="register_password" name="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
            </div>
            <!-- PASSWORT BESTÄTIGEN -->
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Passwort bestätigen</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
            </div>
            <!-- ROLLE -->
            <div class="mb-6">
                <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rolle</label>
                <select id="role" name="role" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-green-500">
                    <option value="customer">Kunde</option>
                    <option value="gardener">Gärtner</option>
                </select>
            </div>
            <!-- REGISTRIEREN-BUTTON -->
            <button type="submit" name="register" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                Registrieren
            </button>
        </form>
        <!-- LINK ZUR ANMELDUNG -->
        <p class="text-center text-sm mt-4">
            Bereits ein Konto? <a href="#login" class="text-green-500 hover:underline">Anmelden</a>
        </p>
    </div>

    <!-- FOOTER -->
    <footer class="mt-8 text-center text-gray-600">
        <p>© 2025 Garten-Webshop</p>
    </footer>
</body>
</html>