<?php
//Iniciar la sesion para el carrito
session_start();
// Conexión a la base de datos
require_once 'include/conexion.php';

try {
    // Obtener todas las categorías para la cabecera
    $query_categorias = "SELECT id_categoria, nombre FROM Categorias";
    $stmt_categorias = $conn->prepare($query_categorias);
    $stmt_categorias->execute();
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

    // Validar la categoria solicitada a traves de 'id' en la URL
    // Primero, verifica que el parámetro sea un entero válido
    $id_categoria = isset($_GET['id']) ? intval($_GET['id']) : 0;
    // Despues se comprueba que la existencia de la categoria
    $query_categoria = "SELECT nombre FROM Categorias WHERE id_categoria = :id_categoria";
    $stmt_categoria = $conn->prepare($query_categoria);
    $stmt_categoria->execute(['id_categoria' => $id_categoria]);
    // Finalmente, se obtienen los datos de la categoría
    $categoria = $stmt_categoria->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        // Si la categoria no existe, se muestra un error y detiene el script
        die("Categoría no encontrada");
    }

    // Se obtienen los productos de la categoría seleccionada
    $query_productos = "
        SELECT p.id_producto, p.nombre AS producto_nombre, p.precio, p.descripcion, p.imagen_url 
        FROM Productos p WHERE p.id_categoria = :id_categoria";
    $stmt_productos = $conn->prepare($query_productos);
    $stmt_productos->execute(['id_categoria' => $id_categoria]);
    // De nuevo, se guarda en el array $productos
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener la cantidad total de productos en el carrito
    $cantidad_carrito = 0;
    if (isset($_SESSION['carrito'])) {
        $cantidad_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
    }

} catch (Exception $e) {
    // Mensaje de error en caso de que alguno las consultas falle
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titulo basado en la categoría seleccionada-->
    <title><?= htmlspecialchars($categoria['nombre']) ?></title>
    <!-- Hoja de estilos -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <!-- Logo -->
    <img src="logo.png" alt="Logo de la tienda" class="logo">

    <!-- Barra de navegación -->
    <nav>
        <a href="index.php">Inicio</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="categoria.php?id=<?= $cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
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
    <!-- Tñitulo -->        
    <h1><?= htmlspecialchars($categoria['nombre']) ?></h1>
    <!-- Listado de productos -->
    <div class="productos">
        <?php foreach ($productos as $producto): ?>
            <div class="producto">
                <!-- Imagen del producto -->
                <img src="<?= htmlspecialchars($producto['imagen_url']) ?>" alt="Imagen de <?= htmlspecialchars($producto['producto_nombre']) ?>">
                <div class="texto-producto">
                    <h3><?= htmlspecialchars($producto['producto_nombre']) ?></h3>
                    <p>Precio: <?= number_format($producto['precio'], 2) ?> €</p>
                    <p><?= htmlspecialchars($producto['descripcion']) ?></p>
                    <a href="producto.php?id=<?= $producto['id_producto'] ?>">Ver producto</a>
                </div>
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