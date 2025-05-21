-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-05-2025 a las 05:14:10
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
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL COMMENT 'Usuario que registra la compra',
  `nro_comprobante_proveedor` varchar(100) DEFAULT NULL COMMENT 'Nro de factura/guía del proveedor',
  `fecha_compra` date NOT NULL,
  `aplica_iva` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = No, 1 = Sí',
  `porcentaje_iva` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Ej: 12.00 para 12%',
  `subtotal_neto` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Suma de subtotales de detalle (base imponible)',
  `monto_iva` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Monto del IVA calculado sobre subtotal_neto',
  `monto_total` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'subtotal_neto + monto_iva',
  `estado` varchar(20) NOT NULL DEFAULT 'REGISTRADA' COMMENT 'REGISTRADA, ANULADA',
  `observaciones` text DEFAULT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `clave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`clave`, `valor`, `fyh_actualizacion`) VALUES
('iva_aplica_compras_defecto', '1', '2025-05-20 20:51:23'),
('iva_porcentaje_compras_defecto', '15.00', '2025-05-20 20:51:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compras`
--

CREATE TABLE `detalle_compras` (
  `id_detalle_compra` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL COMMENT 'Cantidad comprada (puede ser decimal si el producto lo permite)',
  `precio_compra_unitario` decimal(10,2) NOT NULL COMMENT 'Precio de compra unitario (sin IVA si el IVA es general)',
  `subtotal` decimal(10,2) NOT NULL COMMENT 'cantidad * precio_compra_unitario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_almacen`
--

CREATE TABLE `tb_almacen` (
  `id_producto` int(11) NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `stock_minimo` int(11) DEFAULT NULL,
  `stock_maximo` int(11) DEFAULT NULL,
  `precio_compra` decimal(10,2) DEFAULT NULL,
  `precio_venta` decimal(10,2) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `imagen` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_almacen`
--

INSERT INTO `tb_almacen` (`id_producto`, `codigo`, `nombre`, `descripcion`, `stock`, `stock_minimo`, `stock_maximo`, `precio_compra`, `precio_venta`, `fecha_ingreso`, `imagen`, `id_usuario`, `id_categoria`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'P-00001', 'pepsi', '1 litrosss', 121, 11, 120, 0.55, 1.00, '2025-04-19', '2025-04-22-02-46-39__pepsi.png', 1, 12, '2025-04-22 14:46:39', '2025-05-19 20:56:32'),
(5, 'P-00002', 'logo', 'tienditasf', 82, 5, 30, 0.75, 1.90, '2025-04-23', '2025-04-23-05-32-58__logo1.jpg', 1, 13, '2025-04-23 17:32:58', '2025-05-19 21:17:04'),
(17, 'P-00003', 'sfaf', 'fsafsfas', 1, 1, 0, 11.00, 12.00, '2025-05-15', 'default_product.png', 1, 19, '2025-05-20 21:09:57', '2025-05-20 21:09:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_carrito`
--

CREATE TABLE `tb_carrito` (
  `id_carrito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nro_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_categorias`
--

CREATE TABLE `tb_categorias` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_categorias`
--

INSERT INTO `tb_categorias` (`id_categoria`, `nombre_categoria`, `id_usuario`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(12, 'LIQUIDO', 1, '2025-04-22 14:09:40', '0000-00-00 00:00:00'),
(13, 'ELECTRODOMESTICOS', 1, '2025-04-22 14:09:54', '0000-00-00 00:00:00'),
(19, 'dd', 1, '2025-05-17 22:45:42', '2025-05-17 22:45:42'),
(20, 'f', 1, '2025-05-19 20:13:42', '2025-05-19 20:13:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_clientes`
--

CREATE TABLE `tb_clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre_cliente` varchar(255) NOT NULL,
  `nit_ci_cliente` varchar(255) NOT NULL,
  `celular_cliente` varchar(50) NOT NULL,
  `email_cliente` varchar(255) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_clientes`
--

INSERT INTO `tb_clientes` (`id_cliente`, `nombre_cliente`, `nit_ci_cliente`, `celular_cliente`, `email_cliente`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'marcelo', '2142144', '42424124224', 'marcelo@gmail.com', '2025-04-25 22:27:37', '2025-04-25 22:27:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_compras`
--

CREATE TABLE `tb_compras` (
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `nro_compra` int(11) NOT NULL,
  `fecha_compra` date NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `precio_compra` varchar(50) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_proveedores`
--

CREATE TABLE `tb_proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(255) NOT NULL,
  `celular` varchar(50) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `empresa` varchar(255) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_proveedores`
--

INSERT INTO `tb_proveedores` (`id_proveedor`, `nombre_proveedor`, `celular`, `telefono`, `empresa`, `email`, `direccion`, `id_usuario`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(12, 'Jonathan Ordoñez', '0954924922', '1', 'EMPRESA Xx', 'ordoñeez@gmail.com', 'Via quito Kilometro 3/2', 1, '2025-04-22 14:12:18', '2025-05-19 22:26:19'),
(14, 'JUAN1', '12345678910', '1212555', 'xxx111', '1@gmail.com', 'calle xd', 1, '2025-05-11 12:52:03', '2025-05-19 22:21:09'),
(18, 'fffffffffffffffff', '222222222', '2222222222', '2222222222222', '222222222', '222222222222', 13, '2025-05-11 13:11:43', '0000-00-00 00:00:00'),
(21, 'asfa', '1231', '1221', 'fsfa', '1ñ@gmail.com', 'fwafsaffssfa', 1, '2025-05-19 22:26:49', '2025-05-19 22:26:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_roles`
--

CREATE TABLE `tb_roles` (
  `id_rol` int(11) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_roles`
--

INSERT INTO `tb_roles` (`id_rol`, `rol`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'administrador', '2025-04-10 20:05:00', '2025-04-22 21:05:00'),
(7, 'vendedor', '2025-05-04 06:18:50', '0000-00-00 00:00:00'),
(9, 'xdd', '2025-05-04 09:38:01', '2025-05-04 09:39:15'),
(10, 'dd', '2025-05-18 18:06:06', '2025-05-18 18:06:06'),
(12, 'dddd', '2025-05-18 18:17:35', '2025-05-18 22:37:52'),
(16, 'fa', '2025-05-18 18:48:24', '2025-05-18 18:48:24'),
(20, 'F', '2025-05-18 22:01:41', '2025-05-18 22:01:41'),
(21, 'fasfa', '2025-05-18 22:08:06', '2025-05-18 22:08:06'),
(23, 'ffax', '2025-05-18 22:11:08', '2025-05-18 22:11:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_usuarios`
--

CREATE TABLE `tb_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nombres` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `imagen_perfil` varchar(255) DEFAULT 'user_default.png',
  `password_user` text NOT NULL,
  `token` varchar(100) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Volcado de datos para la tabla `tb_usuarios`
--

INSERT INTO `tb_usuarios` (`id_usuario`, `nombres`, `email`, `imagen_perfil`, `password_user`, `token`, `id_rol`, `fyh_creacion`, `fyh_actualizacion`) VALUES
(1, 'marcelo mamani', 'marcelo@gmail.com', '2025-05-11-14-59-30_682101a228f7c.PNG', '$2y$10$75JF2CgxfIl0D2FvA2n7Ce0lJogqlHHmV9I38Z2SHytN7iElNtSxm', '', 1, '2025-04-14 21:07:42', '2025-05-20 18:30:14'),
(10, 'administrador', 'admin@gmail.com', 'user_default.png', '$2y$10$LOP8dOv1tmWBnuZOrxmnw.TK6358ZDbFSgo6FwjuOtm.JVYxd8YGG', '', 1, '2025-05-03 06:55:54', '0000-00-00 00:00:00'),
(12, 'venderw', 'vender@gmail.com', 'user_default.png', '$2y$10$EBfd4aY2yFbbWpkPSAC1XO4PKFeWTK9r9WKa/WB5iRTD5fRGlRNJi', '', 7, '2025-05-04 06:19:20', '2025-05-11 14:41:07'),
(13, 'xd 22', '1@gmail.com', 'user_default.png', '$2y$10$uzWGvuEd0xTk3.jnYza22.FMIzgIxgsPYuGzl7oHngZDxtE6mrr4S', '', 1, '2025-05-08 21:57:46', '2025-05-11 15:04:05'),
(14, '2', '2@gmail.com', 'user_default.png', '$2y$10$8NahENpQCkCI565YSxAffOcfuyB5gfzrtt5UkHyhvx0YfBLrWkXt6', '', 7, '2025-05-10 17:32:16', '2025-05-10 17:32:22'),
(17, 'marcelo mamanis', 'marcelo2312@gmail.com', 'user_default.png', '$2y$10$wTBVK2A1e4kb/hkMMfNwTO2XPCPrxDorR.UhuUDSGmZKTwEnR7k72', '', 7, '2025-05-19 19:10:53', '2025-05-19 19:10:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tb_ventas`
--

CREATE TABLE `tb_ventas` (
  `id_venta` int(11) NOT NULL,
  `nro_venta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `total_pagado` decimal(10,2) NOT NULL,
  `fyh_creacion` datetime NOT NULL,
  `fyh_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `fk_compras_proveedor` (`id_proveedor`),
  ADD KEY `fk_compras_usuario` (`id_usuario`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD PRIMARY KEY (`id_detalle_compra`),
  ADD KEY `fk_detalle_compra_compra` (`id_compra`),
  ADD KEY `fk_detalle_compra_producto` (`id_producto`);

--
-- Indices de la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `tb_carrito`
--
ALTER TABLE `tb_carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_venta` (`nro_venta`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tb_categorias`
--
ALTER TABLE `tb_categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `fk_categoria_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_proveedor` (`id_proveedor`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tb_proveedores`
--
ALTER TABLE `tb_proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `tb_roles`
--
ALTER TABLE `tb_roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- Indices de la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `nro_venta` (`nro_venta`),
  ADD KEY `fk_tb_ventas_tb_usuarios` (`id_usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  MODIFY `id_detalle_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tb_carrito`
--
ALTER TABLE `tb_carrito`
  MODIFY `id_carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de la tabla `tb_categorias`
--
ALTER TABLE `tb_categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `tb_clientes`
--
ALTER TABLE `tb_clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tb_proveedores`
--
ALTER TABLE `tb_proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `tb_roles`
--
ALTER TABLE `tb_roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tb_proveedores` (`id_proveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD CONSTRAINT `fk_detalle_compra_compra` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_compra_producto` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_almacen`
--
ALTER TABLE `tb_almacen`
  ADD CONSTRAINT `tb_almacen_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_almacen_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_carrito`
--
ALTER TABLE `tb_carrito`
  ADD CONSTRAINT `tb_carrito_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tb_categorias`
--
ALTER TABLE `tb_categorias`
  ADD CONSTRAINT `fk_categoria_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`);

--
-- Filtros para la tabla `tb_compras`
--
ALTER TABLE `tb_compras`
  ADD CONSTRAINT `tb_compras_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tb_almacen` (`id_producto`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_3` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_compras_ibfk_4` FOREIGN KEY (`id_proveedor`) REFERENCES `tb_proveedores` (`id_proveedor`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tb_usuarios`
--
ALTER TABLE `tb_usuarios`
  ADD CONSTRAINT `tb_usuarios_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `tb_roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tb_ventas`
--
ALTER TABLE `tb_ventas`
  ADD CONSTRAINT `fk_tb_ventas_tb_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `tb_clientes` (`id_cliente`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `tb_ventas_ibfk_2` FOREIGN KEY (`nro_venta`) REFERENCES `tb_carrito` (`nro_venta`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
