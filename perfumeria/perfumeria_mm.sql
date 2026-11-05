-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-11-2026 a las 17:40:30
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `perfumeria_mm`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bebes`
--

CREATE TABLE `bebes` (
  `id` int(11) NOT NULL,
  `info` varchar(200) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `bebes`
--

INSERT INTO `bebes` (`id`, `info`, `marca`, `precio`, `imagen`) VALUES
(1, 'Zara Kids, 50–100 ml', 'Zara', 499.00, 'ZaraKids.jpg'),
(2, 'Colors Kids, 100 ml', 'Benetton', 600.00, 'ColorsKids.png'),
(3, 'Tous Kids / Baby Tous, 100 ml', 'Tous', 1800.00, 'TousKids.jpg'),
(4, 'Tartine et Chocolat - Ptisenbon, 100 ml', 'Givenchy', 1500.00, 'GivenchTartine.jpg'),
(5, 'Petits et Mamans, 100 ml', 'Bvlgari', 2350.00, 'BvlgariPetits.jpg'),
(6, 'Baby Touch, 100 ml', 'Burberry', 2400.00, 'BurberryBaby.jpg'),
(7, 'Kaloo Lili Eau de Senteur, 100 ml', 'Kaloo', 800.00, 'Kaloo.jpg'),
(8, 'Musti Eau de Soin, 50 ml', 'Mustela', 450.00, 'Mustela.jpg'),
(9, 'Suavinex Baby Cologne, 100 ml', 'Suavinex', 400.00, 'Suavinex.jpg'),
(10, 'Tender Moments Colonia Refrescante Para Bebé, 100 ml', 'Jafra', 500.00, 'Jafra.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hombres`
--

CREATE TABLE `hombres` (
  `id` int(11) NOT NULL,
  `info` varchar(200) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `hombres`
--

INSERT INTO `hombres` (`id`, `info`, `marca`, `precio`, `imagen`) VALUES
(1, 'Vibrant Leather, 100 ml', 'Zara', 650.00, 'ZaraVibrant.jpg'),
(2, 'Colors Man Black, 100 ml', 'Benetton', 670.00, 'BenettonMan.jpg'),
(3, 'Tous Man Intense, 100 ml', 'Tous', 1600.00, 'Tous.jpg'),
(4, 'Burberry Hero, 100 ml', 'Burberry', 3600.00, 'BurberryHero.jpg'),
(5, 'Bvlgari Man in Black, 100 ml', 'Bvlgari', 3800.00, 'BvlgariMan.jpg'),
(6, 'Gentleman Givenchy, 100 ml', 'Givenchy', 3700.00, 'Gentleman.jpg'),
(7, 'Aventus, 100 ml', 'Creed', 10000.00, 'Creed.jpg'),
(8, 'Colonia, 100 ml', 'Acqua di Parma', 4300.00, 'Acqua.jpg'),
(9, 'Le Male, 125 ml', 'Jean Paul Gaultier', 3050.00, 'Jean.jpg'),
(10, '1 Million, 100 ml', 'Paco Rabanne', 3000.00, 'Paco.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mujeres`
--

CREATE TABLE `mujeres` (
  `id` int(11) NOT NULL,
  `info` varchar(200) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mujeres`
--

INSERT INTO `mujeres` (`id`, `info`, `marca`, `precio`, `imagen`) VALUES
(1, 'Red Temptation, 80 ml', 'Zara', 750.00, 'ZaraRed.jpg'),
(2, 'Colors Woman Rose, 80 ml', 'Benetton', 700.00, 'BenettonColors.jpg'),
(3, 'LoveMe / Tous EDT, 90 ml', 'Tous', 2100.00, 'TousLoveMe.jpg'),
(4, 'Omnia Crystalline, 100 ml', 'Bvlgari', 3000.00, 'BvlgariOmnia.jpg'),
(5, 'Burberry Her, 100 ml', 'Burberry', 3100.00, 'Burberry.jpg'),
(6, 'Interdit EDP, 80 ml', 'Givenchy', 3400.00, 'GivenchyL.jpg'),
(7, 'Coco Mademoiselle, 100ml', 'Chanel', 4000.50, 'ChanelCoco.jpg'),
(8, 'J´adore EDP, 100 ml', 'Dior', 3800.00, 'DiorJadore.jpg'),
(9, 'Black Opium, 90 ml', 'Yves Saint Laurent', 3500.00, 'YvesSaint.jpg'),
(10, 'Good Girl, 80 ml', 'Carolina Herrera', 3400.00, 'CarolinaH.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `nombre` varchar(11) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `numero` bigint(12) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `contraseña` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`nombre`, `apellido`, `correo`, `numero`, `direccion`, `contraseña`) VALUES
('ALONDRA', 'MONARREZ', 'a.24308051280867@cbtis128.edu.mx', 6980979889, 'virginia 4', '020709'),
('alondra', 'martinez', 'alondra@gmail.com', 38373, 'bkscsbck', '12345'),
('Alondra', 'Martinez Monarrez', 'sofimtz0207@gmail.com', 0, 'Virginia 82', '020304');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bebes`
--
ALTER TABLE `bebes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `hombres`
--
ALTER TABLE `hombres`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `mujeres`
--
ALTER TABLE `mujeres`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bebes`
--
ALTER TABLE `bebes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `hombres`
--
ALTER TABLE `hombres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `mujeres`
--
ALTER TABLE `mujeres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
