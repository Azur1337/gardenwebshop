<?php
// PRÜFUNG DER ANFRAGEMETHODE
// Überprüft, ob das Formular über eine POST-Anfrage gesendet wurde.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // DATEN AUS DEM FORMULAR AUSLESEN
    // Die Werte aus dem Registrierungsformular werden in Variablen gespeichert.
    $username = $_POST['username']; // Benutzername des neuen Benutzers
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Passwort wird gehasht für Sicherheit
    $role = $_POST['role']; // Rolle des Benutzers (z. B. Admin oder Kunde)

    // VERBINDUNG ZUR DATENBANK HERSTELLEN
    // Neue Verbindung zur Datenbank herstellen.
    $db = new mysqli("localhost", "root", "1337", "garden_shop");
    if ($db->connect_error) {
        // Falls die Verbindung fehlschlägt, wird ein Fehler angezeigt und das Skript abgebrochen.
        die("Verbindungsfehler: " . $db->connect_error);
    }

    // VORBEREITUNG DER SQL-ANFRAGE
    // SQL-Statement vorbereiten, um einen neuen Benutzer in die Tabelle "users" einzufügen.
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    // Platzhalter (?) werden durch echte Werte ersetzt, um SQL-Injection zu verhindern.
    $stmt->bind_param("sss", $username, $password, $role); // "sss" gibt an, dass alle drei Parameter Strings sind.

    // AUSFÜHREN DER SQL-ANFRAGE
    if ($stmt->execute()) {
        // Wenn die Einfügung erfolgreich war, wird eine Erfolgsmeldung angezeigt.
        echo "<script>alert('Registrierung erfolgreich!'); window.location.href='index.php';</script>";
        // Der Benutzer wird auf die Startseite weitergeleitet.
    } else {
        // Wenn die Einfügung fehlschlägt, wird ein Fehler angezeigt.
        echo "<script>alert('Registrierung fehlgeschlagen: " . $stmt->error . "'); window.location.href='register.php';</script>";
        // Der Benutzer wird zurück zum Registrierungsformular geleitet.
    }

    // RÄUMEN DER RESOURCEN
    // Prepared Statement schließen, um Speicher freizugeben.
    $stmt->close();
    // Datenbankverbindung schließen.
    $db->close();
}
?>