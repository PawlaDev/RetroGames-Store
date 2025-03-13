<?php
// Inicio de la sesión para manejar el carrito
session_start();

// Conexión a la base de datos
require_once 'include/conexion.php';

// Obtener categorías de la base de datos
$query = "SELECT id_categoria, nombre FROM Categorias";// Consulta SQL para obtener las categorías
// Prepara la consulta para evitar inyecciones SQL
$stmt = $conn->prepare($query);
// Ejecuta la consulta
$stmt->execute();
// Obtiene todas las categorías como un array
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calcular la cantidad de productos en el carrito
//Se inicia la variable en 0
$cantidad_carrito = 0; 
    //Verifica si existe un carrito en la sesión
    if (isset($_SESSION['carrito'])) { 
        //Si existe, suma las cantidades de todos los productos
        $cantidad_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
    }

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Inicio</title>
    <!-- Hoja de estilos -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <!-- Logo -->
    <img src="logo.png" alt="Logo de la tienda" class="logo">

    <!-- Barra de navegación -->
    <nav>
        <a href="index.php">Inicio</a>
        <?php foreach ($categorias as $categoria): ?>
            <a href="categoria.php?id=<?= $categoria['id_categoria'] ?>">
                <?= htmlspecialchars($categoria['nombre']) ?>
            </a>
        <?php endforeach; ?>
        <!-- Carrito -->
        <a href="carrito.php" class="carrito">
            Carrito (<?= $cantidad_carrito ?>)
        </a>
    </nav>
    <!-- Banner -->        
    <div class="banner">
        <a href="index.php">
            <img src="image/banner.png" alt="Banner principal">
        </a>
    </div>

    <!-- Título -->
    <h1>Categorías</h1>

    <!-- Categorías -->
    <div class="categorias">
        <?php foreach ($categorias as $categoria): ?>
            <div class="categoria">
                <!-- Imagen de cada categoría -->
                <img src="image/<?= htmlspecialchars($categoria['nombre']) ?>.png" alt="Imagen de <?= htmlspecialchars($categoria['nombre']) ?>">
                <a href="categoria.php?id=<?= $categoria['id_categoria'] ?>">
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>
</body>
</html>
