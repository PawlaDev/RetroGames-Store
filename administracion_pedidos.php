<?php
session_start();
require_once 'include/conexion.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header('Location: administracion.php');
    exit();
}

// Obtener categorías de la base de datos y el carrito
$query = "SELECT id_categoria, nombre FROM Categorias";
$stmt = $conn->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cantidad_carrito = 0;
    if (isset($_SESSION['carrito'])) {
        $cantidad_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
    }

// Obtener los pedidos
$query = "SELECT id_pedido, id_cliente FROM pedidos";
$statement = $conn->prepare($query);
$statement->execute();
$pedidos = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Administración</title>
    <!-- Vincular hoja de estilos -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <!-- Imagen del logo -->
    <img src="logo.png" alt="Logo de la tienda" class="logo">

    <!-- Barra de navegación centrada -->
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
    
    <!-- Tabla de pedidos -->
    <main>
        <h1>Pedidos</h1>
        <table>
            <thead>
                <tr>
                    <th>Código Pedido</th>
                    <th>Código Cliente</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pedido['id_pedido']); ?></td>
                        <td><?php echo htmlspecialchars($pedido['id_cliente']); ?></td>
                        <td>
                        <form action="administracion_detalles.php" method="GET" style="margin: 0;">
                            <input type="hidden" name="id_pedido" value="<?php echo htmlspecialchars($pedido['id_pedido']); ?>">
                            <button type="submit">Ver detalles</button>
                        </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <nav>
            <a href="index.php">Cerrar sesión</a>
        </nav>
    </main>
</body>
</html>