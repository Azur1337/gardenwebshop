<?php
// SESSION STARTEN
// Startet die PHP-Session, um Benutzerdaten zwischen Seiten zu speichern.
session_start();

// PRÜFUNG DER ANFRAGEMETHODE
// Überprüft, ob das Formular über eine POST-Anfrage gesendet wurde.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // DATEN AUS DEM FORMULAR AUSLESEN
    // Die Werte aus dem Login-Formular werden in Variablen gespeichert.
    $username = $_POST['username']; // Benutzername des Benutzers
    $password = $_POST['password']; // Passwort des Benutzers

    // VERBINDUNG ZUR DATENBANK HERSTELLEN
    // Neue Verbindung zur Datenbank herstellen.
    $db = new mysqli("localhost", "root", "1337", "garden_shop");
    if ($db->connect_error) {
        // Falls die Verbindung fehlschlägt, wird ein Fehler angezeigt und das Skript abgebrochen.
        die("Verbindungsfehler: " . $db->connect_error);
    }

    // BENUTZERDATEN ABFRAGEN
    // SQL-Statement vorbereiten, um den Benutzer anhand des Benutzernamens zu finden.
    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // Platzhalter (?) wird durch den Benutzernamen ersetzt.
    $stmt->execute(); // Statement ausführen.
    $stmt->store_result(); // Ergebnis zwischenspeichern.
    $stmt->bind_result($user_id, $hashed_password, $role); // Ergebniswerte in Variablen binden.
    $stmt->fetch(); // Ergebniszeile abrufen.

    // AUTHENTIFIZIERUNG PRÜFEN
    // Überprüft, ob genau ein Benutzer gefunden wurde und das eingegebene Passwort korrekt ist.
    if ($stmt->num_rows == 1 && password_verify($password, $hashed_password)) {
        // Wenn die Anmeldung erfolgreich ist, speichert der Code die Benutzer-ID und Rolle in der Session.
        $_SESSION['user'] = [
            'id' => $user_id, // Benutzer-ID
            'role' => $role   // Benutzerrolle (z. B. Kunde oder Gärtner)
        ];

        // WEITERLEITUNG BASIEREND AUF DER BENUTZERROLLE
        if ($role === 'customer') {
            // Wenn der Benutzer eine "Kunde"-Rolle hat, wird er auf die Kunden-Dashboard-Seite weitergeleitet.
            header("Location: customer_dashboard.php");
        } elseif ($role === 'gardener') {
            // Wenn der Benutzer eine "Gärtner"-Rolle hat, wird er auf die Gärtner-Dashboard-Seite weitergeleitet.
            header("Location: gardener_dashboard.php");
        }
    } else {
        // Falls die Anmeldeinformationen ungültig sind, wird eine Fehlermeldung angezeigt.
        echo "<script>alert('Ungültiger Benutzername oder Passwort!'); window.location.href='login.php';</script>";
    }

    // RÄUMEN DER RESOURCEN
    // Prepared Statement schließen, um Speicher freizugeben.
    $stmt->close();
    // Datenbankverbindung schließen.
    $db->close();
}
?>