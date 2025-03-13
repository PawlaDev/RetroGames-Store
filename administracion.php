<?php
session_start();
require_once 'include/conexion.php';

// Obtener categorías de la base de datos
$query = "SELECT id_categoria, nombre FROM Categorias";
$stmt = $conn->prepare($query);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cantidad_carrito = 0;
    if (isset($_SESSION['carrito'])) {
        $cantidad_carrito = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
    }


// Datos de acceso 
$usuario_admin = 'admin';
$contraseña_admin = 'admin';

/// Verifica si se enviaron los datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['usuario'] === $usuario_admin && $_POST['contraseña'] === $contraseña_admin) {
        // Configura la sesión para el usuario autenticado
        $_SESSION['usuario'] = $usuario_admin;
        
        // Redirige a la página de administración de pedidos
        header('Location: administracion_pedidos.php');
        exit();
    } else { 
        //Si las credenciales no son correctas, mensaje de error
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Administración</title>
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

    <h1>Panel de Administración</h1>
    <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>

    <main>
        <div class="contenedor-compra">
            <!-- Usuario invitado -->
            <div class="caja">
                <h2>Iniciar sesión</h2>
                <form method="POST" action="">
                    <input type="text" name="usuario" placeholder="Usuario" required>
                    <input type="password" name="contraseña" placeholder="Contraseña" required>
                    <button type="submit">Ingresar</button>
                </form>
            </div>
        
        </div>
    </main>
</body>
</html>
