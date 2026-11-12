<?php
session_start();
include("conexion.php");

$mensaje_exito = "";
$mensaje_error = "";
$compra_exitosa = false;

// REGLA: Si no ha iniciado sesión, redirigir a registro_inicio.php
if (!isset($_SESSION['usuario'])) {
    header("Location: registro_inicio.php?aviso=iniciar_sesion");
    exit();
}

// 1. Obtener el correo y los datos del usuario que inició sesión
$correo_usuario_actual = "";
$cliente_id_actual     = 0;

if (isset($_SESSION['correo']) && !empty($_SESSION['correo'])) {
    $correo_usuario_actual = $_SESSION['correo'];
    $correo_esc = mysqli_real_escape_string($conexion, $correo_usuario_actual);
    $consulta_u = mysqli_query($conexion, "SELECT id, correo FROM usuarios WHERE correo = '$correo_esc' LIMIT 1");
    if ($consulta_u && $u = mysqli_fetch_assoc($consulta_u)) {
        $cliente_id_actual = (int)$u['id'];
    }
} elseif (isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] > 0) {
    $id_busc = (int)$_SESSION['id_usuario'];
    $consulta_u = mysqli_query($conexion, "SELECT id, correo FROM usuarios WHERE id = '$id_busc' LIMIT 1");
    if ($consulta_u && $u = mysqli_fetch_assoc($consulta_u)) {
        $cliente_id_actual = (int)$u['id'];
        $correo_usuario_actual = $u['correo'];
        $_SESSION['correo'] = $u['correo'];
    }
} elseif (isset($_SESSION['usuario'])) {
    $nombre_busc = mysqli_real_escape_string($conexion, $_SESSION['usuario']);
    $consulta_u = mysqli_query($conexion, "SELECT id, correo FROM usuarios WHERE nombre = '$nombre_busc' ORDER BY id DESC LIMIT 1");
    if ($consulta_u && $u = mysqli_fetch_assoc($consulta_u)) {
        $cliente_id_actual = (int)$u['id'];
        $correo_usuario_actual = $u['correo'];
        $_SESSION['correo'] = $u['correo'];
    }
}

// 2. Comprobar si el usuario ya ha comprado anteriormente buscando en la tabla pagos
$datos_pago_previos = null;
$ya_ha_comprado = false;

if ($cliente_id_actual > 0 || !empty($correo_usuario_actual)) {
    $correo_esc = mysqli_real_escape_string($conexion, $correo_usuario_actual);
    $consulta_pago_anterior = mysqli_query($conexion, "SELECT * FROM pagos WHERE cliente = '$cliente_id_actual' OR correo = '$correo_esc' ORDER BY id DESC LIMIT 1");
    if ($consulta_pago_anterior && mysqli_num_rows($consulta_pago_anterior) > 0) {
        $datos_pago_previos = mysqli_fetch_assoc($consulta_pago_anterior);
        $ya_ha_comprado = true;
    }
}

