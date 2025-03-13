<?php
//Inicia la sesión e incluye la base de datos
session_start();
require 'include/conexion.php'; 

// Obtiene las categorías de la base de datos para la barra de navegación
$query_categorias = "SELECT id_categoria, nombre FROM Categorias";
$stmt_categorias = $conn->prepare($query_categorias);
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Inicializa carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    die("El carrito está vacío. Añade productos antes de continuar.");
}

// Variables 
// Almacena mensajes de error 
$errores = [];
// Almacena mensajes de confirmación
$confirmacion = "";

// Formulario
// Verifica si la solicitud se envió mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura el tipo de usuario (invitado o registrado)
    $tipo_usuario = $_POST['tipo_usuario'];

    // Datos comunes
    // Se valida y limpia el correo electrónico
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    //Limpia el formato de la dirección
    $direccion = trim($_POST['direccion']);
    // Array inical para los datos comunes
    $datos_usuario = [ 
        'tipo_usuario' => $tipo_usuario,
        'email' => $email,
        'direccion' => $direccion
    ];


    // Calcular el importe de la compra actual
    $importe_compra_actual = 0;
    foreach ($_SESSION['carrito'] as $producto) {
        $importe_compra_actual += $producto['precio'] * $producto['cantidad'];
    }

    //Si el usuario es registrado
    if ($tipo_usuario === 'registrado') {
        // Captura los nuevos campos
        $nif = trim($_POST['nif']);
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $password = trim($_POST['password']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        // Decha actual
        $fecha_actual = date('Y-m-d'); 
    
        if (empty($nif) || empty($nombre) || empty($password) || empty($email) || empty($direccion)) {
            $errores[] = "Todos los campos son obligatorios para usuarios registrados.";
        } else {
            try {
                //Si no hay errores, se inicia la transaccion
                $conn->beginTransaction();
                
                // Verifica si el usuario ya está registrado
                $stmt = $conn->prepare("SELECT * FROM Cliente_Registrado WHERE email = ?");
                $stmt->execute([$email]);
                $cliente_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($cliente_existente) {
                    // Si el usuario existe, se actualizan algunos datos
                    $id_cliente = $cliente_existente['id_cliente'];
                    $importe_acumulado = $cliente_existente['importe_acumulado_compras'] + $importe_compra_actual;
                    $numero_compras = $cliente_existente['numero_compras'] + 1;
    
                    $stmt = $conn->prepare("UPDATE Cliente_Registrado 
                                            SET fecha_ultima_compra = ?, 
                                                importe_acumulado_compras = ?, 
                                                numero_compras = ? 
                                            WHERE id_cliente = ?");
                    $stmt->execute([$fecha_actual, $importe_acumulado, $numero_compras, $id_cliente]);
                } else {
                    // Si no existe, se crea un nuevo cliente
                    $stmt = $conn->prepare("INSERT INTO Clientes () VALUES ()");
                    $stmt->execute();
                    $id_cliente = $conn->lastInsertId();
    
                    $stmt = $conn->prepare("INSERT INTO Cliente_Registrado 
                                            (id_cliente, nif, nombre, apellidos, email, contraseña, fecha_nacimiento, 
                                             fecha_primera_compra, fecha_ultima_compra, importe_acumulado_compras, 
                                             numero_compras, baja_logica) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id_cliente, $nif, $nombre, $apellidos, $email, password_hash($password, PASSWORD_BCRYPT), 
                        $fecha_nacimiento, $fecha_actual, $fecha_actual, 
                        $importe_compra_actual, 1, false
                    ]);
                }

                // Se insertan los datos en la tabla Factura_Envio
                $stmt = $conn->prepare("INSERT INTO Factura_Envio 
                (nif, nombre, apellidos, direccion, email, nombreenv, apellidosenv, direccionenv) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nif, $nombre, $apellidos, $direccion, $email, 
                    $nombre, $apellidos, $direccion]);
                $id_fact_env = $conn->lastInsertId();

                // Se insertan los datos en la tabla Pedidos
                $fecha_pedido = date('Y-m-d');
                $hora_pedido = date('H:i:s');
                $ip_cliente = $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare("
                    INSERT INTO Pedidos 
                    (id_cliente, id_fact_env, total_pedido, fecha_pedido, hora_pedido, direc_ip_compra, id_estado, id_pago, id_envio) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_cliente, $id_fact_env, $importe_compra_actual, $fecha_pedido, 
                    //Se establecen los estados de envío como pendiente, pago contrareembolso y envio estandar como predeterminado
                    $hora_pedido, $ip_cliente, 1, 2, 1 
                ]);
                $id_pedido = $conn->lastInsertId();

                // c de las lineas de pedido
                foreach ($_SESSION['carrito'] as $producto) {
                    $id_producto = $producto['id_producto'];
                    $descripcion = $producto['nombre'];
                    $unidades = $producto['cantidad'];
                    $precio_unitario = $producto['precio'];
                    $precio_total = $unidades * $precio_unitario;
                
                    $stmt = $conn->prepare("
                        INSERT INTO Lineas_Pedido 
                        (id_pedido, id_producto, descripcion, unidades, precio_unitario, precio_total) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id_pedido, $id_producto, $descripcion, $unidades, $precio_unitario, $precio_total
                    ]);
                }

                $conn->commit();

                // Se agregan los campos al array datos_usuario
                $datos_usuario['nombre'] = $nombre;
                $datos_usuario['apellidos'] = $apellidos;
                $datos_usuario['nif'] = $nif;
                $datos_usuario['fecha_nacimiento'] = $fecha_nacimiento;
                $datos_usuario['email'] = $email;
                $datos_usuario['direccion'] = $direccion;
                $datos_usuario['id_fact_env'] = $id_fact_env;
                

                // Confirmación
                $_SESSION['datos_usuario'] = $datos_usuario;
                header('Location: compra_confirmar.php');
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                $errores[] = "Error al procesar el pedido: " . $e->getMessage();
            }
        }
    }

    if ($tipo_usuario === 'invitado') {
        // Se validan los campos requeridos
        if (empty($email) || empty($direccion)) {
            $errores[] = "El correo electrónico y la dirección son obligatorios para usuarios invitados.";
        } else {
            try {
                $conn->beginTransaction();
    
                // Se insertan los datos en la tabla Clientes
                $stmt = $conn->prepare("INSERT INTO Clientes () VALUES ()");
                $stmt->execute();
                $id_cliente = $conn->lastInsertId();
    
                // Se insertan los datos en la tabla Cliente_No_Registrado
                $stmt = $conn->prepare("INSERT INTO Cliente_No_Registrado (id_cliente) VALUES (?)");
                $stmt->execute([$id_cliente]);
    
                // Se insertan los datos en la tabla Factura_Envio
                $stmt = $conn->prepare("INSERT INTO Factura_Envio (direccionenv, email) VALUES (?, ?)");
                $stmt->execute([$direccion, $email]);
                $id_fact_env = $conn->lastInsertId();
    
                //Se insertan los datos en la tabla Pedidos
                $fecha_pedido = date('Y-m-d');
                $hora_pedido = date('H:i:s');
                $ip_cliente = $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare("
                    INSERT INTO Pedidos 
                    (id_cliente, id_fact_env, total_pedido, fecha_pedido, hora_pedido, direc_ip_compra, id_estado, id_pago, id_envio) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id_cliente, $id_fact_env, $importe_compra_actual, $fecha_pedido, 
                    $hora_pedido, $ip_cliente, 1, 2, 1 // Estado predeterminado
                ]);
                $id_pedido = $conn->lastInsertId();
    
                // Se insertan los datos en la tablade las líneas de pedidos
                foreach ($_SESSION['carrito'] as $producto) {
                    $id_producto = $producto['id_producto'];
                    $descripcion = $producto['nombre'];
                    $unidades = $producto['cantidad'];
                    $precio_unitario = $producto['precio'];
                    $precio_total = $unidades * $precio_unitario;
    
                    $stmt = $conn->prepare("
                        INSERT INTO Lineas_Pedido 
                        (id_pedido, id_producto, descripcion, unidades, precio_unitario, precio_total) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $id_pedido, $id_producto, $descripcion, $unidades, $precio_unitario, $precio_total
                    ]);
                }
    
                $conn->commit();
    
                // Confirmación y redirección
                $_SESSION['datos_usuario'] = [
                    'tipo_usuario' => $tipo_usuario,
                    'email' => $email,
                    'direccion' => $direccion,
                    'id_fact_env' => $id_fact_env,
                    'id_pedido' => $id_pedido
                ];
                header('Location: compra_confirmar.php');
                exit;
            } catch (Exception $e) {
                $conn->rollBack();
                $errores[] = "Error al procesar el pedido del usuario invitado: " . $e->getMessage();
            }
        }
    }

    if (empty($errores)) {
        // Guardar datos del usuario en la sesión
        $_SESSION['datos_usuario'] = $datos_usuario;

        // Redirigir a compra_confirmar.php
        header('Location: compra_confirmar.php');
        exit; // Asegurarse de que no se ejecute más código
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar compra</title>
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

    <h1>Finalizar compra</h1>
    
    <main>
        <div class="contenedor-compra">
            <!-- Usuario invitado -->
            <div class="caja">
                <h2>Usuario invitado</h2>
                <form action="compra.php" method="POST">
                    <input type="hidden" name="tipo_usuario" value="invitado">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                    <input type="text" name="direccion" placeholder="Dirección" required>
                    <button type="submit">Finalizar compra</button>
                </form>
            </div>

            <!-- Usuario registrado -->
            <div class="caja">
                <h2>Usuario registrado</h2>
                <form action="compra.php" method="POST">
                    <input type="hidden" name="tipo_usuario" value="registrado">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellidos" placeholder="Apellidos">
                    <input type="email" name="email" placeholder="Correo electrónico" required>
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <input type="text" name="nif" placeholder="NIF" required>
                    <a1>Fecha nacimiento</a1>
                    <input type="date" name="fecha_nacimiento" placeholder="Fecha de nacimiento">
                    <input type="text" name="direccion" placeholder="Dirección" required>
                    <button type="submit">Finalizar compra</button>
                </form>
            </div>
        </div>
    </main>
    <!-- Pie de página -->
    <div class="footer">
        <a href="administracion.php">
            Admin</a>
    </div>
</body>
</html>