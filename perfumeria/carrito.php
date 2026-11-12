<?php
session_start();
include("conexion.php");

$mensaje_exito = "";
$mensaje_error = "";

// Cerrar sesión
if (isset($_GET['cerrar_sesion'])) {
    session_unset();
    session_destroy();
    header("Location: carrito.php");
    exit();
}

// Acción: Agregar producto al carrito
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    // REGLA CLAVE: Validar si la sesión está iniciada
    if (!isset($_SESSION['usuario'])) {
        header("Location: registro_inicio.php?aviso=iniciar_sesion");
        exit();
    }

    if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    $perfume = isset($_POST['perfume']) ? trim($_POST['perfume']) : '';
    $marca   = isset($_POST['marca']) ? trim($_POST['marca']) : '';
    $precio  = isset($_POST['precio']) ? (float)$_POST['precio'] : 0.0;
    $imagen  = isset($_POST['imagen']) ? trim($_POST['imagen']) : '';

    // Llave única por marca y producto para evitar duplicar filas
    $clave = md5($marca . '_' . $perfume);

    if (isset($_SESSION['carrito'][$clave])) {
        $_SESSION['carrito'][$clave]['cantidad'] += 1;
    } else {
        $_SESSION['carrito'][$clave] = [
            'perfume'  => $perfume,
            'marca'    => $marca,
            'precio'   => $precio,
            'cantidad' => 1,
            'imagen'   => $imagen
        ];
    }

        // Redirigir a la página de origen para que el usuario se quede donde mismo
        $origen = !empty($_POST['origen']) ? $_POST['origen'] : 'carrito.php';
        $permitidos = ['mujeres.php', 'hombres.php', 'bebes.php', 'index.php', 'carrito.php'];
        if (!in_array($origen, $permitidos)) {
            $origen = 'carrito.php';
        }

        header("Location: " . $origen);
        exit();
}

// Acción: Modificar cantidad directamente desde el input
if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_cantidad') {
    $clave = isset($_POST['clave']) ? $_POST['clave'] : '';
    $nueva_cant = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

    if (isset($_SESSION['carrito'][$clave])) {
        if ($nueva_cant > 0) {
            $_SESSION['carrito'][$clave]['cantidad'] = $nueva_cant;
        } else {
            unset($_SESSION['carrito'][$clave]);
        }
    }
    header("Location: carrito.php");
    exit();
}

// Acción: Sumar (+) o Restar (-) cantidad con botones rápidos
if (isset($_GET['cambiar_cant']) && isset($_GET['clave'])) {
    $clave = $_GET['clave'];
    $tipo = $_GET['cambiar_cant'];

    if (isset($_SESSION['carrito'][$clave])) {
        if ($tipo === 'mas') {
            $_SESSION['carrito'][$clave]['cantidad'] += 1;
        } elseif ($tipo === 'menos') {
            if ($_SESSION['carrito'][$clave]['cantidad'] > 1) {
                $_SESSION['carrito'][$clave]['cantidad'] -= 1;
            } else {
                unset($_SESSION['carrito'][$clave]);
            }
        }
    }
    header("Location: carrito.php");
    exit();
}

// Acción: Eliminar un producto del carrito
if (isset($_GET['eliminar'])) {
    $clave = $_GET['eliminar'];
    if (isset($_SESSION['carrito'][$clave])) {
        unset($_SESSION['carrito'][$clave]);
    }
    header("Location: carrito.php");
    exit();
}

// Acción: Vaciar carrito por completo
if (isset($_GET['vaciar'])) {
    unset($_SESSION['carrito']);
    header("Location: carrito.php");
    exit();
}

// Acción: Finalizar Compra (Guardar en la tabla compras de MySQL)
if (isset($_POST['accion']) && $_POST['accion'] === 'finalizar_compra') {
    // Validar sesión
    if (!isset($_SESSION['usuario'])) {
        header("Location: registro_inicio.php?aviso=iniciar_sesion");
        exit();
    }

    if (empty($_SESSION['carrito'])) {
        $mensaje_error = "Tu carrito está vacío. Agrega perfumes antes de finalizar tu compra.";
    } else {
        // Validar todos los campos obligatorios de entrega y tarjeta
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
            $mensaje_error = "Por favor completa todos los campos obligatorios del formulario de envío y pago.";
        } else {
            // Obtener el ID del cliente registrado
            $cliente_id = 0;
            if (isset($_SESSION['id_usuario']) && (int)$_SESSION['id_usuario'] > 0) {
                $cliente_id = (int)$_SESSION['id_usuario'];
            } else {
                // Si por alguna razón no estaba en sesión, buscar por su nombre
                $nombre_escaped = mysqli_real_escape_string($conexion, $_SESSION['usuario']);
                $res_u = mysqli_query($conexion, "SELECT id FROM usuarios WHERE nombre = '$nombre_escaped' LIMIT 1");
                if ($res_u && $row_u = mysqli_fetch_assoc($res_u)) {
                    $cliente_id = (int)$row_u['id'];
                    $_SESSION['id_usuario'] = $cliente_id;
                }
            }

            if ($cliente_id > 0) {
                $todo_bien = true;

                foreach ($_SESSION['carrito'] as $producto) {
                    $perfume  = mysqli_real_escape_string($conexion, $producto['perfume']);
                    $marca    = mysqli_real_escape_string($conexion, $producto['marca']);
                    $precio   = (float)$producto['precio'];
                    $cantidad = (int)$producto['cantidad'];
                    $subtotal = $precio * $cantidad;

                    // Inserción en la tabla compras guardando la fecha del día automáticamente con CURDATE()
                    $sql = "INSERT INTO compras (fecha, cliente, perfume, marca, precio, cantidad, subtotal)
                            VALUES (CURDATE(), '$cliente_id', '$perfume', '$marca', '$precio', '$cantidad', '$subtotal')";

                    if (!mysqli_query($conexion, $sql)) {
                        $todo_bien = false;
                    }
                }

                if ($todo_bien) {
                    unset($_SESSION['carrito']);
                    $ultimos_4 = substr($numero_tarjeta, -4);
                    $mensaje_exito = "¡Pago aprobado con éxito! Tu pedido ha sido guardado en la base de datos para entregarse a " . htmlspecialchars($receptor) . " en " . htmlspecialchars($direccion) . ", " . htmlspecialchars($ciudad) . " (Pago con " . htmlspecialchars($tipo_tarjeta) . " " . htmlspecialchars($tipo_cuenta) . " terminada en " . htmlspecialchars($ultimos_4) . ").";
                } else {
                    $mensaje_error = "Ocurrió un error al registrar la compra: " . mysqli_error($conexion);
                }
            } else {
                $mensaje_error = "No se pudo identificar tu ID de usuario. Por favor cierra sesión y vuelve a entrar.";
            }
        }
    }
}

