<?php
session_start();
require_once 'include/conexion.php';

// Obtener categorías de la base de datos para la barra de navegaciuón
$query = "SELECT id_categoria, nombre FROM Categorias";
$stmt = $conn->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vacía el carrito y los datos del usuario
unset($_SESSION['carrito']);
unset($_SESSION['datos_usuario']);

$cantidad_carrito = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gracias por su compra</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <img src="logo.png" alt="Logo de la tienda" class="logo">

    <header>
        <nav>
            <a href="index.php">Inicio</a>
            <?php foreach ($categorias as $cat): ?>
                <a href="categoria.php?id=<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></a>
            <?php endforeach; ?>
            <a href="carrito.php" class="carrito">Carrito (0)</a>
        </nav>
        <div class="banner">
            <a href="index.php">
                <img src="image/banner.png" alt="Banner principal">
            </a>
        </div>
    </header>

    <h1>Gracias por su compra</h1>
    <h1>Su pedido ha sido confirmado</h1>

    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>

</body>
</html>
