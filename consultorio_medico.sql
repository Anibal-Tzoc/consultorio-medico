-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2025 a las 19:28:13
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
-- Base de datos: `consultorio_medico`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `expedientes`
--

CREATE TABLE `expedientes` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT 1,
  `fecha_consulta` date NOT NULL,
  `notas_consulta` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `receta` text DEFAULT NULL,
  `ingreso` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `adjunto` varchar(255) DEFAULT NULL,
  `tipo_ingreso` enum('consulta','examen','receta','otro') DEFAULT 'consulta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `expedientes`
--

INSERT INTO `expedientes` (`id`, `paciente_id`, `doctor_id`, `fecha_consulta`, `notas_consulta`, `diagnostico`, `receta`, `ingreso`, `created_at`, `adjunto`, `tipo_ingreso`) VALUES
(1, 1, 1, '2025-11-12', 'cmIzSEdwakoxMkVaQWVBZkJzWWhyaXI0K0QrdW11cnBGZlJiZE1RZjQzUT06Og1avSr9eqZ3Dix/Q02xs4s=', 'UWgzUDRNZUF1YjArakNZRWw2cE9EQT09OjqLy9aF8EMLUbW8VF3f0PlL', 'YVhMakJDMGUvSStEN01IbGtSUldxQT09Ojq/ttLbvjq7O0pduPV1zi/l', 25.25, '2025-11-04 20:10:46', NULL, 'consulta'),
(2, 2, 1, '2025-11-26', 'TThtZEVYZHF2bE0zWDNQbk5pSmdDakJXNlNaSWtDaW5sNlZtUFk5RUxTRT06Oht8PHmwt2fBG9E8cO2h6Mg=', 'eGlUdFVJUkRiWk44SEtkRTJ5ai9sdz09Ojo0R3817CbhkfSjlNEaZHKk', 'VUZiVGZYWjk1RDZGZW44SVNWV2d5Zz09OjrZUQ1pxuJNZUvyjHZfgDK1', 15.00, '2025-11-04 22:03:05', NULL, 'consulta'),
(3, 2, 1, '2025-11-18', 'VWNpeEYwTHIvNjhFR1FsOHQxejhHQT09OjrSEnw8sgRjVePKQ6AJDeMt', 'TVErRGtVenZZREhqMlFKaHZ0UGVjZz09OjqQGHD7S9+ORTYC8LtpJ6qA', 'T2I0bG1NS0VmOEhyV3E0ZVpoeXp6UT09Ojpy5lzHIXv9OJ3i8H2hfrQx', 15.00, '2025-11-05 01:26:32', NULL, 'consulta'),
(4, 2, 1, '2025-11-28', 'bTM1Y3pZZjQyS2J4OXZXejhtZDNXczVwTzR0RjVtUzFwcnBUTzdZNW92TT06OrsC/j3aLs8knXkCCRoLJXE=', 'R3hMaGhmbjV6VmVtWk0wY2Y1SHpiZz09OjpdZeINJXrw3Gyeys0nsTq2', 'emJ0T1Bxem5lLzJtYXBWcWY3YUVOdz09Ojq+jmFh0uQ9A7YhismfmzPF', 25.00, '2025-11-05 17:58:43', 'Gemini_Generated_Image_nj2ou1nj2ou1nj2o.png', 'consulta');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `historial_medico` text DEFAULT NULL,
  `alergias` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `nombre`, `apellido`, `email`, `telefono`, `fecha_nacimiento`, `historial_medico`, `alergias`, `created_at`) VALUES
(1, 'anhe', 'lopez', 'anitzoc10@gmail.com', '123231', '2025-11-20', 'U0d4dmZGMEIxU3FWdkpxMEticDlEZz09OjpOG6TDd8gW3ZSNX07T9Lde', 'SkQ1TWxXR2hTa1dZekIrWVpscENldz09OjqQPQWdxur3l6rFnSe644HH', '2025-11-04 20:09:32'),
(2, 'juan', 'werwer', 'ricbenavi21@gmail.com', '+50372123143', '0000-00-00', 'ajlFY3NYTXp1RHVtK1ExTkhlVEZFUT09Ojo6ApphwfwsUp+W9qCC379P', 'bi9BTTJmV29zN0lPZFA4blM1OG5Xdz09OjqRfReDqCfBk15QZPuUrvQI', '2025-11-04 21:45:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$Q1jPPiGlf0/sO2x2ttJWh.3QMRKUPmBO7TUMEdJJsOjE9DQKgdiIG');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `expedientes`
--
ALTER TABLE `expedientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `expedientes`
--
ALTER TABLE `expedientes`
  ADD CONSTRAINT `expedientes_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expedientes_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
