<?php
session_start();
include("conexion.php");

$mensaje_error = "";
$mensaje_aviso = "";
$error_correo  = "";

// Variables para conservar los datos del formulario si hay error
$reg_nombre    = "";
$reg_apellido  = "";
$reg_correo    = "";
$reg_numero    = "";
$reg_direccion = "";

if (isset($_GET['aviso']) && $_GET['aviso'] === 'iniciar_sesion') {
    $mensaje_aviso = "Debes iniciar sesión con tu cuenta para poder comprar y agregar productos al carrito.";
}

// Cerrar sesión
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header("Location: registro_inicio.php");
    exit();
}

// Procesar Registro (Inserta datos a la base de datos usando la conexión)
if (isset($_POST['accion']) && $_POST['accion'] === 'registro') {
    $nombre    = isset($_POST['nombre']) ? trim($_POST['nombre']) : "";
    $apellido  = isset($_POST['apellido']) ? trim($_POST['apellido']) : "";
    $correo    = isset($_POST['correo']) ? trim($_POST['correo']) : "";
    $numero    = isset($_POST['numero']) ? trim($_POST['numero']) : "";
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : "";
    $password  = isset($_POST['password']) ? $_POST['password'] : "";

    // Conservar datos ingresados para no perderlos si hay error
    $reg_nombre    = $nombre;
    $reg_apellido  = $apellido;
    $reg_correo    = $correo;
    $reg_numero    = $numero;
    $reg_direccion = $direccion;

    // Escapar el correo para la consulta
    $correo_esc = mysqli_real_escape_string($conexion, $correo);

    // 1. Verificar si el correo ya está registrado en la base de datos
    $sql_check = "SELECT * FROM usuarios WHERE correo = '$correo_esc'";
    $res_check = mysqli_query($conexion, $sql_check);

    if ($res_check && mysqli_num_rows($res_check) > 0) {
        // Mensaje específico para mostrar debajo de la casilla del correo
        $error_correo = "Este correo ya está usado. Por favor intenta con otro o inicia sesión.";
    } else {
        // 2. Si no está usado, procedemos a registrar el usuario
        $nombre_esc    = mysqli_real_escape_string($conexion, $nombre);
        $apellido_esc  = mysqli_real_escape_string($conexion, $apellido);
        $numero_esc    = mysqli_real_escape_string($conexion, $numero);
        $direccion_esc = mysqli_real_escape_string($conexion, $direccion);
        $password_esc  = mysqli_real_escape_string($conexion, $password);

        // Usamos la columna 'contraseña' exacta como está en tu base de datos
        $sql = "INSERT INTO usuarios (nombre, apellido, correo, numero, direccion, contraseña) 
                VALUES ('$nombre_esc', '$apellido_esc', '$correo_esc', '$numero_esc', '$direccion_esc', '$password_esc')";

        if (mysqli_query($conexion, $sql)) {
            $_SESSION['usuario'] = $nombre;
            $_SESSION['id_usuario'] = mysqli_insert_id($conexion);
            $_SESSION['correo'] = $correo;
            header("Location: index.php");
            exit();
        } else {
            $mensaje_error = "Error al registrar: " . mysqli_error($conexion);
        }
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
        $_SESSION['id_usuario'] = isset($usuario['id']) ? $usuario['id'] : 1;
        $_SESSION['correo'] = $usuario['correo'];
        header("Location: index.php");
        exit();
    } else {
        $mensaje_error = "Correo o contraseña incorrectos.";
    }
}

// Contar productos en el carrito
$total_carrito = 0;
if (isset($_SESSION['carrito'])) {
    if (is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $producto) {
            if (is_array($producto) && isset($producto['cantidad'])) {
                $total_carrito += $producto['cantidad'];
            } else {
                $total_carrito++;
            }
        }
    } else {
        $total_carrito = (int)$_SESSION['carrito'];
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
        <a href="carrito.php" class="btn-carrito" title="Ver Carrito de Compras">
            <img src="carrito.png" alt="Carrito">
            Carrito<?php if ($total_carrito > 0) { echo " ($total_carrito)"; } ?>
        </a>
    </nav>

    <!-- Barra de Bienvenida y Botón de Cerrar Sesión -->
    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="barra-bienvenida">
            <span class="bienvenida-texto">¡Bienvenid@, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!</span>
            <a href="registro_inicio.php?cerrar_sesion=1" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    <?php endif; ?>

    <!-- Mensaje de Aviso cuando intentan comprar sin sesión -->
    <?php if (!empty($mensaje_aviso)): ?>
        <div class="mensaje-error" style="background-color: #fff8e6; color: #8a6d3b; border-left-color: #c8b29b;">
            <p><?php echo htmlspecialchars($mensaje_aviso); ?></p>
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
                        value="<?php echo htmlspecialchars($reg_nombre); ?>" required>

                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" maxlength="50" placeholder="Escribe tu apellido"
                        value="<?php echo htmlspecialchars($reg_apellido); ?>" required>

                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" maxlength="255" placeholder="ejemplo@correo.com"
                        value="<?php echo htmlspecialchars($reg_correo); ?>"
                        class="<?php echo !empty($error_correo) ? 'input-error' : ''; ?>"
                        <?php echo !empty($error_correo) ? 'autofocus' : ''; ?>
                        required>
                    <?php if (!empty($error_correo)): ?>
                        <span class="error-campo"><?php echo htmlspecialchars($error_correo); ?></span>
                    <?php endif; ?>

                    <label for="telefono">Número de Teléfono:</label>
                    <input type="tel" id="telefono" name="numero" maxlength="12" placeholder="Ej. 6561234567"
                        pattern="[0-9]+" title="Solo se permiten números"
                        value="<?php echo htmlspecialchars($reg_numero); ?>" required>

                    <label for="direccion">Dirección:</label>
                    <input type="text" id="direccion" name="direccion" maxlength="150"
                        placeholder="Calle, número y colonia"
                        value="<?php echo htmlspecialchars($reg_direccion); ?>" required>

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

