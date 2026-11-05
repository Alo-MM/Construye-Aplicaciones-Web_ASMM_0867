<?php
session_start();
include("conexion.php");

// Cerrar sesión
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfumeria MM</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <h1>Perfumeria MM</h1>
    </header>
    <nav>
        <a href="index.php" class="activo">Inicio</a>
        <a href="mujeres.php">Mujer</a>
        <a href="hombres.php">Hombre</a>
        <a href="bebes.php">Niños</a>
        <a href="registro_inicio.php">Cuenta</a>
    </nav>

    <!-- Barra de Bienvenida y Botón de Cerrar Sesión -->
    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="barra-bienvenida">
            <span class="bienvenida-texto">¡Bienvenid@, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!</span>
            <a href="index.php?cerrar_sesion=1" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    <?php endif; ?>
     
    <!-- Sección de Información / Bienvenida Clásica -->
    <section class="seccion-intro">
        <div class="caja-intro">
            <p>
                En nuestra tienda encontrarás una gran variedad de perfumes de las mejores marcas como lo son <strong>Zara</strong> y <strong>Dior</strong>.
                Ven con nosotros y encuentra los mejores precios para fragancias totalmente originales; tenemos opciones para mujer, hombre y hasta para los más pequeños de la casa,
                no te pierdas de todos los perfumes que tenemos para ti y toda tu familia.<br>
                ¡Únete a esta gran familia y siempre huele rico con la<br><strong>Perfumería MM</strong>!
            </p>
        </div>
    </section>

    <footer>
        Martinez Monarrez Alondra Sofia <br>5°J<br>24308051280867
    </footer>
</body>

</html>