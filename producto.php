<?php
// Iniciar la sesión
session_start();
// Conexión a la base de datos
require_once 'include/conexion.php';

// Obtener las categorías de la base de datos
$query_categorias = "SELECT id_categoria, nombre FROM Categorias";
$stmt_categorias = $conn->prepare($query_categorias);
$stmt_categorias->execute();
// Recupera los resultados de la consulta
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Recupera el ID del producto de la URL, si no existe, asigna 0
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Consultar la información del producto
$query_producto = "SELECT id_producto, nombre, precio, descripcion, imagen_url FROM Productos WHERE id_producto = :id_producto";
$stmt_producto = $conn->prepare($query_producto);
$stmt_producto->execute(['id_producto' => $id_producto]);
$producto = $stmt_producto->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    // Si no se encuentra el producto, muestra un mensaje de error
    die("Producto no encontrado");
}

// Solciitud POST - Se añade producto al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    // Obtiene el ID del producto, el nombre y el precio
    $id_producto = intval($_POST['id_producto']);
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];

    //Verifica si la variable de sesión 'carrito' no existe
    if (!isset($_SESSION['carrito'])) {
        // Si no existe, la inicializa con un array vacío
        $_SESSION['carrito'] = [];
    }

    // Agregar el producto al carrito
    if (!isset($_SESSION['carrito'][$id_producto])) {
        // Si el producto no está en el carrito, lo añade con cantidad 1
        $_SESSION['carrito'][$id_producto] = [
            'id_producto' => $id_producto, 
            'nombre' => $nombre,
            'precio' => $precio,
            'cantidad' => 1,
        ];
    } else {
        // Si el producto ya está en el carrito, aumenta su cantidad en 1
        $_SESSION['carrito'][$id_producto]['cantidad']++;
    }

    // Redirige a la página del producto después de añadirlo al carrito
    header('Location: producto.php?id=' . $id_producto);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']) ?></title>
    <!-- Enlazar el archivo CSS -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <!-- Imagen del logo -->
    <img src="logo.png" alt="Logo de la tienda" class="logo">

    <!-- Barra de navegación -->
    <nav>
        <a href="index.php">Inicio</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="categoria.php?id=<?= $cat['id_categoria'] ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </a>
        <?php endforeach; ?>
        <a href="carrito.php" class="carrito">
            Carrito (<?= isset($_SESSION['carrito']) ? array_sum(array_column($_SESSION['carrito'], 'cantidad')) : 0 ?>)
        </a>
    </nav>

    <!-- Banner -->
    <div class="banner">
        <a href="index.php">
            <img src="image/banner.png" alt="Banner principal">
        </a>
    </div>

    <!-- Información del producto -->
    <div class="producto-container">
        <!-- Imagen del producto usando la URL de la base de datos -->
        <img 
            src="<?= htmlspecialchars($producto['imagen_url']) ?>" 
            alt="<?= htmlspecialchars($producto['nombre']) ?>" 
            class="imagen-producto"
            onclick="abrirLightbox(this)"
        >
        
        <!-- Detalles del producto -->
        <div class="producto-info">
            <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
            <p class="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></p>
            <p class="precio">Precio: <?= number_format($producto['precio'], 2) ?> €</p>
            <form method="post">
                <input type="hidden" name="id_producto" value="<?= htmlspecialchars($producto['id_producto']) ?>">
                <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>">
                <input type="hidden" name="precio" value="<?= htmlspecialchars($producto['precio']) ?>">
                <button type="submit">Añadir al carrito</button>
            </form>
            <p class="mensaje-ampliar">Haz clic en la imagen para ampliar</p>
        </div>
    </div>

    <!-- Contenedor del lightbox -->
    <div id="lightbox" onclick="cerrarLightbox()">
        <img id="imagen-ampliada" src="" alt="Vista ampliada">
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>

    <script>
        // Función para abrir el lightbox
        function abrirLightbox(imagen) {
            const lightbox = document.getElementById("lightbox");
            const imagenAmpliada = document.getElementById("imagen-ampliada");

            // Establece la fuente de la imagen ampliada a la imagen seleccionada
            imagenAmpliada.src = imagen.src;

            // Muestra el lightbox
            lightbox.style.display = "flex";
        }

        // Función para cerrar el lightbox
        function cerrarLightbox() {
            const lightbox = document.getElementById("lightbox");

            // Ocultar el lightbox
            lightbox.style.display = "none";
        }   
    </script>

</body>
</html>