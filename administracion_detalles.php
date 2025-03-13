<?php
session_start();
require_once 'include/conexion.php';

//Verifica si está autenticado como administrador, si no lo redirige a la página de administración
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header('Location: administracion.php');
    exit();
}

// Verifica que se haya pasado un ID de pedido valido
if (!isset($_GET['id_pedido']) || empty($_GET['id_pedido'])) {
    die("No se ha especificado un ID de pedido válido.");
}

// Obtener categorías de la base de datos y el número del carrito
$query = "SELECT id_categoria, nombre FROM Categorias";
$stmt = $conn->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cantidad_carrito = 0;
if (isset($_SESSION['carrito'])) {
    $cantidad_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
}

// Se sanitiza el ID del pedido para que sea un número entero
$id_pedido = intval($_GET['id_pedido']);

// Consulta SQL para el cliente asociado al pedido
$query_clientes = "
    SELECT 
        c.id_cliente,
        COALESCE(cr.nombre, 'N/A') AS nombre,
        COALESCE(cr.apellidos, 'N/A') AS apellidos,
        COALESCE(cr.nif, 'N/A') AS nif,
        IF(cr.id_cliente IS NOT NULL, 'Registrado', 'Invitado') AS tipo_cliente,
        COALESCE(fe.email, 'Sin email') AS factura_email,
        COALESCE(fe.direccionenv, 'Sin dirección') AS factura_direccion
    FROM Clientes c
    LEFT JOIN Cliente_Registrado cr ON c.id_cliente = cr.id_cliente
    INNER JOIN Pedidos p ON c.id_cliente = p.id_cliente
    LEFT JOIN Factura_Envio fe ON p.id_fact_env = fe.id_fact_env
    WHERE p.id_pedido = :id_pedido
";
$stmt_clientes = $conn->prepare($query_clientes);
$stmt_clientes->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
$stmt_clientes->execute();
$clientes = $stmt_clientes->fetch(PDO::FETCH_ASSOC);

if (!$clientes) {
    // Si no se encuentra información para el pedido, muestra un mensaje de error y termina el script
    die("No se encontró información para el pedido con ID: $id_pedido.");
}

// Consulta para obtener los productos asociados al pedido
$query_productos = "
    SELECT lp.unidades AS cantidad, pr.nombre AS producto_nombre
    FROM Lineas_Pedido lp
    INNER JOIN Productos pr ON lp.id_producto = pr.id_producto
    WHERE lp.id_pedido = :id_pedido
";
$stmt_productos = $conn->prepare($query_productos);
$stmt_productos->bindParam(':id_pedido', $id_pedido, PDO::PARAM_INT);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles - Administración</title>
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

    <div class="banner">
        <a href="index.php">
            <img src="image/banner.png" alt="Banner principal">
        </a>
    </div>

    <main>
        <h1>Detalles del Pedido (ID: <?= htmlspecialchars($id_pedido) ?>)</h1>
        
        <!-- Información del usuario -->
        <div class="caja-usuario">
            <h2>Información del cliente</h2>
            <p><strong>ID cliente:</strong> <?= htmlspecialchars($clientes['id_cliente']) ?></p>
            <p><strong>Tipo de cliente:</strong> <?= htmlspecialchars($clientes['tipo_cliente']) ?></p>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($clientes['nombre']) ?></p>
            <p><strong>Apellidos:</strong> <?= htmlspecialchars($clientes['apellidos']) ?></p>
            <p><strong>NIF:</strong> <?= htmlspecialchars($clientes['nif']) ?></p>
            <p><strong>Email (Factura):</strong> <?= htmlspecialchars($clientes['factura_email']) ?></p>
            <p><strong>Dirección (Factura):</strong> <?= htmlspecialchars($clientes['factura_direccion']) ?></p>
        </div>

        <!-- Información de los productos -->
        <div class="caja-productos">
            <h2>Productos del Pedido</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cantidad</th>
                        <th>Producto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= htmlspecialchars($producto['cantidad']) ?></td>
                            <td><?= htmlspecialchars($producto['producto_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav>
            <a href="index.php">Cerrar sesión</a>
        </nav>
    </main>
</body>
</html>