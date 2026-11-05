<?php

$servidor = "localhost";               
$usuario  = "root";                    
$password = "";                        
$db       = "perfumeria_mm";

$conexion = mysqli_connect($servidor, $usuario, $password, $db);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");
?>