// 3. Procesar el formulario de pago y compra
if (isset($_POST['accion']) && $_POST['accion'] === 'finalizar_compra') {
    if (empty($_SESSION['carrito'])) {
        $mensaje_error = "Tu carrito está vacío. Agrega productos antes de procesar el pago.";
    } else {
        // El correo es de solo lectura y se toma directamente del usuario autenticado
        $correo_ingresado = $correo_usuario_actual;
        $correo_escaped   = mysqli_real_escape_string($conexion, $correo_ingresado);
        $cliente_id       = $cliente_id_actual;

        // Obtener y validar campos de envío y tarjeta
        $receptor        = isset($_POST['receptor']) ? trim($_POST['receptor']) : '';
        $telefono        = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
        $direccion       = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
        $ciudad          = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
        $estado          = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $codigo_postal   = isset($_POST['codigo_postal']) ? trim($_POST['codigo_postal']) : '';
        $tipo_tarjeta    = isset($_POST['tipo_tarjeta']) ? trim($_POST['tipo_tarjeta']) : '';
        $tipo_cuenta     = isset($_POST['tipo_cuenta']) ? trim($_POST['tipo_cuenta']) : '';
        $titular_tarjeta = isset($_POST['titular_tarjeta']) ? trim($_POST['titular_tarjeta']) : '';
        $numero_tarjeta  = isset($_POST['numero_tarjeta']) ? trim($_POST['numero_tarjeta']) : '';
        $expiracion      = isset($_POST['expiracion']) ? trim($_POST['expiracion']) : '';
        $cvv             = isset($_POST['cvv']) ? trim($_POST['cvv']) : '';

        if (
            empty($receptor) || empty($telefono) || empty($direccion) || 
            empty($ciudad) || empty($estado) || empty($codigo_postal) || 
            empty($tipo_tarjeta) || empty($tipo_cuenta) || empty($titular_tarjeta) || 
            empty($numero_tarjeta) || empty($expiracion) || empty($cvv)
        ) {
            $mensaje_error = "Por favor completa todos los campos obligatorios del formulario.";
        } else {
            // Calcular el total a pagar
            $monto_total = 0.0;
            foreach ($_SESSION['carrito'] as $producto) {
                $monto_total += ((float)$producto['precio'] * (int)$producto['cantidad']);
            }

            // Escapar datos de texto para seguridad SQL
            $receptor_esc        = mysqli_real_escape_string($conexion, $receptor);
            $telefono_esc        = mysqli_real_escape_string($conexion, $telefono);
            $direccion_esc       = mysqli_real_escape_string($conexion, $direccion);
            $ciudad_esc          = mysqli_real_escape_string($conexion, $ciudad);
            $estado_esc          = mysqli_real_escape_string($conexion, $estado);
            $cp_esc              = mysqli_real_escape_string($conexion, $codigo_postal);
            $tipo_tarjeta_esc    = mysqli_real_escape_string($conexion, $tipo_tarjeta);
            $tipo_cuenta_esc     = mysqli_real_escape_string($conexion, $tipo_cuenta);
            $titular_esc         = mysqli_real_escape_string($conexion, $titular_tarjeta);
            $num_tarjeta_esc     = mysqli_real_escape_string($conexion, $numero_tarjeta);
            $expiracion_esc      = mysqli_real_escape_string($conexion, $expiracion);
            $cvv_esc             = mysqli_real_escape_string($conexion, $cvv);

            // COMPROBAR: Si el usuario ya había comprado, ACTUALIZAR su registro en pagos.
            // Si es un usuario nuevo que no ha comprado, INSERTAR un nuevo registro.
            $check_pago = mysqli_query($conexion, "SELECT id FROM pagos WHERE cliente = '$cliente_id' OR correo = '$correo_escaped' LIMIT 1");

            if ($check_pago && mysqli_num_rows($check_pago) > 0) {
                $pago_existente = mysqli_fetch_assoc($check_pago);
                $pago_id = (int)$pago_existente['id'];

                $sql_pago = "UPDATE pagos SET 
                                fecha = CURDATE(),
                                receptor = '$receptor_esc',
                                telefono = '$telefono_esc',
                                direccion = '$direccion_esc',
                                ciudad = '$ciudad_esc',
                                estado = '$estado_esc',
                                codigo_postal = '$cp_esc',
                                tipo_tarjeta = '$tipo_tarjeta_esc',
                                tipo_cuenta = '$tipo_cuenta_esc',
                                titular = '$titular_esc',
                                numero_tarjeta = '$num_tarjeta_esc',
                                expiracion = '$expiracion_esc',
                                cvv = '$cvv_esc',
                                total = '$monto_total'
                             WHERE id = '$pago_id'";
                $pago_registrado = mysqli_query($conexion, $sql_pago);
            } else {
                $sql_pago = "INSERT INTO pagos (fecha, cliente, correo, receptor, telefono, direccion, ciudad, estado, codigo_postal, tipo_tarjeta, tipo_cuenta, titular, numero_tarjeta, expiracion, cvv, total)
                             VALUES (CURDATE(), '$cliente_id', '$correo_escaped', '$receptor_esc', '$telefono_esc', '$direccion_esc', '$ciudad_esc', '$estado_esc', '$cp_esc', '$tipo_tarjeta_esc', '$tipo_cuenta_esc', '$titular_esc', '$num_tarjeta_esc', '$expiracion_esc', '$cvv_esc', '$monto_total')";
                $pago_registrado = mysqli_query($conexion, $sql_pago);
            }

            // Registrar los perfumes del pedido actual en la tabla compras de MySQL
            $todo_bien = ($pago_registrado !== false);

            foreach ($_SESSION['carrito'] as $producto) {
                $perfume  = mysqli_real_escape_string($conexion, $producto['perfume']);
                $marca    = mysqli_real_escape_string($conexion, $producto['marca']);
                $precio   = (float)$producto['precio'];
                $cantidad = (int)$producto['cantidad'];
                $subtotal = $precio * $cantidad;

                $sql_compra = "INSERT INTO compras (fecha, cliente, perfume, marca, precio, cantidad, subtotal)
                               VALUES (CURDATE(), '$cliente_id', '$perfume', '$marca', '$precio', '$cantidad', '$subtotal')";

                if (!mysqli_query($conexion, $sql_compra)) {
                    $todo_bien = false;
                }
            }

            if ($todo_bien) {
                unset($_SESSION['carrito']);
                $compra_exitosa = true;
                $ultimos_4 = substr($numero_tarjeta, -4);
                $mensaje_exito = "¡Pago aprobado con éxito! Tu orden ha sido procesada correctamente en la base de datos para entregarse a " . htmlspecialchars($receptor) . " en " . htmlspecialchars($direccion) . ", " . htmlspecialchars($ciudad) . " (Tarjeta " . htmlspecialchars($tipo_tarjeta) . " " . htmlspecialchars($tipo_cuenta) . " terminada en " . htmlspecialchars($ultimos_4) . ").";
            } else {
                $mensaje_error = "Ocurrió un error al registrar la compra en la base de datos: " . mysqli_error($conexion);
            }
        }
    }
}

