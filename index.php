<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Garten-Webshop</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100">

    <!-- NAVIGATION-EINSCHLUSS -->
    <?php include 'navbar.php'; ?>
    <!-- Die Navbar wird hier eingebunden, um eine konsistente Navigation über alle Seiten zu ermöglichen. -->

    <!-- HAUPTINHALT: HERO-SEKTION UND BILD -->
    <div class="flex flex-wrap">
        <!-- TEXTTEIL DER HERO-SEKTION -->
        <div class="w-full sm:w-8/12 mb-10">
            <div class="container mx-auto h-full sm:p-10">
                <header class="container px-4 lg:flex mt-10 items-center h-full lg:mt-0">
                    <div class="w-full">
                        <!-- ÜBERSCHRIFT DER SEITE -->
                        <h1 class="text-4xl lg:text-6xl font-bold">Deine <span class="text-green-700">grüne</span> Welt, unsere Auswahl</h1>
                        <!-- DEKORATIVER STRICH UNTER DER ÜBERSCHRIFT -->
                        <div class="w-20 h-2 bg-green-700 my-4"></div>
                        <!-- KURZE EINFÜHRUNGSTEXT -->
                        <p class="text-xl mb-10">Entdecke unsere Auswahl an Gartenutensilien und Dienstleistungen.</p>
                        <!-- BUTTON ZU DEN PRODUKTEN -->
                        <a class="bg-green-700 hover:bg-green-600 transition-colors duration-150 text-white text-2xl font-medium px-4 py-2 rounded-lg shadow" href="products.php">Zu den Produkten</a>
                    </div>
                </header>
            </div>
        </div>

        <!-- BILDTEIL DER HERO-SEKTION -->
        <img src="https://images.unsplash.com/photo-1536147116438-62679a5e01f2?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=634&q=80" 
             alt="Leafs" 
             class="w-full h-48 object-cover sm:h-screen sm:w-4/12">
        <!-- Ein Bild wird angezeigt, das sich responsiv verhält (auf größeren Geräten nimmt es 1/3 der Breite ein). -->
    </div>

    <!-- FOOTER -->
    <footer class="bg-green-700 text-white p-4">
        <div class="container mx-auto text-center">
            <p>&copy; 2025 Garten-Webshop</p>
            <!-- Copyright-Hinweis im Footer. -->
        </div>
    </footer>
</body>
</html>