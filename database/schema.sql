-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 18-12-2025 a las 22:19:13
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u228864460_system`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accessories`
--

CREATE TABLE `accessories` (
  `id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('EPP','Glucómetros','Termómetros','Estuche de Langiroscopio','Esfigmomanómetro portátil') NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `property` varchar(255) DEFAULT NULL,
  `inventory_number` varchar(255) DEFAULT NULL,
  `acquisition_type_id` int(11) UNSIGNED DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` int(10) UNSIGNED DEFAULT NULL,
  `numero_parte` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acquisition_type`
--

CREATE TABLE `acquisition_type` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(3) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `branches`
--

CREATE TABLE `branches` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` char(6) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Volcado de datos para la tabla `branches`
--

INSERT INTO `branches` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'HAC', 'Hospital Amerimed Cancún', 'Sucursal principal', '2025-12-14 02:10:37'),
(2, 'HAP', 'Hospital Amerimed Playa del Carmen', NULL, '2025-12-15 16:12:14'),
(3, 'HAM', 'Hospital Amerimed Mérida', NULL, '2025-12-15 20:54:40'),
(5, 'HJC', 'Hospital Prueba', NULL, '2025-12-17 15:24:13'),
(6, 'LAV', 'Lavandería', NULL, '2025-12-17 20:49:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comments`
--

CREATE TABLE `comments` (
  `id` int(30) NOT NULL,
  `user_id` int(30) NOT NULL,
  `user_type` tinyint(1) NOT NULL COMMENT '1= admin, 2= staff,3= customer',
  `ticket_id` int(30) NOT NULL,
  `comment` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `receptions_comments` text DEFAULT NULL,
  `receptios_status` tinyint(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `customers`
--

CREATE TABLE `customers` (
  `id` int(50) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `username` varchar(600) DEFAULT NULL,
  `password` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `avatar` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dashboard_cache`
--

CREATE TABLE `dashboard_cache` (
  `cache_key` varchar(100) NOT NULL,
  `cache_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cache_data`)),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departments`
--

CREATE TABLE `departments` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipments`
--

CREATE TABLE `equipments` (
  `id` int(11) UNSIGNED NOT NULL,
  `number_inventory` varchar(255) DEFAULT NULL,
  `serie` varchar(255) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `brand` varchar(255) NOT NULL DEFAULT '',
  `model` varchar(255) NOT NULL DEFAULT '',
  `acquisition_type` int(11) UNSIGNED NOT NULL,
  `mandate_period_id` int(10) UNSIGNED NOT NULL,
  `amount` double(11,2) DEFAULT NULL,
  `discipline` varchar(255) DEFAULT NULL,
  `equipment_category_id` int(11) DEFAULT NULL,
  `characteristics` text DEFAULT NULL,
  `revision` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `branch_id` int(10) UNSIGNED DEFAULT NULL,
  `inventario_anterior` varchar(255) DEFAULT NULL,
  `numero_parte` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `id` int(11) NOT NULL,
  `clave` varchar(3) NOT NULL,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_control_documents`
--

CREATE TABLE `equipment_control_documents` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) UNSIGNED NOT NULL,
  `invoice` varchar(255) NOT NULL DEFAULT '',
  `bailment_file` varchar(255) DEFAULT NULL,
  `contract_file` varchar(255) DEFAULT NULL,
  `usermanual_file` varchar(255) DEFAULT NULL,
  `fast_guide_file` varchar(255) DEFAULT NULL,
  `datasheet_file` varchar(255) DEFAULT NULL,
  `servicemanual_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_delivery`
--

CREATE TABLE `equipment_delivery` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) UNSIGNED NOT NULL,
  `department_id` int(11) UNSIGNED DEFAULT NULL,
  `location_id` int(11) UNSIGNED DEFAULT NULL,
  `responsible_name` varchar(255) DEFAULT NULL,
  `responsible_position` int(11) UNSIGNED DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `date_training` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_deparments`
--

CREATE TABLE `equipment_deparments` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_power_specs`
--

CREATE TABLE `equipment_power_specs` (
  `id` int(11) NOT NULL,
  `equipment_id` int(10) UNSIGNED NOT NULL,
  `voltage` decimal(6,2) NOT NULL,
  `amperage` decimal(6,2) NOT NULL,
  `frequency_hz` decimal(5,2) NOT NULL DEFAULT 60.00,
  `power_w` decimal(8,2) DEFAULT NULL,
  `measurement_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_reception`
--

CREATE TABLE `equipment_reception` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) UNSIGNED DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_report_sistem`
--

CREATE TABLE `equipment_report_sistem` (
  `id` int(11) NOT NULL,
  `orden_servicio` varchar(50) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `numero_inv` varchar(255) DEFAULT NULL,
  `serie` varchar(255) DEFAULT NULL,
  `modelo` varchar(255) DEFAULT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `tipo_servicio` varchar(50) DEFAULT NULL,
  `dia` varchar(255) DEFAULT NULL,
  `mes` varchar(255) DEFAULT NULL,
  `yea` varchar(255) DEFAULT NULL,
  `inicio` varchar(255) DEFAULT NULL,
  `fin` varchar(255) DEFAULT NULL,
  `fecha_servicio` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `mantenimientoPreventivo` varchar(255) DEFAULT NULL,
  `unidad_riesgo` varchar(255) DEFAULT NULL,
  `componentes` varchar(255) DEFAULT NULL,
  `toner` varchar(255) DEFAULT NULL,
  `impresiom_pruebas` varchar(255) DEFAULT NULL,
  `numero1` varchar(255) DEFAULT NULL,
  `material1` varchar(255) DEFAULT NULL,
  `numero2` varchar(255) DEFAULT NULL,
  `material2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_revision`
--

CREATE TABLE `equipment_revision` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `date_revision` date DEFAULT NULL,
  `frecuencia` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_safeguard`
--

CREATE TABLE `equipment_safeguard` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) UNSIGNED NOT NULL,
  `rfc_id` int(11) DEFAULT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `phone` int(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `warranty_time` int(11) DEFAULT NULL,
  `date_adquisition` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_unsubscribe`
--

CREATE TABLE `equipment_unsubscribe` (
  `id` int(11) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `time` time DEFAULT NULL,
  `equipment_id` int(11) NOT NULL,
  `withdrawal_reason` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(600) DEFAULT NULL,
  `comments` varchar(600) DEFAULT NULL,
  `opinion` tinyint(1) DEFAULT NULL,
  `destination` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_by_name` varchar(255) DEFAULT NULL,
  `folio` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `responsible` int(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipment_withdrawal_reason`
--

CREATE TABLE `equipment_withdrawal_reason` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory`
--

CREATE TABLE `inventory` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `min_stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `branch_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_config`
--

CREATE TABLE `inventory_config` (
  `id` int(11) NOT NULL,
  `branch_id` int(10) UNSIGNED NOT NULL,
  `acquisition_type_id` int(11) DEFAULT NULL,
  `equipment_category_id` int(11) DEFAULT NULL,
  `prefix` varchar(10) NOT NULL,
  `current_number` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_positions`
--

CREATE TABLE `job_positions` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `location_id` int(11) UNSIGNED DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations`
--

CREATE TABLE `locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `location_positions`
--

CREATE TABLE `location_positions` (
  `id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `job_position_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_periods`
--

CREATE TABLE `maintenance_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `days_interval` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_reports`
--

CREATE TABLE `maintenance_reports` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `report_date` varchar(20) NOT NULL,
  `report_time` time DEFAULT NULL,
  `engineer_name` varchar(100) NOT NULL DEFAULT 'ING. AMALIA BACAB',
  `client_name` varchar(150) DEFAULT NULL,
  `client_phone` varchar(50) DEFAULT NULL,
  `client_address` text DEFAULT NULL,
  `client_email` varchar(100) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `equipment_name` varchar(150) DEFAULT NULL,
  `equipment_brand` varchar(100) DEFAULT NULL,
  `equipment_model` varchar(100) DEFAULT NULL,
  `equipment_serial` varchar(100) DEFAULT NULL,
  `equipment_inventory_code` varchar(100) DEFAULT NULL,
  `equipment_location` varchar(150) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `service_type` enum('INSTALACION','MP','MC','SOPORTE TECNICO','PREDICTIVO') NOT NULL DEFAULT 'MP',
  `execution_type` enum('PLAZA','TALLER') NOT NULL DEFAULT 'PLAZA',
  `service_date` date DEFAULT NULL,
  `service_start_time` time DEFAULT NULL,
  `service_end_time` time DEFAULT NULL,
  `description` text DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `final_status` enum('FUNCIONAL','STAND BY','SIN REPARACION') NOT NULL DEFAULT 'FUNCIONAL',
  `received_by` varchar(150) DEFAULT NULL,
  `parts_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parts_used`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `admin_name` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int(11) NOT NULL,
  `equipo_id` int(10) UNSIGNED NOT NULL,
  `fecha_programada` date NOT NULL,
  `tipo_mantenimiento` varchar(20) NOT NULL DEFAULT 'Preventivo',
  `hora_programada` time DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `estatus` enum('pendiente','completado') DEFAULT 'pendiente',
  `created_at` datetime DEFAULT current_timestamp(),
  `branch_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quote`
--

CREATE TABLE `quote` (
  `id` int(30) NOT NULL,
  `email` varchar(250) NOT NULL,
  `service_ids` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `responsibles`
--

CREATE TABLE `responsibles` (
  `id` int(11) UNSIGNED NOT NULL,
  `equipment_id` int(11) UNSIGNED NOT NULL,
  `responsible_name` varchar(100) NOT NULL,
  `job_position_id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `responsible_positions`
--

CREATE TABLE `responsible_positions` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `services`
--

CREATE TABLE `services` (
  `id` int(30) NOT NULL,
  `category_id` int(30) NOT NULL,
  `service` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `img_path` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `services_category`
--

CREATE TABLE `services_category` (
  `id` int(30) NOT NULL,
  `category` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `staff`
--

CREATE TABLE `staff` (
  `id` int(30) NOT NULL,
  `department_id` int(30) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `empresa` varchar(255) NOT NULL,
  `rfc` varchar(13) DEFAULT NULL,
  `representante` varchar(150) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `sitio_web` varchar(255) DEFAULT NULL,
  `sector` varchar(150) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_info`
--

CREATE TABLE `system_info` (
  `id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
(1, 'name', 'Sistema de Tickets'),
(4, 'intro', 'Hi! I&apos;m Johnny, a ChatBot of this application. How can I help you?'),
(6, 'short_name', 'Ticlets'),
(10, 'no_result', 'I am sorry. I can&apos;t understand your question. Please rephrase your question and make sure it is related to this site. Thank you :)'),
(11, 'logo', 'uploads/1625709600_tech-support-icon-png-0.jpg'),
(12, 'bot_avatar', 'uploads/bot_avatar.png'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'welcome_message', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(30) NOT NULL,
  `subject` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL COMMENT '0=Pending,1=on process,2= Closed',
  `service_id` int(11) DEFAULT NULL,
  `department_id` int(30) DEFAULT NULL,
  `customer_id` int(30) DEFAULT NULL,
  `staff_id` int(30) DEFAULT NULL,
  `admin_id` int(30) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp(),
  `title` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_created` varchar(255) NOT NULL DEFAULT 'CURRENT_TIMESTAMP',
  `supplier_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL COMMENT 'ID del equipo relacionado',
  `reporter_name` varchar(255) DEFAULT NULL COMMENT 'Nombre de quien reporta (público)',
  `reporter_email` varchar(255) DEFAULT NULL COMMENT 'Email de quien reporta (público)',
  `reporter_phone` varchar(50) DEFAULT NULL COMMENT 'Teléfono de quien reporta (público)',
  `issue_type` varchar(100) DEFAULT NULL COMMENT 'Tipo de falla reportada',
  `ticket_number` varchar(50) DEFAULT NULL COMMENT 'Número único de ticket',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '1=Ticket público (QR), 0=Ticket normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_comment`
--

CREATE TABLE `ticket_comment` (
  `id` int(30) NOT NULL,
  `ticket_id` int(30) NOT NULL,
  `comment` text DEFAULT NULL,
  `user_id` varchar(30) NOT NULL,
  `user_created` varchar(50) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools`
--

CREATE TABLE `tools` (
  `id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `costo` decimal(10,2) NOT NULL,
  `fecha_adquisicion` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `estatus` enum('Activa','Inactiva') DEFAULT 'Activa',
  `fecha_baja` date DEFAULT NULL,
  `caracteristicas` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `branch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(50) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `role` tinyint(1) NOT NULL COMMENT '1 = Admin,2=support',
  `username` varchar(200) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` text NOT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `date_created` int(11) NOT NULL,
  `active_branch_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `role`, `username`, `password`, `avatar`, `date_created`, `active_branch_id`) VALUES
(14, 'Admin', '', 'System', 1, 'admin', '0192023a7bbd73250516f069df18b500', 'default-avatar.png', 1766096319, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accessories`
--
ALTER TABLE `accessories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_inventario` (`inventory_number`),
  ADD KEY `area_id` (`area_id`),
  ADD KEY `fk_accessories_acquisition_type` (`acquisition_type_id`),
  ADD KEY `fk_accessories_branch` (`branch_id`);

--
-- Indices de la tabla `acquisition_type`
--
ALTER TABLE `acquisition_type`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_acquisition_type_code` (`code`);

--
-- Indices de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_branch_code` (`code`);

--
-- Indices de la tabla `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `dashboard_cache`
--
ALTER TABLE `dashboard_cache`
  ADD PRIMARY KEY (`cache_key`);

--
-- Indices de la tabla `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `equipments`
--
ALTER TABLE `equipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acquisition_type` (`acquisition_type`),
  ADD KEY `fk_equipment_supplier` (`supplier_id`),
  ADD KEY `fk_mandate_period` (`mandate_period_id`),
  ADD KEY `fk_equipments_branch` (`branch_id`),
  ADD KEY `idx_date_created` (`date_created`);

--
-- Indices de la tabla `equipment_categories`
--
ALTER TABLE `equipment_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_equipment_categories_clave` (`clave`);

--
-- Indices de la tabla `equipment_control_documents`
--
ALTER TABLE `equipment_control_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indices de la tabla `equipment_delivery`
--
ALTER TABLE `equipment_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `location_id` (`location_id`),
  ADD KEY `responsible_position` (`responsible_position`),
  ADD KEY `idx_equipment_delivery_department` (`department_id`),
  ADD KEY `idx_equipment_delivery_position` (`responsible_position`);

--
-- Indices de la tabla `equipment_deparments`
--
ALTER TABLE `equipment_deparments`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `equipment_power_specs`
--
ALTER TABLE `equipment_power_specs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipment` (`equipment_id`),
  ADD KEY `idx_date` (`measurement_date`);

--
-- Indices de la tabla `equipment_reception`
--
ALTER TABLE `equipment_reception`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indices de la tabla `equipment_report_sistem`
--
ALTER TABLE `equipment_report_sistem`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `equipment_revision`
--
ALTER TABLE `equipment_revision`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `equipment_safeguard`
--
ALTER TABLE `equipment_safeguard`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indices de la tabla `equipment_unsubscribe`
--
ALTER TABLE `equipment_unsubscribe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipment_id` (`equipment_id`);

--
-- Indices de la tabla `equipment_withdrawal_reason`
--
ALTER TABLE `equipment_withdrawal_reason`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inventory_branch` (`branch_id`);

--
-- Indices de la tabla `inventory_config`
--
ALTER TABLE `inventory_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_inventory_cfg` (`branch_id`,`acquisition_type_id`,`equipment_category_id`);

--
-- Indices de la tabla `job_positions`
--
ALTER TABLE `job_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_job_position_location` (`location_id`),
  ADD KEY `fk_job_position_department` (`department_id`);

--
-- Indices de la tabla `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_location_department` (`department_id`);

--
-- Indices de la tabla `location_positions`
--
ALTER TABLE `location_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_equipment_location_positions_location` (`location_id`),
  ADD KEY `fk_equipment_location_positions_job` (`job_position_id`);

--
-- Indices de la tabla `maintenance_periods`
--
ALTER TABLE `maintenance_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_number`),
  ADD KEY `idx_equipment` (`equipment_id`),
  ADD KEY `idx_date` (`report_date`),
  ADD KEY `idx_service_type` (`service_type`),
  ADD KEY `idx_service_date` (`service_date`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipo` (`equipo_id`),
  ADD KEY `fk_mantenimientos_branch` (`branch_id`);

--
-- Indices de la tabla `quote`
--
ALTER TABLE `quote`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `responsibles`
--
ALTER TABLE `responsibles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_responsible_equipment` (`equipment_id`),
  ADD KEY `fk_responsible_position` (`job_position_id`),
  ADD KEY `fk_responsible_location` (`location_id`);

--
-- Indices de la tabla `responsible_positions`
--
ALTER TABLE `responsible_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `services_category`
--
ALTER TABLE `services_category`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rfc` (`rfc`);

--
-- Indices de la tabla `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ticket_supplier` (`supplier_id`),
  ADD KEY `idx_is_public` (`is_public`),
  ADD KEY `idx_equipment_id` (`equipment_id`),
  ADD KEY `idx_ticket_number` (`ticket_number`);

--
-- Indices de la tabla `ticket_comment`
--
ALTER TABLE `ticket_comment`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proveedor_id` (`supplier_id`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accessories`
--
ALTER TABLE `accessories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `acquisition_type`
--
ALTER TABLE `acquisition_type`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT de la tabla `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `equipments`
--
ALTER TABLE `equipments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=313;

--
-- AUTO_INCREMENT de la tabla `equipment_categories`
--
ALTER TABLE `equipment_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `equipment_control_documents`
--
ALTER TABLE `equipment_control_documents`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT de la tabla `equipment_delivery`
--
ALTER TABLE `equipment_delivery`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=318;

--
-- AUTO_INCREMENT de la tabla `equipment_deparments`
--
ALTER TABLE `equipment_deparments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `equipment_power_specs`
--
ALTER TABLE `equipment_power_specs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=258;

--
-- AUTO_INCREMENT de la tabla `equipment_reception`
--
ALTER TABLE `equipment_reception`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=300;

--
-- AUTO_INCREMENT de la tabla `equipment_report_sistem`
--
ALTER TABLE `equipment_report_sistem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `equipment_revision`
--
ALTER TABLE `equipment_revision`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `equipment_safeguard`
--
ALTER TABLE `equipment_safeguard`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- AUTO_INCREMENT de la tabla `equipment_unsubscribe`
--
ALTER TABLE `equipment_unsubscribe`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `equipment_withdrawal_reason`
--
ALTER TABLE `equipment_withdrawal_reason`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `inventory_config`
--
ALTER TABLE `inventory_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT de la tabla `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT de la tabla `location_positions`
--
ALTER TABLE `location_positions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `maintenance_periods`
--
ALTER TABLE `maintenance_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `maintenance_reports`
--
ALTER TABLE `maintenance_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6055;

--
-- AUTO_INCREMENT de la tabla `quote`
--
ALTER TABLE `quote`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `responsibles`
--
ALTER TABLE `responsibles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `responsible_positions`
--
ALTER TABLE `responsible_positions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `services`
--
ALTER TABLE `services`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `services_category`
--
ALTER TABLE `services_category`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `ticket_comment`
--
ALTER TABLE `ticket_comment`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `accessories`
--
ALTER TABLE `accessories`
  ADD CONSTRAINT `accessories_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_accessories_acquisition_type` FOREIGN KEY (`acquisition_type_id`) REFERENCES `acquisition_type` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_accessories_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `equipments`
--
ALTER TABLE `equipments`
  ADD CONSTRAINT `equipments_ibfk_1` FOREIGN KEY (`acquisition_type`) REFERENCES `acquisition_type` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equipment_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equipments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mandate_period` FOREIGN KEY (`mandate_period_id`) REFERENCES `maintenance_periods` (`id`);

--
-- Filtros para la tabla `equipment_control_documents`
--
ALTER TABLE `equipment_control_documents`
  ADD CONSTRAINT `equipment_control_documents_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipment_delivery`
--
ALTER TABLE `equipment_delivery`
  ADD CONSTRAINT `equipment_delivery_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `equipment_delivery_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `equipment_delivery_position_fk` FOREIGN KEY (`responsible_position`) REFERENCES `job_positions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipment_power_specs`
--
ALTER TABLE `equipment_power_specs`
  ADD CONSTRAINT `fk_equipment_power` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `equipment_reception`
--
ALTER TABLE `equipment_reception`
  ADD CONSTRAINT `equipment_reception_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `equipment_safeguard`
--
ALTER TABLE `equipment_safeguard`
  ADD CONSTRAINT `equipment_safeguard_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `inventory_config`
--
ALTER TABLE `inventory_config`
  ADD CONSTRAINT `inventory_config_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Filtros para la tabla `job_positions`
--
ALTER TABLE `job_positions`
  ADD CONSTRAINT `fk_job_position_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_job_position_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_location_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `location_positions`
--
ALTER TABLE `location_positions`
  ADD CONSTRAINT `fk_equipment_location_positions_job` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equipment_location_positions_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `fk_mantenimientos_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mantenimientos_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `responsibles`
--
ALTER TABLE `responsibles`
  ADD CONSTRAINT `fk_responsible_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsible_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_responsible_position` FOREIGN KEY (`job_position_id`) REFERENCES `job_positions` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `fk_tools_proveedor` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