// Si no hay productos en el carrito y no viene de finalizar compra, mandar al carrito
if (empty($_SESSION['carrito']) && !$compra_exitosa) {
    header("Location: carrito.php");
    exit();
}

// Calcular cantidades y gran total para el resumen
$total_carrito = 0;
$gran_total = 0.0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $producto) {
        $cant = isset($producto['cantidad']) ? (int)$producto['cantidad'] : 1;
        $prec = isset($producto['precio']) ? (float)$producto['precio'] : 0.0;
        $total_carrito += $cant;
        $gran_total += ($prec * $cant);
    }
}

// 4. Determinar los valores con los que se rellenará el formulario
if ($ya_ha_comprado && $datos_pago_previos) {
    // Si ya compró antes, autocompletar con sus datos de la tabla pagos
    $val_receptor     = $datos_pago_previos['receptor'];
    $val_telefono     = $datos_pago_previos['telefono'];
    $val_direccion    = $datos_pago_previos['direccion'];
    $val_ciudad       = $datos_pago_previos['ciudad'];
    $val_estado       = $datos_pago_previos['estado'];
    $val_cp           = $datos_pago_previos['codigo_postal'];
    $val_tipo_tarjeta = $datos_pago_previos['tipo_tarjeta'];
    $val_tipo_cuenta  = $datos_pago_previos['tipo_cuenta'];
    $val_titular      = $datos_pago_previos['titular'];
    $val_num_tarjeta  = $datos_pago_previos['numero_tarjeta'];
    $val_expiracion   = $datos_pago_previos['expiracion'];
    $val_cvv          = $datos_pago_previos['cvv'];
} else {
    // Si es un usuario nuevo (que no tiene ninguna compra), NO se rellenan los campos
    $val_receptor     = '';
    $val_telefono     = '';
    $val_direccion    = '';
    $val_ciudad       = '';
    $val_estado       = '';
    $val_cp           = '';
    $val_tipo_tarjeta = '';
    $val_tipo_cuenta  = '';
    $val_titular      = '';
    $val_num_tarjeta  = '';
    $val_expiracion   = '';
    $val_cvv          = '';
}

