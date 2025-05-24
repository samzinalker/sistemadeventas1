-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-05-2025 a las 03:36:52
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
-- Base de datos: `sistemadeventas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_clientes`
--

CREATE TABLE `tb_clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre_cliente` varchar(255) NOT NULL,
  `nit_ci_cliente` varchar(255) NOT NULL,
  `tipo_documento` enum('cedula','ruc','pasaporte','extranjero') DEFAULT 'cedula',
  `celular_cliente` varchar(50) NOT NULL,
  `telefono_fijo` varchar(20) DEFAULT NULL,
  `email_cliente` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `direccion` varchar(500) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_clientes`
--

INSERT INTO `tb_clientes` (`id_cliente`, `nombre_cliente`, `nit_ci_cliente`, `tipo_documento`, `celular_cliente`, `telefono_fijo`, `email_cliente`, `id_usuario`, `direccion`, `ciudad`, `provincia`, `fecha_nacimiento`, `observaciones`, `estado`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'marcelo', '2142144', 'cedula', '42424124224', NULL, 'marcelo@gmail.com', 0, NULL, NULL, NULL, NULL, NULL, 'activo', '2025-04-25 22:27:37', '2025-04-25 22:27:37');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
