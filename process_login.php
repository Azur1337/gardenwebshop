<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $db = new mysqli("localhost", "root", "1337", "garden_shop");
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }

    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows == 1 && password_verify($password, $hashed_password)) {
        $_SESSION['user'] = [
            'id' => $user_id,
            'role' => $role
        ];
        if ($role === 'customer') {
            header("Location: customer_dashboard.php");
        } elseif ($role === 'gardener') {
            header("Location: gardener_dashboard.php");
        }
    } else {
        echo "<script>alert('Ungültiger Benutzername oder Passwort!'); window.location.href='login.php';</script>";
    }

    $stmt->close();
    $db->close();
}
?>
