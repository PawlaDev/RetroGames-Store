<?php
session_start();
require 'include/conexion.php';

// Verifica si el carrito está vacío o no existe en la sesión
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    // Si no hay productos en el carrito, detiene el script y muestra el mensaje
    die("El carrito está vacío. Añade productos antes de continuar.");
}
// Verifica si se han enviado los datos del usuario a través de la sesion
if (!isset($_SESSION['datos_usuario'])) {
    // Si no hay datos del usuario, detiene el script y muestra wl mensaje
    die("No se han enviado los datos del usuario. Por favor, vuelve a completar el formulario.");
}

// Se obtienen las categorías de la base de datos para la barra de navegación
$query_categorias = "SELECT id_categoria, nombre FROM Categorias";
$stmt_categorias = $conn->prepare($query_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Recupera los datos del usuario y el carrito almacenados en la sesión
$datos_usuario = $_SESSION['datos_usuario'];
$carrito = $_SESSION['carrito'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar compra</title>
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
            <a href="carrito.php" class="carrito">Carrito (<?= count($_SESSION['carrito']) ?>)</a>
        </nav>
        <div class="banner">
            <a href="index.php">
                <img src="image/banner.png" alt="Banner principal">
            </a>
        </div>
    </header>

    <!-- Título de la página -->
    <h1>Confirmar compra</h1>

    <main>
        <!-- Sección con los datos del usuario -->
        <section>
            <h2>Datos de envío</h2>
            <p><strong>Tipo de usuario:</strong> <?= htmlspecialchars($datos_usuario['tipo_usuario']) ?></p>
            <?php if ($datos_usuario['tipo_usuario'] === 'registrado'): ?>
                <!-- Información adicional para usuarios registrados -->
                <p><strong>Nombre:</strong> <?= htmlspecialchars($datos_usuario['nombre']) ?></p>
                <p><strong>Apellidos:</strong> <?= htmlspecialchars($datos_usuario['apellidos'] ?? 'No especificado') ?></p>
                <p><strong>Nif:</strong> <?= htmlspecialchars($datos_usuario['nif'] ?? 'No especificado') ?></p>
                <p><strong>Fecha de nacimiento:</strong> <?= htmlspecialchars($datos_usuario['fecha_nacimiento'] ?? 'No especificado') ?></p>
            <?php endif; ?>
            <p><strong>Correo electrónico:</strong> <?= htmlspecialchars($datos_usuario['email']) ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($datos_usuario['direccion']) ?></p>
        </section>

        <!-- Sección con el resumen del carrito -->                           
        <section>
            <h2>Resumen de produtos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($carrito as $producto):
                        $subtotal = $producto['precio'] * $producto['cantidad'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <!-- Muestra los detalles de cada producto -->
                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                            <td><?= number_format($producto['precio'], 2) ?> €</td>
                            <td><?= $producto['cantidad'] ?></td>
                            <td><?= number_format($subtotal, 2) ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th><?= number_format($total, 2) ?> €</th>
                    </tr>
                </tfoot>
            </table>
        </section> 
        <!-- Botón para confirmar el pedido -->
        <div class="caja">
        <form action="agradecimiento.php" method="POST">
            <input type="hidden" name="confirmar" value="1">
            <button type="submit">Confirmar y realizar pedido</button>
        </form>
        </div>
    </main>

    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>
</body>
</html>
