<?php
session_start();
include("conexion.php");

$mensaje_error = "";

// Cerrar sesión
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header("Location: registro_inicio.php");
    exit();
}

// Procesar Registro (Inserta datos a la base de datos usando la conexión)
if (isset($_POST['accion']) && $_POST['accion'] === 'registro') {
    $nombre    = $_POST['nombre'];
    $apellido  = $_POST['apellido'];
    $correo    = $_POST['correo'];
    $numero    = $_POST['numero'];
    $direccion = $_POST['direccion'];
    $password  = $_POST['password'];

    // Usamos la columna 'contraseña' exacta como está en tu base de datos
    $sql = "INSERT INTO usuarios (nombre, apellido, correo, numero, direccion, contraseña) 
            VALUES ('$nombre', '$apellido', '$correo', '$numero', '$direccion', '$password')";

    if (mysqli_query($conexion, $sql)) {
        $_SESSION['usuario'] = $nombre;
        header("Location: index.php");
        exit();
    } else {
        $mensaje_error = "Error al registrar: " . mysqli_error($conexion);
    }
}

// Procesar Inicio de Sesión (Busca en la base de datos usando la conexión)
if (isset($_POST['accion']) && $_POST['accion'] === 'login') {
    $correo   = $_POST['login_correo'];
    $password = $_POST['login_pass'];

    // Buscamos coincidencia en la columna 'contraseña'
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo' AND contraseña = '$password'";
    $resultado = mysqli_query($conexion, $sql);

    if (!$resultado) {
        $mensaje_error = "Error en la base de datos: " . mysqli_error($conexion);
    } else if (mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_array($resultado);
        $_SESSION['usuario'] = $usuario['nombre'];
        header("Location: index.php");
        exit();
    } else {
        $mensaje_error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfumeria MM - Registro e Inicio de Sesión</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <h1>Perfumeria MM</h1>
    </header>
    <nav>
        <a href="index.php">Inicio</a>
        <a href="mujeres.php">Mujer</a>
        <a href="hombres.php">Hombre</a>
        <a href="bebes.php">Niños</a>
        <a href="registro_inicio.php" class="activo">Cuenta</a>
    </nav>

    <!-- Barra de Bienvenida y Botón de Cerrar Sesión -->
    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="barra-bienvenida">
            <span class="bienvenida-texto">¡Bienvenid@, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!</span>
            <a href="registro_inicio.php?cerrar_sesion=1" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    <?php endif; ?>

    <!-- Mensaje de Error si falla el registro o credenciales -->
    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-error">
            <p><?php echo htmlspecialchars($mensaje_error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Sección de Registro e Inicio de Sesión -->
    <section id="registro">
        <div class="contenedor-formularios">

            <!-- Formulario de Registro -->
            <div class="columna-formulario">
                <h2>Crear Cuenta</h2>
                <form id="form-registro" action="" method="POST">
                    <input type="hidden" name="accion" value="registro">

                    <label for="nombre">Nombre (máx. 11 letras):</label>
                    <input type="text" id="nombre" name="nombre" maxlength="11" placeholder="Escribe tu nombre"
                        required>

                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" maxlength="50" placeholder="Escribe tu apellido"
                        required>

                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" maxlength="255" placeholder="ejemplo@correo.com"
                        required>

                    <label for="telefono">Número de Teléfono:</label>
                    <input type="tel" id="telefono" name="numero" maxlength="12" placeholder="Ej. 6561234567"
                        pattern="[0-9]+" title="Solo se permiten números" required>

                    <label for="direccion">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" maxlength="150"
                        placeholder="Calle, número y colonia" required>

                    <label for="password">Contraseña (máx. 20 caracteres):</label>
                    <input type="password" id="password" name="password" maxlength="20"
                        placeholder="Crea una contraseña" required>
                    <button type="submit">Registrarse</button>
                </form>
            </div>

            <!-- Formulario de Inicio de Sesión -->
            <div class="columna-formulario">
                <h2>Iniciar Sesión</h2>
                <form id="form-login" class="formulario" action="" method="POST">
                    <input type="hidden" name="accion" value="login">

                    <label for="login-correo">Correo Electrónico:</label>
                    <input type="email" id="login-correo" name="login_correo" placeholder="correo@ejemplo.com" required>

                    <label for="login-pass">Contraseña:</label>
                    <input type="password" id="login-pass" name="login_pass" placeholder="Tu contraseña" required>
                    <button type="submit" class="btn-enviar">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        Martinez Monarrez Alondra Sofia <br>5°J<br>24308051280867
    </footer>
</body>

</html>

