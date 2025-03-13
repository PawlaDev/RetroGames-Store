<?php
//Variables con los datos de coenxión
$host = "localhost"; 
$dbname = "tiendaretro"; 
$admin = "admin"; //usuario creado
$password = "admin"; //contraseña creada

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $admin, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error en la conexión -> " . $e->getMessage();
    exit;
}
?>