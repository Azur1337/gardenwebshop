<?php
function sanitizeFilename($string) {
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

    $sanitized = strtr($string, $transliterationTable);

    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $sanitized);

    return strtolower($sanitized);
}


$db = new mysqli("localhost", "root", "1337", "garden_shop");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT * FROM services");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Produkte</title>
     <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">
<?php include 'navbar.php'; ?>
       <main class="bg-white">
        <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Dienstleistungen</h2>

            <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="group relative">
                        <img src="./images/<?php echo sanitizeFilename($row['name']); ?>.jpeg" alt="<?php echo $row['name']; ?>" class="aspect-square w-full rounded-md bg-green-200 object-cover group-hover:scale-105 group-hover:opacity-75 transition-all duration-150 lg:aspect-auto lg:h-80">
                        <div class="mt-4 flex justify-between">
                            <div>
                                <h3 class="text-sm text-gray-700">
                                    <a href="service_detail.php?id=<?php echo $row['id']; ?>">
                                        <span aria-hidden="true" class="absolute inset-0"></span>
                                        <?php echo $row['name']; ?>
                                    </a>
                                </h3>
                                <p class="mt-1 text-sm text-gray-500"><?php echo $row['description']; ?></p>
                            </div>
                            <p class="text-sm font-medium text-gray-900"><?php echo $row['price']; ?> €</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
        </div>
    </footer>
</body>
</html>
