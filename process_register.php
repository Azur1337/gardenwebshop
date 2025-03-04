<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $db = new mysqli("localhost", "root", "1337", "garden_shop");
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Registrierung erfolgreich!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Registrierung fehlgeschlagen: " . $stmt->error . "'); window.location.href='register.php';</script>";
    }

    $stmt->close();
    $db->close();
}
?>