// Calcular cantidades y gran total
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfumeria MM - Carrito de Compras</title>
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
        <a href="carrito.php" class="btn-carrito activo" title="Ver Carrito de Compras">
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

    <!-- Sección del Carrito de Compras -->
    <section>
        <h2>Mi Carrito de Compras</h2>

        <?php if (!empty($_SESSION['carrito'])): ?>
            <!-- Tabla de Productos en el Carrito -->
            <table>
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Información del Perfume</th>
                        <th>Marca</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['carrito'] as $clave => $item): ?>
                        <?php 
                            $subtotal_item = $item['precio'] * $item['cantidad']; 
                            $img_item = (!empty($item['imagen']) && strpos($item['imagen'], 'img/') === 0) 
                                ? $item['imagen'] 
                                : ((!empty($item['imagen'])) ? 'img/' . $item['imagen'] : 'carrito.png');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($img_item); ?>" alt="<?php echo htmlspecialchars($item['perfume']); ?>" width="80" height="80">
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['perfume']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['marca']); ?></td>
                            <td>$<?php echo number_format($item['precio'], 2); ?> MXN</td>
                            <td style="text-align: center;">
                                <form action="carrito.php" method="POST" class="form-cantidad">
                                    <input type="hidden" name="accion" value="actualizar_cantidad">
                                    <input type="hidden" name="clave" value="<?php echo htmlspecialchars($clave); ?>">
                                    <a href="carrito.php?cambiar_cant=menos&clave=<?php echo urlencode($clave); ?>" class="btn-cant" title="Disminuir uno">-</a>
                                    <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" max="99" class="input-cantidad" onchange="this.form.submit()" title="Escribe la cantidad y presiona Enter">
                                    <a href="carrito.php?cambiar_cant=mas&clave=<?php echo urlencode($clave); ?>" class="btn-cant" title="Aumentar uno">+</a>
                                </form>
                            </td>
                            <td><strong>$<?php echo number_format($subtotal_item, 2); ?> MXN</strong></td>
                            <td>
                                <a href="carrito.php?eliminar=<?php echo urlencode($clave); ?>" class="btn-eliminar" title="Eliminar este producto">Quitar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Resumen y Botones de Acción -->
            <div class="carrito-resumen">
                <div class="total-destacado">
                    Total a Pagar: <span>$<?php echo number_format($gran_total, 2); ?> MXN</span>
                </div>
                <div class="acciones-carrito">
                    <a href="mujeres.php" class="btn-vaciar">Seguir Comprando</a>
                    <a href="carrito.php?vaciar=1" class="btn-vaciar" onclick="return confirm('¿Seguro que deseas vaciar todo el carrito?');">Vaciar Carrito</a>
                    <a href="formulariocarrito.php" class="btn-finalizar">Confirmar compra</a>
                </div>
            </div>

        <?php else: ?>
            <!-- Estado cuando el Carrito está Vacío -->
            <div class="caja-intro" style="text-align: center; max-width: 650px; margin: 30px auto;">
                <img src="carrito.png" alt="Carrito" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px;">
                <h3 style="font-family: var(--fuente-titulos); color: var(--color-rojo-oscuro); margin-bottom: 10px;">Tu carrito está vacío</h3>
                <p style="font-size: 16px; margin-bottom: 25px; color: var(--color-texto-secundario);">
                    Aún no has agregado perfumes a tu carrito. Explora nuestras colecciones y añade tus favoritos.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="mujeres.php" class="btn-cerrar-sesion">Colección Mujeres</a>
                    <a href="hombres.php" class="btn-cerrar-sesion">Colección Hombres</a>
                    <a href="bebes.php" class="btn-cerrar-sesion">Colección Niños</a>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <footer>
        Martinez Monarrez Alondra Sofia <br>5°J<br>24308051280867
    </footer>
</body>

</html>
