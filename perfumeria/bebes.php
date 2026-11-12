<?php
session_start();
include("conexion.php");

// Consultar los productos de la tabla bebes
$consulta = "SELECT * FROM bebes";
$resultado = mysqli_query($conexion, $consulta);

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
    <title>Perfumeria MM - Niños y Bebés</title>
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
        <a href="bebes.php" class="activo">Niños</a>
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
            <a href="index.php?cerrar_sesion=1" class="btn-cerrar-sesion">Cerrar Sesión</a>
        </div>
    <?php endif; ?>

    <!-- Catálogo Niños Dinámico -->
    <section>
        <h2>Perfumes para Niñ@s / Bebés</h2>
        <table>
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Información del Producto</th>
                    <th>Marca</th>
                    <th>Precio</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                    <?php while ($fila = mysqli_fetch_array($resultado)): ?>
                        <?php 
                            $ruta_imagen = (!empty($fila['imagen']) && strpos($fila['imagen'], 'img/') === 0) 
                                ? $fila['imagen'] 
                                : 'img/' . $fila['imagen']; 
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($ruta_imagen); ?>" alt="<?php echo htmlspecialchars($fila['info']); ?>" width="100" height="100">
                            </td>
                            <td><strong><?php echo htmlspecialchars($fila['marca']); ?></strong> (<em><?php echo htmlspecialchars($fila['info']); ?></em>)</td>
                            <td><?php echo htmlspecialchars($fila['marca']); ?></td>
                            <td>$<?php echo number_format($fila['precio'], 2); ?> MXN</td>
                            <td>
                                <form action="carrito.php" method="POST">
                                    <input type="hidden" name="accion" value="agregar">
                                    <input type="hidden" name="origen" value="bebes.php">
                                    <input type="hidden" name="perfume" value="<?php echo htmlspecialchars($fila['info']); ?>">
                                    <input type="hidden" name="marca" value="<?php echo htmlspecialchars($fila['marca']); ?>">
                                    <input type="hidden" name="precio" value="<?php echo $fila['precio']; ?>">
                                    <input type="hidden" name="imagen" value="<?php echo htmlspecialchars($ruta_imagen); ?>">
                                    <button type="submit">Comprar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 25px; color: var(--color-texto-secundario);">
                            No hay productos disponibles en este momento. Inserta productos en la tabla <code>bebes</code> de tu base de datos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <footer>
        Martinez Monarrez Alondra Sofia <br>5°J<br>24308051280867
    </footer>
</body>

</html>