// Si hubo error al enviar, conservar lo que el usuario escribió
if (!empty($mensaje_error) && isset($_POST['accion'])) {
    $val_receptor     = isset($_POST['receptor']) ? $_POST['receptor'] : $val_receptor;
    $val_telefono     = isset($_POST['telefono']) ? $_POST['telefono'] : $val_telefono;
    $val_direccion    = isset($_POST['direccion']) ? $_POST['direccion'] : $val_direccion;
    $val_ciudad       = isset($_POST['ciudad']) ? $_POST['ciudad'] : $val_ciudad;
    $val_estado       = isset($_POST['estado']) ? $_POST['estado'] : $val_estado;
    $val_cp           = isset($_POST['codigo_postal']) ? $_POST['codigo_postal'] : $val_cp;
    $val_tipo_tarjeta = isset($_POST['tipo_tarjeta']) ? $_POST['tipo_tarjeta'] : $val_tipo_tarjeta;
    $val_tipo_cuenta  = isset($_POST['tipo_cuenta']) ? $_POST['tipo_cuenta'] : $val_tipo_cuenta;
    $val_titular      = isset($_POST['titular_tarjeta']) ? $_POST['titular_tarjeta'] : $val_titular;
    $val_num_tarjeta  = isset($_POST['numero_tarjeta']) ? $_POST['numero_tarjeta'] : $val_num_tarjeta;
    $val_expiracion   = isset($_POST['expiracion']) ? $_POST['expiracion'] : $val_expiracion;
    $val_cvv          = isset($_POST['cvv']) ? $_POST['cvv'] : $val_cvv;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfumeria MM - Formulario de Compra</title>
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
        <a href="registro_inicio.php">Cuenta</a>
        <a href="carrito.php" class="btn-carrito" title="Ver Carrito de Compras">
            <img src="carrito.png" alt="Carrito">
            Carrito<?php if ($total_carrito > 0) { echo " ($total_carrito)"; } ?>
        </a>
    </nav>

    <!-- Barra de Bienvenida y Botón de Cerrar Sesión -->
    <?php if (isset($_SESSION['usuario'])): ?>
        <div class="barra-bienvenida">
            <span class="bienvenida-texto">¡Bienvenid@, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!</span>
            <a href="carrito.php?cerrar_sesion=1" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    <?php endif; ?>

    <!-- Mensajes de Éxito o Error -->
    <?php if (!empty($mensaje_exito)): ?>
        <div class="mensaje-exito">
            <p><?php echo htmlspecialchars($mensaje_exito); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensaje_error)): ?>
        <div class="mensaje-error">
            <p><?php echo htmlspecialchars($mensaje_error); ?></p>
        </div>
    <?php endif; ?>

    <section>
        <h2>Finalizar Pedido</h2>

        <?php if ($compra_exitosa): ?>
            <!-- Vista de Confirmación Exitosa -->
            <div class="caja-intro" style="text-align: center; max-width: 650px; margin: 30px auto;">
                <img src="carrito.png" alt="Éxito" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px;">
                <h3 style="font-family: var(--fuente-titulos); color: var(--color-rojo-oscuro); margin-bottom: 15px;">¡Gracias por tu compra!</h3>
                <p style="font-size: 16px; margin-bottom: 25px; color: var(--color-texto-secundario);">
                    Tu orden ha sido registrada correctamente en nuestra base de datos. Recibirás tu pedido en la dirección proporcionada.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="index.php" class="btn-cerrar-sesion">Volver al Inicio</a>
                    <a href="mujeres.php" class="btn-finalizar">Seguir Explorando</a>
                </div>
            </div>

        <?php else: ?>

            <!-- 1. Tabla de Resumen de Productos del Carrito -->
            <h3 style="font-family: var(--fuente-titulos); color: var(--color-rojo-oscuro); font-size: 22px; margin-bottom: 15px;">
                Resumen de tu Carrito de Compras
            </h3>

            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Información del Perfume</th>
                        <th>Marca</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['carrito'] as $item): ?>
                        <?php 
                            $subtotal_item = $item['precio'] * $item['cantidad']; 
                            $img_item = (!empty($item['imagen']) && strpos($item['imagen'], 'img/') === 0) 
                                ? $item['imagen'] 
                                : ((!empty($item['imagen'])) ? 'img/' . $item['imagen'] : 'carrito.png');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($img_item); ?>" alt="<?php echo htmlspecialchars($item['perfume']); ?>" width="75" height="75">
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['perfume']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['marca']); ?></td>
                            <td>$<?php echo number_format($item['precio'], 2); ?> MXN</td>
                            <td style="text-align: center; font-weight: 600; font-size: 16px;">
                                <?php echo $item['cantidad']; ?>
                            </td>
                            <td><strong>$<?php echo number_format($subtotal_item, 2); ?> MXN</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="carrito-resumen" style="margin-bottom: 30px;">
                <div class="total-destacado">
                    Total a Pagar: <span>$<?php echo number_format($gran_total, 2); ?> MXN</span>
                </div>
                <div class="acciones-carrito">
                    <a href="carrito.php" class="btn-vaciar">Modificar Carrito</a>
                </div>
            </div>

            <!-- 2. Formulario de Datos de Entrega y Pago con Tarjeta -->
            <div class="contenedor-formulario-pago">
                <h3 style="font-family: var(--fuente-titulos); color: var(--color-rojo-oscuro); font-size: 24px; text-align: center; margin-bottom: 15px;">
                    Formulario de Envío y Pago
                </h3>

                <!-- Mensaje Informativo si ya ha comprado anteriormente -->
                <?php if ($ya_ha_comprado): ?>
                    <div style="background-color: #f0fdf4; border: 1.5px solid #86efac; color: #166534; padding: 12px 18px; border-radius: var(--radio-sm); margin-bottom: 22px; font-size: 14px; text-align: center;">
                        ✨ <strong>Cliente Frecuente:</strong> Se han rellenado tus datos de tu compra anterior automáticamente. Si deseas cambiar alguno, puedes editarlo libremente y se actualizará en tu cuenta.
                    </div>
                <?php endif; ?>

                <form action="formulariocarrito.php" method="POST" id="form-pago">
                    <input type="hidden" name="accion" value="finalizar_compra">

                    <!-- Correo que identifica al usuario: SOLO LECTURA (No modificable) -->
                    <div style="background-color: var(--color-rojo-suave); padding: 16px 20px; border-radius: var(--radio-sm); border: 1.5px solid var(--color-beige-medio); margin-bottom: 25px;">
                        <label for="correo" style="font-weight: 600; color: var(--color-rojo-oscuro); font-size: 15px; margin-bottom: 6px; display: block;">
                            Correo Electrónico de la Cuenta (Identificador Único):
                        </label>
                        <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo_usuario_actual); ?>" readonly required style="background-color: #f3ece4; color: var(--color-texto-principal); cursor: not-allowed; font-weight: 600; border-color: var(--color-beige-medio);">
                        <small style="color: var(--color-texto-secundario); font-size: 13px; margin-top: 5px; display: block;">
                            🔒 Este correo identifica tu cuenta de usuario en la base de datos y no se puede modificar.
                        </small>
                    </div>

                    <div class="grid-pago">
                        <!-- Bloque 1: Datos de Entrega y Receptor -->
                        <div class="columna-pago">
                            <h4><span class="paso-num">1</span> Datos del Receptor y Envío</h4>

                            <label for="receptor">Nombre Completo del Receptor: *</label>
                            <input type="text" id="receptor" name="receptor" value="<?php echo htmlspecialchars($val_receptor); ?>" placeholder="Nombre y Apellidos de quien recibe" required>

                            <label for="telefono">Teléfono de Contacto: *</label>
                            <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($val_telefono); ?>" maxlength="12" pattern="[0-9]{10,12}" placeholder="Ej. 6561234567 (10 dígitos)" required>

                            <label for="direccion">Dirección de Entrega: *</label>
                            <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($val_direccion); ?>" placeholder="Calle, número y colonia" required>

                            <div class="fila-doble">
                                <div>
                                    <label for="ciudad">Ciudad: *</label>
                                    <input type="text" id="ciudad" name="ciudad" value="<?php echo htmlspecialchars($val_ciudad); ?>" placeholder="Ej. Ciudad Juárez" required>
                                </div>
                                <div>
                                    <label for="estado">Estado: *</label>
                                    <input type="text" id="estado" name="estado" value="<?php echo htmlspecialchars($val_estado); ?>" placeholder="Ej. Chihuahua" required>
                                </div>
                            </div>

                            <label for="codigo_postal">Código Postal (C.P.): *</label>
                            <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($val_cp); ?>" maxlength="5" pattern="[0-9]{5}" placeholder="Ej. 32000" required>
                        </div>

                        <!-- Bloque 2: Información de Pago y Tarjeta -->
                        <div class="columna-pago">
                            <h4><span class="paso-num">2</span> Datos de la Tarjeta</h4>

                            <div class="fila-doble">
                                <div>
                                    <label for="tipo_tarjeta">Franquicia: *</label>
                                    <select id="tipo_tarjeta" name="tipo_tarjeta" required>
                                        <option value="">Selecciona...</option>
                                        <option value="Visa" <?php if ($val_tipo_tarjeta === 'Visa') echo 'selected'; ?>>Visa</option>
                                        <option value="Mastercard" <?php if ($val_tipo_tarjeta === 'Mastercard') echo 'selected'; ?>>Mastercard</option>
                                        <option value="American Express" <?php if ($val_tipo_tarjeta === 'American Express') echo 'selected'; ?>>American Express</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="tipo_cuenta">Tipo de Tarjeta: *</label>
                                    <select id="tipo_cuenta" name="tipo_cuenta" required>
                                        <option value="">Selecciona...</option>
                                        <option value="Débito" <?php if ($val_tipo_cuenta === 'Débito') echo 'selected'; ?>>Débito</option>
                                        <option value="Crédito" <?php if ($val_tipo_cuenta === 'Crédito') echo 'selected'; ?>>Crédito</option>
                                    </select>
                                </div>
                            </div>

                            <label for="titular_tarjeta">Nombre del Titular: *</label>
                            <input type="text" id="titular_tarjeta" name="titular_tarjeta" value="<?php echo htmlspecialchars($val_titular); ?>" placeholder="Como aparece en la tarjeta" required>

                            <label for="numero_tarjeta">Número de Tarjeta: *</label>
                            <input type="text" id="numero_tarjeta" name="numero_tarjeta" value="<?php echo htmlspecialchars($val_num_tarjeta); ?>" maxlength="16" pattern="[0-9]{16}" placeholder="16 dígitos continuos (sin guiones)" required>

                            <div class="fila-doble">
                                <div>
                                    <label for="expiracion">Vencimiento (MM/AA): *</label>
                                    <input type="text" id="expiracion" name="expiracion" value="<?php echo htmlspecialchars($val_expiracion); ?>" maxlength="5" pattern="(0[1-9]|1[0-2])\/[0-9]{2}" placeholder="MM/AA (Ej. 08/28)" required>
                                </div>
                                <div>
                                    <label for="cvv">CVV / CVC: *</label>
                                    <input type="password" id="cvv" name="cvv" value="<?php echo htmlspecialchars($val_cvv); ?>" maxlength="4" pattern="[0-9]{3,4}" placeholder="3 o 4 dígitos" required>
                                </div>
                            </div>

                            <div style="margin-top: 25px; padding: 14px; background: #fffcf8; border: 1px dashed var(--color-beige-dorado); border-radius: var(--radio-sm);">
                                <p style="margin: 0; font-size: 13px; color: var(--color-texto-secundario);">
                                    🔒 <strong>Datos Seguros:</strong> Si ya habías comprado antes, tus datos están cargados. Al cambiar alguno, se actualizará tu registro automáticamente.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 35px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                        <a href="carrito.php" class="btn-vaciar" style="line-height: 26px;">Regresar al Carrito</a>
                        <button type="submit" class="btn-finalizar" style="font-size: 16px; padding: 12px 35px;">
                            Pagar $<?php echo number_format($gran_total, 2); ?> MXN y Finalizar Compra
                        </button>
                    </div>
                </form>
            </div>

        <?php endif; ?>
    </section>

    <footer>
        Martinez Monarrez Alondra Sofia <br>5°J<br>24308051280867
    </footer>
</body>

</html>
