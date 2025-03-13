<?php
session_start();
require 'include/conexion.php'; 

// Obtener las categorías para la barra de navegación
$query_categorias = "SELECT id_categoria, nombre FROM Categorias";
$stmt_categorias = $conn->prepare($query_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Inicializar el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    // Si no existe el carrito en la sesión, se inicializa como un array vacío
    $_SESSION['carrito'] = [];
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    // Obtiene el ID del producto que se va a eliminar
    $id_eliminar = $_GET['eliminar'];
    // Elimina el producto del carrito
    unset($_SESSION['carrito'][$id_eliminar]);
}

// Manejar actualizaciones del carrito (cambiar cantidad)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar' && isset($_POST['cantidad'])) {
    // Itera sobre las cantidades
    foreach ($_POST['cantidad'] as $id_producto => $cantidad) {
        // Asegura que la cantidad es válida y actualiza la cantidad del producto en el carrito
        if (isset($_SESSION['carrito'][$id_producto])) {
            //La cantidad mínima debe ser 1
            $_SESSION['carrito'][$id_producto]['cantidad'] = max(1, intval($cantidad));
        }
    }
}

// Obtener productos del carrito desde la base de datos
// Array para almacenar la información detallada de los productos
$productos_carrito = [];
// Variable para acumular el precio total
$total = 0;

// Itera sobre los productos en el carrito
foreach ($_SESSION['carrito'] as $id_producto => $producto) {
    // Consultar información del producto
    $stmt = $conn->prepare('SELECT id_producto, nombre, precio, imagen_url FROM Productos WHERE id_producto = :id_producto');
    $stmt->execute(['id_producto' => $id_producto]);
    $producto_db = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si el producto existe en la base de datos
    if ($producto_db) {
        // Añade la cantidad del carrito al producto
        $producto_db['cantidad'] = $producto['cantidad'];
        // Calcula el subtotal del producto
        $producto_db['subtotal'] = $producto_db['precio'] * $producto['cantidad'];
        // Suma el subtotal al total del carrito
        $total += $producto_db['subtotal'];
        // Añade el producto detallado al arreglo de productos del carrito
        $productos_carrito[] = $producto_db;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compras</title>
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

    <h1>Carrito</h1>

    <!-- Cuando el carrito no está vacío -->
    <?php if (!empty($productos_carrito)): ?>
    <form method="post" action="carrito.php">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos_carrito as $producto): ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($producto['imagen_url']) ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" style="width: 50px;">
                            <?= htmlspecialchars($producto['nombre']) ?>
                        </td>
                        <td>
                            <input type="hidden" name="accion" value="actualizar">
                            <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">
                            <input type="number" name="cantidad[<?= $producto['id_producto'] ?>]" value="<?= $producto['cantidad'] ?>" min="1">
                        </td>
                        <td><?= number_format($producto['subtotal'], 2) ?> €</td>
                        <td>
                            <a href="carrito.php?eliminar=<?= $producto['id_producto'] ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                    <td><?= number_format($total, 2) ?> €</td>
                </tr>
            </tfoot>
        </table>

        <div class="carrito-acciones">
            <button type="submit">Actualizar carrito</button>
            <a href="compra.php">Finalizar compra</a>
        </div>
    </form>
    <!-- Cuando el carrito está vacío -->
    <?php else: ?>
        <b>El carrito está vacío.</b>
    <?php endif; ?>
    
    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>
</body>
</html>