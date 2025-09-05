-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 24-08-2025 a las 05:28:49
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
-- Base de datos: `sistema_admin_eest2`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_adjuntos`
--

CREATE TABLE `archivos_adjuntos` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `tipo` enum('certificado_medico','constancia','otro') NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_emergencia`
--

CREATE TABLE `contactos_emergencia` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `parentesco` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contactos_emergencia`
--

INSERT INTO `contactos_emergencia` (`id`, `estudiante_id`, `nombre`, `telefono`, `parentesco`) VALUES
(1, 1, 'Roberto García', '15-1234-5678', 'Padre'),
(2, 2, 'Carlos López', '15-1234-5680', 'Padre'),
(3, 3, 'Ana Martínez', '15-1234-5681', 'Madre'),
(4, 4, 'Miguel Rodríguez', '15-1234-5682', 'Padre'),
(5, 5, 'Lucía Fernández', '15-1234-5683', 'Madre');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `division` varchar(5) NOT NULL,
  `turno_id` int(11) NOT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `taller_id` int(11) DEFAULT NULL,
  `grado` enum('inferior','superior') NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `anio`, `division`, `turno_id`, `especialidad_id`, `taller_id`, `grado`, `activo`) VALUES
(1, 1, 'A', 1, NULL, NULL, 'inferior', 1),
(2, 1, 'B', 1, NULL, NULL, 'inferior', 1),
(3, 2, 'A', 1, NULL, NULL, 'inferior', 1),
(4, 2, 'B', 1, NULL, NULL, 'inferior', 1),
(5, 3, 'A', 1, NULL, NULL, 'inferior', 1),
(6, 4, 'A', 1, 1, NULL, 'superior', 1),
(7, 5, 'A', 1, 1, NULL, 'superior', 1),
(8, 6, 'A', 1, 1, NULL, 'superior', 1),
(9, 7, 'A', 1, 1, NULL, 'superior', 1),
(10, 1, 'C', 1, NULL, NULL, 'inferior', 1),
(11, 1, 'D', 2, NULL, NULL, 'inferior', 1),
(12, 2, 'C', 1, NULL, NULL, 'inferior', 1),
(13, 2, 'D', 2, NULL, NULL, 'inferior', 1),
(14, 3, 'C', 1, NULL, NULL, 'inferior', 1),
(15, 4, 'C', 1, 2, NULL, 'superior', 1),
(16, 5, 'C', 1, 2, NULL, 'superior', 1),
(17, 6, 'C', 1, 2, NULL, 'superior', 1),
(18, 7, 'C', 1, 2, NULL, 'superior', 1),
(19, 1, 'E', 2, NULL, NULL, 'inferior', 1),
(20, 2, 'E', 2, NULL, NULL, 'inferior', 1),
(21, 3, 'E', 2, NULL, NULL, 'inferior', 1),
(22, 4, 'E', 2, 3, NULL, 'superior', 1),
(23, 5, 'E', 2, 3, NULL, 'superior', 1),
(24, 6, 'E', 2, 3, NULL, 'superior', 1),
(25, 7, 'E', 2, 3, NULL, 'superior', 1),
(26, 1, 'G', 2, NULL, NULL, 'inferior', 1),
(27, 2, 'G', 2, NULL, NULL, 'inferior', 1),
(28, 3, 'G', 2, NULL, NULL, 'inferior', 1),
(29, 4, 'G', 2, 4, NULL, 'superior', 1),
(30, 5, 'G', 2, 4, NULL, 'superior', 1),
(31, 6, 'G', 2, 4, NULL, 'superior', 1),
(32, 7, 'G', 2, 4, NULL, 'superior', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipo_directivo`
--

CREATE TABLE `equipo_directivo` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `apellido` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `equipo_directivo`
--

INSERT INTO `equipo_directivo` (`id`, `usuario_id`, `apellido`, `nombre`, `cargo`, `telefono`, `email`, `foto`, `activo`) VALUES
(1, 2, 'González', 'Juan Carlos', 'Director', '011-4567-8001', 'director@eest2.edu.ar', NULL, 1),
(2, NULL, 'Martínez', 'Ana María', 'Vicedirectora', '011-4567-8002', 'vicedirectora@eest2.edu.ar', NULL, 0),
(3, NULL, 'López', 'Carlos Alberto', 'Secretario', '011-4567-8003', 'secretario@eest2.edu.ar', NULL, 0),
(4, NULL, 'Fernández', 'María Elena', 'Secretaria', '011-4567-8004', 'secretaria@eest2.edu.ar', NULL, 0),
(5, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'martinez08alan@gmail.com', NULL, 0),
(6, NULL, 'Martínez', 'as', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 0),
(7, NULL, 'Martínez', 'a', 'preceptor', '32', 'lucas.acosta@email.com', NULL, 0),
(8, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 0),
(9, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 0),
(10, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 0),
(11, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 0),
(13, NULL, 'Martínez', 'a', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 1),
(14, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 1),
(15, NULL, 'Martínez', 'Alan Ezequiel', 'preceptor', '223 671-3071', 'lucas.acosta@email.com', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id`, `nombre`, `descripcion`, `activa`) VALUES
(1, 'Informática', 'Especialidad en programación y sistemas informáticos', 1),
(2, 'Electromecánica', 'Especialidad en mecánica y electricidad industrial', 1),
(3, 'Construcciones', 'Especialidad en construcción civil y arquitectura', 1),
(4, 'Química', 'Especialidad en procesos químicos e industriales', 1),
(5, 'Electromecánica', 'Especialidad en electromecánica industrial', 0),
(6, 'Informática', 'Especialidad en informática y programación', 0),
(7, 'Química', 'Especialidad en química industrial', 0),
(8, 'Construcciones', 'Especialidad en construcciones civiles', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `grupo_sanguineo` varchar(10) DEFAULT NULL,
  `obra_social` varchar(100) DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `telefono_fijo` varchar(20) DEFAULT NULL,
  `telefono_celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_ingreso` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `dni`, `apellido`, `nombre`, `fecha_nacimiento`, `grupo_sanguineo`, `obra_social`, `domicilio`, `telefono_fijo`, `telefono_celular`, `email`, `foto`, `curso_id`, `activo`, `fecha_ingreso`) VALUES
(1, '40123456', 'García', 'María', '2008-03-15', 'A+', 'OSDE', 'Av. San Martín 1234', '011-4567-8901', '15-1234-5678', 'maria.garcia@email.com', NULL, 1, 1, '2025-08-21'),
(2, '40123457', 'López', 'Juan', '2008-07-22', 'O+', 'Swiss Medical', 'Belgrano 567', '011-4567-8902', '15-1234-5679', 'juan.lopez@email.com', NULL, 1, 1, '2025-08-21'),
(3, '40123458', 'Martínez', 'Ana', '2008-11-08', 'B+', 'Galeno', 'Rivadavia 890', '011-4567-8903', '15-1234-5680', 'ana.martinez@email.com', NULL, 1, 1, '2025-08-21'),
(4, '40123459', 'Rodríguez', 'Carlos', '2008-05-30', 'AB+', 'OSDE', 'Mitre 234', '011-4567-8904', '15-1234-5681', 'carlos.rodriguez@email.com', NULL, 1, 1, '2025-08-21'),
(5, '40123460', 'Fernández', 'Lucía', '2008-09-12', 'A-', 'Swiss Medical', 'Sarmiento 456', '011-4567-8905', '15-1234-5682', 'lucia.fernandez@email.com', NULL, 1, 1, '2025-08-21'),
(6, '40123461', 'González', 'Diego', '2008-01-25', 'O+', 'Galeno', 'San Juan 789', '011-4567-8906', '15-1234-5683', 'diego.gonzalez@email.com', NULL, 2, 1, '2025-08-21'),
(7, '40123462', 'Pérez', 'Valentina', '2008-04-18', 'B+', 'OSDE', 'Corrientes 321', '011-4567-8907', '15-1234-5684', 'valentina.perez@email.com', NULL, 2, 1, '2025-08-21'),
(8, '40123463', 'Sánchez', 'Matías', '2008-08-05', 'A+', 'Swiss Medical', 'Córdoba 654', '011-4567-8908', '15-1234-5685', 'matias.sanchez@email.com', NULL, 2, 1, '2025-08-21'),
(9, '40123464', 'Torres', 'Camila', '2008-12-14', 'O-', 'Galeno', 'Lavalle 987', '011-4567-8909', '15-1234-5686', 'camila.torres@email.com', NULL, 2, 1, '2025-08-21'),
(10, '40123465', 'Ramírez', 'Santiago', '2008-06-20', 'AB+', 'OSDE', 'Florida 147', '011-4567-8910', '15-1234-5687', 'santiago.ramirez@email.com', NULL, 2, 1, '2025-08-21'),
(11, '39123456', 'Herrera', 'Sofía', '2007-02-10', 'A+', 'Swiss Medical', 'Callao 258', '011-4567-8911', '15-1234-5688', 'sofia.herrera@email.com', NULL, 3, 1, '2025-08-21'),
(12, '39123457', 'Jiménez', 'Nicolás', '2007-05-28', 'O+', 'Galeno', 'Santa Fe 369', '011-4567-8912', '15-1234-5689', 'nicolas.jimenez@email.com', NULL, 3, 1, '2025-08-21'),
(13, '39123458', 'Ruiz', 'Isabella', '2007-10-15', 'B+', 'OSDE', 'Pueyrredón 741', '011-4567-8913', '15-1234-5690', 'isabella.ruiz@email.com', NULL, 3, 1, '2025-08-21'),
(14, '39123459', 'Díaz', 'Facundo', '2007-07-03', 'AB+', 'Swiss Medical', 'Alem 852', '011-4567-8914', '15-1234-5691', 'facundo.diaz@email.com', NULL, 3, 1, '2025-08-21'),
(15, '39123460', 'Moreno', 'Agustina', '2007-12-22', 'A-', 'Galeno', 'Reconquista 963', '011-4567-8915', '15-1234-5692', 'agustina.moreno@email.com', NULL, 3, 1, '2025-08-21'),
(16, '40123466', 'Castro', 'Lucas', '2008-03-08', 'O+', 'OSDE', 'Viamonte 159', '011-4567-8916', '15-1234-5693', 'lucas.castro@email.com', NULL, 10, 1, '2025-08-21'),
(17, '40123467', 'Ortiz', 'Mía', '2008-06-17', 'B+', 'Swiss Medical', 'Tucumán 753', '011-4567-8917', '15-1234-5694', 'mia.ortiz@email.com', NULL, 10, 1, '2025-08-21'),
(18, '40123468', 'Silva', 'Thiago', '2008-09-25', 'A+', 'Galeno', 'Paraguay 951', '011-4567-8918', '15-1234-5695', 'thiago.silva@email.com', NULL, 10, 1, '2025-08-21'),
(19, '40123469', 'Cruz', 'Emma', '2008-11-30', 'AB+', 'OSDE', 'Esmeralda 357', '011-4567-8919', '15-1234-5696', 'emma.cruz@email.com', NULL, 10, 1, '2025-08-21'),
(20, '40123470', 'Reyes', 'Benjamín', '2008-04-12', 'O-', 'Swiss Medical', 'Maipú 486', '011-4567-8920', '15-1234-5697', 'benjamin.reyes@email.com', NULL, 10, 1, '2025-08-21'),
(21, '40123471', 'Morales', 'Zoe', '2008-08-19', 'A+', 'Galeno', 'Suipacha 753', '011-4567-8921', '15-1234-5698', 'zoe.morales@email.com', NULL, 11, 1, '2025-08-21'),
(22, '40123472', 'Flores', 'Axel', '2008-01-07', 'B+', 'OSDE', 'Libertad 159', '011-4567-8922', '15-1234-5699', 'axel.flores@email.com', NULL, 11, 1, '2025-08-21'),
(23, '40123473', 'Acosta', 'Luna', '2008-05-14', 'O+', 'Swiss Medical', 'Talcahuano 486', '011-4567-8923', '15-1234-5700', 'luna.acosta@email.com', NULL, 11, 1, '2025-08-21'),
(24, '40123474', 'Medina', 'Ian', '2008-10-28', 'AB+', 'Galeno', 'Ayacucho 753', '011-4567-8924', '15-1234-5701', 'ian.medina@email.com', NULL, 11, 1, '2025-08-21'),
(25, '40123475', 'Vargas', 'Aria', '2008-12-05', 'A-', 'OSDE', 'Junín 159', '011-4567-8925', '15-1234-5702', 'aria.vargas@email.com', NULL, 11, 1, '2025-08-21');

--
-- Disparadores `estudiantes`
--
DELIMITER $$
CREATE TRIGGER `estudiantes_updated` BEFORE UPDATE ON `estudiantes` FOR EACH ROW BEGIN
        -- Aquí podrías agregar lógica adicional si fuera necesaria
        SET NEW.fecha_ingreso = COALESCE(NEW.fecha_ingreso, OLD.fecha_ingreso);
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `lugar` varchar(100) DEFAULT NULL,
  `tipo` enum('academico','deportivo','cultural','institucional','otro') DEFAULT 'institucional',
  `publico` tinyint(1) DEFAULT 1,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `dia_semana` int(11) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `aula` varchar(50) DEFAULT NULL,
  `docente` varchar(100) DEFAULT NULL,
  `es_contraturno` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id`, `curso_id`, `materia_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `aula`, `docente`, `es_contraturno`, `activo`) VALUES
(3, 1, 25, 1, '10:45:00', '12:15:00', 'Taller 1', 'Prof. López', 0, 1),
(4, 1, 5, 2, '07:30:00', '09:00:00', 'Aula 101', 'Prof. Martínez', 0, 1),
(6, 1, 26, 2, '10:45:00', '12:15:00', 'Laboratorio 1', 'Prof. Silva', 0, 1),
(10, 10, 5, 2, '07:30:00', '09:00:00', 'Aula 201', 'Prof. Martínez', 0, 1),
(11, 10, 34, 2, '09:00:00', '10:30:00', 'Laboratorio Informática', 'Prof. Ortiz', 0, 1),
(13, 1, 5, 5, '07:00:00', '09:00:00', 'Aula 101', 'Fernández Lucía Beatriz', 0, 1);

--
-- Disparadores `horarios`
--
DELIMITER $$
CREATE TRIGGER `horarios_conflict_check` BEFORE INSERT ON `horarios` FOR EACH ROW BEGIN
        DECLARE conflict_count INT DEFAULT 0;
        
        SELECT COUNT(*) INTO conflict_count
        FROM horarios h
        WHERE h.curso_id = NEW.curso_id
          AND h.dia_semana = NEW.dia_semana
          AND h.activo = TRUE
          AND (
              (NEW.hora_inicio >= h.hora_inicio AND NEW.hora_inicio < h.hora_fin) OR
              (NEW.hora_fin > h.hora_inicio AND NEW.hora_fin <= h.hora_fin) OR
              (NEW.hora_inicio <= h.hora_inicio AND NEW.hora_fin >= h.hora_fin)
          );
          
        IF conflict_count > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Conflicto de horarios: Ya existe un horario para este curso en el mismo día y horario';
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inasistencias`
--

CREATE TABLE `inasistencias` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('completa','tarde','retiro_anticipado') NOT NULL,
  `justificada` tinyint(1) DEFAULT 0,
  `motivo` varchar(255) DEFAULT NULL,
  `certificado_medico` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `llamados_atencion`
--

CREATE TABLE `llamados_atencion` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `motivo` text NOT NULL,
  `sancion` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `llamados_atencion`
--

INSERT INTO `llamados_atencion` (`id`, `estudiante_id`, `fecha`, `motivo`, `sancion`, `observaciones`, `usuario_id`, `fecha_registro`) VALUES
(1, 2, '2025-08-21', 'Llegada tarde sin justificación', 'Amonestación verbal', 'Segunda llegada tarde en la semana', 1, '2025-08-21 04:16:42'),
(2, 4, '2025-08-19', 'Uso de celular en clase', 'Amonestación escrita', 'Reiterado uso de dispositivos electrónicos', 1, '2025-08-21 04:16:42'),
(3, 16, '2025-08-20', 'Falta de material de trabajo', 'Amonestación verbal', 'No trajo elementos necesarios para la clase', 1, '2025-08-21 04:16:42'),
(4, 17, '2025-08-21', 'Interrupciones en clase', 'Amonestación escrita', 'Constantes interrupciones durante la explicación', 1, '2025-08-21 04:16:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `es_taller` tinyint(1) DEFAULT 0,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id`, `nombre`, `especialidad_id`, `es_taller`, `activa`) VALUES
(1, 'Matemática', NULL, 0, 1),
(2, 'Lengua y Literatura', NULL, 0, 1),
(3, 'Historia', NULL, 0, 1),
(4, 'Geografía', NULL, 0, 1),
(5, 'Física', NULL, 0, 1),
(6, 'Química', NULL, 0, 1),
(7, 'Biología', NULL, 0, 1),
(8, 'Educación Física', NULL, 0, 1),
(9, 'Inglés', NULL, 0, 1),
(10, 'Formación Ética y Ciudadana', NULL, 0, 1),
(11, 'Construcciones', 3, 1, 1),
(12, 'Electrónica', 2, 1, 1),
(13, 'Programación', 1, 1, 1),
(14, 'Laboratorio de Química', 4, 1, 1),
(15, 'Matemática', NULL, 0, 0),
(16, 'Lengua y Literatura', NULL, 0, 0),
(17, 'Historia', NULL, 0, 0),
(18, 'Geografía', NULL, 0, 0),
(19, 'Física', NULL, 0, 0),
(20, 'Química General', NULL, 0, 1),
(21, 'Biología', NULL, 0, 0),
(22, 'Educación Física', NULL, 0, 0),
(23, 'Inglés', NULL, 0, 0),
(24, 'Formación Ética y Ciudadana', NULL, 0, 0),
(25, 'Dibujo Técnico Mecánico', 1, 0, 1),
(26, 'Tecnología de los Materiales', 1, 0, 1),
(27, 'Mecánica Técnica', 1, 0, 1),
(28, 'Electricidad Técnica', 1, 0, 1),
(29, 'Termodinámica', 1, 0, 1),
(30, 'Máquinas Eléctricas', 1, 0, 1),
(31, 'Automatización Industrial', 1, 0, 0),
(32, 'Neumática e Hidráulica', 1, 0, 1),
(33, 'Algoritmos y Programación', 2, 0, 1),
(34, 'Estructuras de Datos', 2, 0, 1),
(35, 'Bases de Datos', 2, 0, 1),
(36, 'Redes de Computadoras', 2, 0, 1),
(37, 'Sistemas Operativos', 2, 0, 1),
(38, 'Desarrollo Web', 2, 0, 1),
(39, 'Inteligencia Artificial', 2, 0, 1),
(40, 'Programación Orientada a Objetos', 2, 0, 1),
(41, 'Química Analítica', 3, 0, 1),
(42, 'Química Orgánica', 3, 0, 1),
(43, 'Química Inorgánica', 3, 0, 1),
(44, 'Fisicoquímica', 3, 0, 1),
(45, 'Microbiología', 3, 0, 1),
(46, 'Control de Calidad', 3, 0, 1),
(47, 'Tecnología de los Alimentos', 3, 0, 1),
(48, 'Química Ambiental', 3, 0, 1),
(49, 'Dibujo Arquitectónico', 4, 0, 1),
(50, 'Resistencia de Materiales', 4, 0, 1),
(51, 'Hidráulica', 4, 0, 1),
(52, 'Topografía', 4, 0, 1),
(53, 'Construcciones Metálicas', 4, 0, 1),
(54, 'Instalaciones Sanitarias', 4, 0, 1),
(55, 'Estructuras de Hormigón', 4, 0, 1),
(56, 'Planificación de Obras', 4, 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias_previas`
--

CREATE TABLE `materias_previas` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `anio_previo` int(11) NOT NULL,
  `estado` enum('pendiente','regularizada','aprobada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materias_previas`
--

INSERT INTO `materias_previas` (`id`, `estudiante_id`, `materia_id`, `anio_previo`, `estado`, `observaciones`, `fecha_registro`) VALUES
(1, 2, 2, 1, 'aprobada', 'Reprobada en etapa final con promedio: 6.75 - Aprobada el 2025-08-21 01:33:39', '2025-08-21 04:27:28'),
(2, 5, 9, 1, 'aprobada', 'Aprobada desde materia previa', '2025-08-21 04:38:14'),
(3, 5, 7, 1, 'aprobada', 'Aprobada desde materia previa', '2025-08-21 04:41:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia_curso`
--

CREATE TABLE `materia_curso` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materia_curso`
--

INSERT INTO `materia_curso` (`id`, `materia_id`, `curso_id`, `fecha_asignacion`) VALUES
(1, 1, 1, '2025-08-21 04:16:42'),
(2, 1, 2, '2025-08-21 04:16:42'),
(3, 1, 3, '2025-08-21 04:16:42'),
(4, 1, 4, '2025-08-21 04:16:42'),
(5, 1, 5, '2025-08-21 04:16:42'),
(6, 1, 6, '2025-08-21 04:16:42'),
(7, 1, 7, '2025-08-21 04:16:42'),
(8, 1, 8, '2025-08-21 04:16:42'),
(9, 1, 9, '2025-08-21 04:16:42'),
(10, 1, 10, '2025-08-21 04:16:42'),
(11, 1, 11, '2025-08-21 04:16:42'),
(12, 1, 12, '2025-08-21 04:16:42'),
(13, 1, 13, '2025-08-21 04:16:42'),
(14, 1, 14, '2025-08-21 04:16:42'),
(15, 1, 15, '2025-08-21 04:16:42'),
(16, 1, 16, '2025-08-21 04:16:42'),
(17, 1, 17, '2025-08-21 04:16:42'),
(18, 1, 18, '2025-08-21 04:16:42'),
(19, 1, 19, '2025-08-21 04:16:42'),
(20, 1, 20, '2025-08-21 04:16:42'),
(21, 1, 21, '2025-08-21 04:16:42'),
(22, 1, 22, '2025-08-21 04:16:42'),
(23, 1, 23, '2025-08-21 04:16:42'),
(24, 1, 24, '2025-08-21 04:16:42'),
(25, 1, 25, '2025-08-21 04:16:42'),
(26, 1, 26, '2025-08-21 04:16:42'),
(27, 1, 27, '2025-08-21 04:16:42'),
(28, 1, 28, '2025-08-21 04:16:42'),
(29, 1, 29, '2025-08-21 04:16:42'),
(30, 1, 30, '2025-08-21 04:16:42'),
(31, 1, 31, '2025-08-21 04:16:42'),
(32, 1, 32, '2025-08-21 04:16:42'),
(33, 2, 1, '2025-08-21 04:16:42'),
(34, 2, 2, '2025-08-21 04:16:42'),
(35, 2, 3, '2025-08-21 04:16:42'),
(36, 2, 4, '2025-08-21 04:16:42'),
(37, 2, 5, '2025-08-21 04:16:42'),
(38, 2, 6, '2025-08-21 04:16:42'),
(39, 2, 7, '2025-08-21 04:16:42'),
(40, 2, 8, '2025-08-21 04:16:42'),
(41, 2, 9, '2025-08-21 04:16:42'),
(42, 2, 10, '2025-08-21 04:16:42'),
(43, 2, 11, '2025-08-21 04:16:42'),
(44, 2, 12, '2025-08-21 04:16:42'),
(45, 2, 13, '2025-08-21 04:16:42'),
(46, 2, 14, '2025-08-21 04:16:42'),
(47, 2, 15, '2025-08-21 04:16:42'),
(48, 2, 16, '2025-08-21 04:16:42'),
(49, 2, 17, '2025-08-21 04:16:42'),
(50, 2, 18, '2025-08-21 04:16:42'),
(51, 2, 19, '2025-08-21 04:16:42'),
(52, 2, 20, '2025-08-21 04:16:42'),
(53, 2, 21, '2025-08-21 04:16:42'),
(54, 2, 22, '2025-08-21 04:16:42'),
(55, 2, 23, '2025-08-21 04:16:42'),
(56, 2, 24, '2025-08-21 04:16:42'),
(57, 2, 25, '2025-08-21 04:16:42'),
(58, 2, 26, '2025-08-21 04:16:42'),
(59, 2, 27, '2025-08-21 04:16:42'),
(60, 2, 28, '2025-08-21 04:16:42'),
(61, 2, 29, '2025-08-21 04:16:42'),
(62, 2, 30, '2025-08-21 04:16:42'),
(63, 2, 31, '2025-08-21 04:16:42'),
(64, 2, 32, '2025-08-21 04:16:42'),
(65, 3, 1, '2025-08-21 04:16:42'),
(66, 3, 2, '2025-08-21 04:16:42'),
(67, 3, 3, '2025-08-21 04:16:42'),
(68, 3, 4, '2025-08-21 04:16:42'),
(69, 3, 5, '2025-08-21 04:16:42'),
(70, 3, 6, '2025-08-21 04:16:42'),
(71, 3, 7, '2025-08-21 04:16:42'),
(72, 3, 8, '2025-08-21 04:16:42'),
(73, 3, 9, '2025-08-21 04:16:42'),
(74, 3, 10, '2025-08-21 04:16:42'),
(75, 3, 11, '2025-08-21 04:16:42'),
(76, 3, 12, '2025-08-21 04:16:42'),
(77, 3, 13, '2025-08-21 04:16:42'),
(78, 3, 14, '2025-08-21 04:16:42'),
(79, 3, 15, '2025-08-21 04:16:42'),
(80, 3, 16, '2025-08-21 04:16:42'),
(81, 3, 17, '2025-08-21 04:16:42'),
(82, 3, 18, '2025-08-21 04:16:42'),
(83, 3, 19, '2025-08-21 04:16:42'),
(84, 3, 20, '2025-08-21 04:16:42'),
(85, 3, 21, '2025-08-21 04:16:42'),
(86, 3, 22, '2025-08-21 04:16:42'),
(87, 3, 23, '2025-08-21 04:16:42'),
(88, 3, 24, '2025-08-21 04:16:42'),
(89, 3, 25, '2025-08-21 04:16:42'),
(90, 3, 26, '2025-08-21 04:16:42'),
(91, 3, 27, '2025-08-21 04:16:42'),
(92, 3, 28, '2025-08-21 04:16:42'),
(93, 3, 29, '2025-08-21 04:16:42'),
(94, 3, 30, '2025-08-21 04:16:42'),
(95, 3, 31, '2025-08-21 04:16:42'),
(96, 3, 32, '2025-08-21 04:16:42'),
(97, 4, 1, '2025-08-21 04:16:42'),
(98, 4, 2, '2025-08-21 04:16:42'),
(99, 4, 3, '2025-08-21 04:16:42'),
(100, 4, 4, '2025-08-21 04:16:42'),
(101, 4, 5, '2025-08-21 04:16:42'),
(102, 4, 6, '2025-08-21 04:16:42'),
(103, 4, 7, '2025-08-21 04:16:42'),
(104, 4, 8, '2025-08-21 04:16:42'),
(105, 4, 9, '2025-08-21 04:16:42'),
(106, 4, 10, '2025-08-21 04:16:42'),
(107, 4, 11, '2025-08-21 04:16:42'),
(108, 4, 12, '2025-08-21 04:16:42'),
(109, 4, 13, '2025-08-21 04:16:42'),
(110, 4, 14, '2025-08-21 04:16:42'),
(111, 4, 15, '2025-08-21 04:16:42'),
(112, 4, 16, '2025-08-21 04:16:42'),
(113, 4, 17, '2025-08-21 04:16:42'),
(114, 4, 18, '2025-08-21 04:16:42'),
(115, 4, 19, '2025-08-21 04:16:42'),
(116, 4, 20, '2025-08-21 04:16:42'),
(117, 4, 21, '2025-08-21 04:16:42'),
(118, 4, 22, '2025-08-21 04:16:42'),
(119, 4, 23, '2025-08-21 04:16:42'),
(120, 4, 24, '2025-08-21 04:16:42'),
(121, 4, 25, '2025-08-21 04:16:42'),
(122, 4, 26, '2025-08-21 04:16:42'),
(123, 4, 27, '2025-08-21 04:16:42'),
(124, 4, 28, '2025-08-21 04:16:42'),
(125, 4, 29, '2025-08-21 04:16:42'),
(126, 4, 30, '2025-08-21 04:16:42'),
(127, 4, 31, '2025-08-21 04:16:42'),
(128, 4, 32, '2025-08-21 04:16:42'),
(129, 5, 1, '2025-08-21 04:16:42'),
(130, 5, 2, '2025-08-21 04:16:42'),
(131, 5, 3, '2025-08-21 04:16:42'),
(132, 5, 4, '2025-08-21 04:16:42'),
(133, 5, 5, '2025-08-21 04:16:42'),
(134, 5, 6, '2025-08-21 04:16:42'),
(135, 5, 7, '2025-08-21 04:16:42'),
(136, 5, 8, '2025-08-21 04:16:42'),
(137, 5, 9, '2025-08-21 04:16:42'),
(138, 5, 10, '2025-08-21 04:16:42'),
(139, 5, 11, '2025-08-21 04:16:42'),
(140, 5, 12, '2025-08-21 04:16:42'),
(141, 5, 13, '2025-08-21 04:16:42'),
(142, 5, 14, '2025-08-21 04:16:42'),
(143, 5, 15, '2025-08-21 04:16:42'),
(144, 5, 16, '2025-08-21 04:16:42'),
(145, 5, 17, '2025-08-21 04:16:42'),
(146, 5, 18, '2025-08-21 04:16:42'),
(147, 5, 19, '2025-08-21 04:16:42'),
(148, 5, 20, '2025-08-21 04:16:42'),
(149, 5, 21, '2025-08-21 04:16:42'),
(150, 5, 22, '2025-08-21 04:16:42'),
(151, 5, 23, '2025-08-21 04:16:42'),
(152, 5, 24, '2025-08-21 04:16:42'),
(153, 5, 25, '2025-08-21 04:16:42'),
(154, 5, 26, '2025-08-21 04:16:42'),
(155, 5, 27, '2025-08-21 04:16:42'),
(156, 5, 28, '2025-08-21 04:16:42'),
(157, 5, 29, '2025-08-21 04:16:42'),
(158, 5, 30, '2025-08-21 04:16:42'),
(159, 5, 31, '2025-08-21 04:16:42'),
(160, 5, 32, '2025-08-21 04:16:42'),
(161, 6, 1, '2025-08-21 04:16:42'),
(162, 6, 2, '2025-08-21 04:16:42'),
(163, 6, 3, '2025-08-21 04:16:42'),
(164, 6, 4, '2025-08-21 04:16:42'),
(165, 6, 5, '2025-08-21 04:16:42'),
(166, 6, 6, '2025-08-21 04:16:42'),
(167, 6, 7, '2025-08-21 04:16:42'),
(168, 6, 8, '2025-08-21 04:16:42'),
(169, 6, 9, '2025-08-21 04:16:42'),
(170, 6, 10, '2025-08-21 04:16:42'),
(171, 6, 11, '2025-08-21 04:16:42'),
(172, 6, 12, '2025-08-21 04:16:42'),
(173, 6, 13, '2025-08-21 04:16:42'),
(174, 6, 14, '2025-08-21 04:16:42'),
(175, 6, 15, '2025-08-21 04:16:42'),
(176, 6, 16, '2025-08-21 04:16:42'),
(177, 6, 17, '2025-08-21 04:16:42'),
(178, 6, 18, '2025-08-21 04:16:42'),
(179, 6, 19, '2025-08-21 04:16:42'),
(180, 6, 20, '2025-08-21 04:16:42'),
(181, 6, 21, '2025-08-21 04:16:42'),
(182, 6, 22, '2025-08-21 04:16:42'),
(183, 6, 23, '2025-08-21 04:16:42'),
(184, 6, 24, '2025-08-21 04:16:42'),
(185, 6, 25, '2025-08-21 04:16:42'),
(186, 6, 26, '2025-08-21 04:16:42'),
(187, 6, 27, '2025-08-21 04:16:42'),
(188, 6, 28, '2025-08-21 04:16:42'),
(189, 6, 29, '2025-08-21 04:16:42'),
(190, 6, 30, '2025-08-21 04:16:42'),
(191, 6, 31, '2025-08-21 04:16:42'),
(192, 6, 32, '2025-08-21 04:16:42'),
(193, 7, 1, '2025-08-21 04:16:42'),
(194, 7, 2, '2025-08-21 04:16:42'),
(195, 7, 3, '2025-08-21 04:16:42'),
(196, 7, 4, '2025-08-21 04:16:42'),
(197, 7, 5, '2025-08-21 04:16:42'),
(198, 7, 6, '2025-08-21 04:16:42'),
(199, 7, 7, '2025-08-21 04:16:42'),
(200, 7, 8, '2025-08-21 04:16:42'),
(201, 7, 9, '2025-08-21 04:16:42'),
(202, 7, 10, '2025-08-21 04:16:42'),
(203, 7, 11, '2025-08-21 04:16:42'),
(204, 7, 12, '2025-08-21 04:16:42'),
(205, 7, 13, '2025-08-21 04:16:42'),
(206, 7, 14, '2025-08-21 04:16:42'),
(207, 7, 15, '2025-08-21 04:16:42'),
(208, 7, 16, '2025-08-21 04:16:42'),
(209, 7, 17, '2025-08-21 04:16:42'),
(210, 7, 18, '2025-08-21 04:16:42'),
(211, 7, 19, '2025-08-21 04:16:42'),
(212, 7, 20, '2025-08-21 04:16:42'),
(213, 7, 21, '2025-08-21 04:16:42'),
(214, 7, 22, '2025-08-21 04:16:42'),
(215, 7, 23, '2025-08-21 04:16:42'),
(216, 7, 24, '2025-08-21 04:16:42'),
(217, 7, 25, '2025-08-21 04:16:42'),
(218, 7, 26, '2025-08-21 04:16:42'),
(219, 7, 27, '2025-08-21 04:16:42'),
(220, 7, 28, '2025-08-21 04:16:42'),
(221, 7, 29, '2025-08-21 04:16:42'),
(222, 7, 30, '2025-08-21 04:16:42'),
(223, 7, 31, '2025-08-21 04:16:42'),
(224, 7, 32, '2025-08-21 04:16:42'),
(225, 8, 1, '2025-08-21 04:16:42'),
(226, 8, 2, '2025-08-21 04:16:42'),
(227, 8, 3, '2025-08-21 04:16:42'),
(228, 8, 4, '2025-08-21 04:16:42'),
(229, 8, 5, '2025-08-21 04:16:42'),
(230, 8, 6, '2025-08-21 04:16:42'),
(231, 8, 7, '2025-08-21 04:16:42'),
(232, 8, 8, '2025-08-21 04:16:42'),
(233, 8, 9, '2025-08-21 04:16:42'),
(234, 8, 10, '2025-08-21 04:16:42'),
(235, 8, 11, '2025-08-21 04:16:42'),
(236, 8, 12, '2025-08-21 04:16:42'),
(237, 8, 13, '2025-08-21 04:16:42'),
(238, 8, 14, '2025-08-21 04:16:42'),
(239, 8, 15, '2025-08-21 04:16:42'),
(240, 8, 16, '2025-08-21 04:16:42'),
(241, 8, 17, '2025-08-21 04:16:42'),
(242, 8, 18, '2025-08-21 04:16:42'),
(243, 8, 19, '2025-08-21 04:16:42'),
(244, 8, 20, '2025-08-21 04:16:42'),
(245, 8, 21, '2025-08-21 04:16:42'),
(246, 8, 22, '2025-08-21 04:16:42'),
(247, 8, 23, '2025-08-21 04:16:42'),
(248, 8, 24, '2025-08-21 04:16:42'),
(249, 8, 25, '2025-08-21 04:16:42'),
(250, 8, 26, '2025-08-21 04:16:42'),
(251, 8, 27, '2025-08-21 04:16:42'),
(252, 8, 28, '2025-08-21 04:16:42'),
(253, 8, 29, '2025-08-21 04:16:42'),
(254, 8, 30, '2025-08-21 04:16:42'),
(255, 8, 31, '2025-08-21 04:16:42'),
(256, 8, 32, '2025-08-21 04:16:42'),
(257, 9, 1, '2025-08-21 04:16:42'),
(258, 9, 2, '2025-08-21 04:16:42'),
(259, 9, 3, '2025-08-21 04:16:42'),
(260, 9, 4, '2025-08-21 04:16:42'),
(261, 9, 5, '2025-08-21 04:16:42'),
(262, 9, 6, '2025-08-21 04:16:42'),
(263, 9, 7, '2025-08-21 04:16:42'),
(264, 9, 8, '2025-08-21 04:16:42'),
(265, 9, 9, '2025-08-21 04:16:42'),
(266, 9, 10, '2025-08-21 04:16:42'),
(267, 9, 11, '2025-08-21 04:16:42'),
(268, 9, 12, '2025-08-21 04:16:42'),
(269, 9, 13, '2025-08-21 04:16:42'),
(270, 9, 14, '2025-08-21 04:16:42'),
(271, 9, 15, '2025-08-21 04:16:42'),
(272, 9, 16, '2025-08-21 04:16:42'),
(273, 9, 17, '2025-08-21 04:16:42'),
(274, 9, 18, '2025-08-21 04:16:42'),
(275, 9, 19, '2025-08-21 04:16:42'),
(276, 9, 20, '2025-08-21 04:16:42'),
(277, 9, 21, '2025-08-21 04:16:42'),
(278, 9, 22, '2025-08-21 04:16:42'),
(279, 9, 23, '2025-08-21 04:16:42'),
(280, 9, 24, '2025-08-21 04:16:42'),
(281, 9, 25, '2025-08-21 04:16:42'),
(282, 9, 26, '2025-08-21 04:16:42'),
(283, 9, 27, '2025-08-21 04:16:42'),
(284, 9, 28, '2025-08-21 04:16:42'),
(285, 9, 29, '2025-08-21 04:16:42'),
(286, 9, 30, '2025-08-21 04:16:42'),
(287, 9, 31, '2025-08-21 04:16:42'),
(288, 9, 32, '2025-08-21 04:16:42'),
(289, 10, 1, '2025-08-21 04:16:42'),
(290, 10, 2, '2025-08-21 04:16:42'),
(291, 10, 3, '2025-08-21 04:16:42'),
(292, 10, 4, '2025-08-21 04:16:42'),
(293, 10, 5, '2025-08-21 04:16:42'),
(294, 10, 6, '2025-08-21 04:16:42'),
(295, 10, 7, '2025-08-21 04:16:42'),
(296, 10, 8, '2025-08-21 04:16:42'),
(297, 10, 9, '2025-08-21 04:16:42'),
(298, 10, 10, '2025-08-21 04:16:42'),
(299, 10, 11, '2025-08-21 04:16:42'),
(300, 10, 12, '2025-08-21 04:16:42'),
(301, 10, 13, '2025-08-21 04:16:42'),
(302, 10, 14, '2025-08-21 04:16:42'),
(303, 10, 15, '2025-08-21 04:16:42'),
(304, 10, 16, '2025-08-21 04:16:42'),
(305, 10, 17, '2025-08-21 04:16:42'),
(306, 10, 18, '2025-08-21 04:16:42'),
(307, 10, 19, '2025-08-21 04:16:42'),
(308, 10, 20, '2025-08-21 04:16:42'),
(309, 10, 21, '2025-08-21 04:16:42'),
(310, 10, 22, '2025-08-21 04:16:42'),
(311, 10, 23, '2025-08-21 04:16:42'),
(312, 10, 24, '2025-08-21 04:16:42'),
(313, 10, 25, '2025-08-21 04:16:42'),
(314, 10, 26, '2025-08-21 04:16:42'),
(315, 10, 27, '2025-08-21 04:16:42'),
(316, 10, 28, '2025-08-21 04:16:42'),
(317, 10, 29, '2025-08-21 04:16:42'),
(318, 10, 30, '2025-08-21 04:16:42'),
(319, 10, 31, '2025-08-21 04:16:42'),
(320, 10, 32, '2025-08-21 04:16:42'),
(321, 15, 1, '2025-08-21 04:16:42'),
(322, 15, 2, '2025-08-21 04:16:42'),
(323, 15, 3, '2025-08-21 04:16:42'),
(324, 15, 4, '2025-08-21 04:16:42'),
(325, 15, 5, '2025-08-21 04:16:42'),
(326, 15, 6, '2025-08-21 04:16:42'),
(327, 15, 7, '2025-08-21 04:16:42'),
(328, 15, 8, '2025-08-21 04:16:42'),
(329, 15, 9, '2025-08-21 04:16:42'),
(330, 15, 10, '2025-08-21 04:16:42'),
(331, 15, 11, '2025-08-21 04:16:42'),
(332, 15, 12, '2025-08-21 04:16:42'),
(333, 15, 13, '2025-08-21 04:16:42'),
(334, 15, 14, '2025-08-21 04:16:42'),
(335, 15, 15, '2025-08-21 04:16:42'),
(336, 15, 16, '2025-08-21 04:16:42'),
(337, 15, 17, '2025-08-21 04:16:42'),
(338, 15, 18, '2025-08-21 04:16:42'),
(339, 15, 19, '2025-08-21 04:16:42'),
(340, 15, 20, '2025-08-21 04:16:42'),
(341, 15, 21, '2025-08-21 04:16:42'),
(342, 15, 22, '2025-08-21 04:16:42'),
(343, 15, 23, '2025-08-21 04:16:42'),
(344, 15, 24, '2025-08-21 04:16:42'),
(345, 15, 25, '2025-08-21 04:16:42'),
(346, 15, 26, '2025-08-21 04:16:42'),
(347, 15, 27, '2025-08-21 04:16:42'),
(348, 15, 28, '2025-08-21 04:16:42'),
(349, 15, 29, '2025-08-21 04:16:42'),
(350, 15, 30, '2025-08-21 04:16:42'),
(351, 15, 31, '2025-08-21 04:16:42'),
(352, 15, 32, '2025-08-21 04:16:42'),
(353, 16, 1, '2025-08-21 04:16:42'),
(354, 16, 2, '2025-08-21 04:16:42'),
(355, 16, 3, '2025-08-21 04:16:42'),
(356, 16, 4, '2025-08-21 04:16:42'),
(357, 16, 5, '2025-08-21 04:16:42'),
(358, 16, 6, '2025-08-21 04:16:42'),
(359, 16, 7, '2025-08-21 04:16:42'),
(360, 16, 8, '2025-08-21 04:16:42'),
(361, 16, 9, '2025-08-21 04:16:42'),
(362, 16, 10, '2025-08-21 04:16:42'),
(363, 16, 11, '2025-08-21 04:16:42'),
(364, 16, 12, '2025-08-21 04:16:42'),
(365, 16, 13, '2025-08-21 04:16:42'),
(366, 16, 14, '2025-08-21 04:16:42'),
(367, 16, 15, '2025-08-21 04:16:42'),
(368, 16, 16, '2025-08-21 04:16:42'),
(369, 16, 17, '2025-08-21 04:16:42'),
(370, 16, 18, '2025-08-21 04:16:42'),
(371, 16, 19, '2025-08-21 04:16:42'),
(372, 16, 20, '2025-08-21 04:16:42'),
(373, 16, 21, '2025-08-21 04:16:42'),
(374, 16, 22, '2025-08-21 04:16:42'),
(375, 16, 23, '2025-08-21 04:16:42'),
(376, 16, 24, '2025-08-21 04:16:42'),
(377, 16, 25, '2025-08-21 04:16:42'),
(378, 16, 26, '2025-08-21 04:16:42'),
(379, 16, 27, '2025-08-21 04:16:42'),
(380, 16, 28, '2025-08-21 04:16:42'),
(381, 16, 29, '2025-08-21 04:16:42'),
(382, 16, 30, '2025-08-21 04:16:42'),
(383, 16, 31, '2025-08-21 04:16:42'),
(384, 16, 32, '2025-08-21 04:16:42'),
(385, 17, 1, '2025-08-21 04:16:42'),
(386, 17, 2, '2025-08-21 04:16:42'),
(387, 17, 3, '2025-08-21 04:16:42'),
(388, 17, 4, '2025-08-21 04:16:42'),
(389, 17, 5, '2025-08-21 04:16:42'),
(390, 17, 6, '2025-08-21 04:16:42'),
(391, 17, 7, '2025-08-21 04:16:42'),
(392, 17, 8, '2025-08-21 04:16:42'),
(393, 17, 9, '2025-08-21 04:16:42'),
(394, 17, 10, '2025-08-21 04:16:42'),
(395, 17, 11, '2025-08-21 04:16:42'),
(396, 17, 12, '2025-08-21 04:16:42'),
(397, 17, 13, '2025-08-21 04:16:42'),
(398, 17, 14, '2025-08-21 04:16:42'),
(399, 17, 15, '2025-08-21 04:16:42'),
(400, 17, 16, '2025-08-21 04:16:42'),
(401, 17, 17, '2025-08-21 04:16:42'),
(402, 17, 18, '2025-08-21 04:16:42'),
(403, 17, 19, '2025-08-21 04:16:42'),
(404, 17, 20, '2025-08-21 04:16:42'),
(405, 17, 21, '2025-08-21 04:16:42'),
(406, 17, 22, '2025-08-21 04:16:42'),
(407, 17, 23, '2025-08-21 04:16:42'),
(408, 17, 24, '2025-08-21 04:16:42'),
(409, 17, 25, '2025-08-21 04:16:42'),
(410, 17, 26, '2025-08-21 04:16:42'),
(411, 17, 27, '2025-08-21 04:16:42'),
(412, 17, 28, '2025-08-21 04:16:42'),
(413, 17, 29, '2025-08-21 04:16:42'),
(414, 17, 30, '2025-08-21 04:16:42'),
(415, 17, 31, '2025-08-21 04:16:42'),
(416, 17, 32, '2025-08-21 04:16:42'),
(417, 18, 1, '2025-08-21 04:16:42'),
(418, 18, 2, '2025-08-21 04:16:42'),
(419, 18, 3, '2025-08-21 04:16:42'),
(420, 18, 4, '2025-08-21 04:16:42'),
(421, 18, 5, '2025-08-21 04:16:42'),
(422, 18, 6, '2025-08-21 04:16:42'),
(423, 18, 7, '2025-08-21 04:16:42'),
(424, 18, 8, '2025-08-21 04:16:42'),
(425, 18, 9, '2025-08-21 04:16:42'),
(426, 18, 10, '2025-08-21 04:16:42'),
(427, 18, 11, '2025-08-21 04:16:42'),
(428, 18, 12, '2025-08-21 04:16:42'),
(429, 18, 13, '2025-08-21 04:16:42'),
(430, 18, 14, '2025-08-21 04:16:42'),
(431, 18, 15, '2025-08-21 04:16:42'),
(432, 18, 16, '2025-08-21 04:16:42'),
(433, 18, 17, '2025-08-21 04:16:42'),
(434, 18, 18, '2025-08-21 04:16:42'),
(435, 18, 19, '2025-08-21 04:16:42'),
(436, 18, 20, '2025-08-21 04:16:42'),
(437, 18, 21, '2025-08-21 04:16:42'),
(438, 18, 22, '2025-08-21 04:16:42'),
(439, 18, 23, '2025-08-21 04:16:42'),
(440, 18, 24, '2025-08-21 04:16:42'),
(441, 18, 25, '2025-08-21 04:16:42'),
(442, 18, 26, '2025-08-21 04:16:42'),
(443, 18, 27, '2025-08-21 04:16:42'),
(444, 18, 28, '2025-08-21 04:16:42'),
(445, 18, 29, '2025-08-21 04:16:42'),
(446, 18, 30, '2025-08-21 04:16:42'),
(447, 18, 31, '2025-08-21 04:16:42'),
(448, 18, 32, '2025-08-21 04:16:42'),
(449, 19, 1, '2025-08-21 04:16:42'),
(450, 19, 2, '2025-08-21 04:16:42'),
(451, 19, 3, '2025-08-21 04:16:42'),
(452, 19, 4, '2025-08-21 04:16:42'),
(453, 19, 5, '2025-08-21 04:16:42'),
(454, 19, 6, '2025-08-21 04:16:42'),
(455, 19, 7, '2025-08-21 04:16:42'),
(456, 19, 8, '2025-08-21 04:16:42'),
(457, 19, 9, '2025-08-21 04:16:42'),
(458, 19, 10, '2025-08-21 04:16:42'),
(459, 19, 11, '2025-08-21 04:16:42'),
(460, 19, 12, '2025-08-21 04:16:42'),
(461, 19, 13, '2025-08-21 04:16:42'),
(462, 19, 14, '2025-08-21 04:16:42'),
(463, 19, 15, '2025-08-21 04:16:42'),
(464, 19, 16, '2025-08-21 04:16:42'),
(465, 19, 17, '2025-08-21 04:16:42'),
(466, 19, 18, '2025-08-21 04:16:42'),
(467, 19, 19, '2025-08-21 04:16:42'),
(468, 19, 20, '2025-08-21 04:16:42'),
(469, 19, 21, '2025-08-21 04:16:42'),
(470, 19, 22, '2025-08-21 04:16:42'),
(471, 19, 23, '2025-08-21 04:16:42'),
(472, 19, 24, '2025-08-21 04:16:42'),
(473, 19, 25, '2025-08-21 04:16:42'),
(474, 19, 26, '2025-08-21 04:16:42'),
(475, 19, 27, '2025-08-21 04:16:42'),
(476, 19, 28, '2025-08-21 04:16:42'),
(477, 19, 29, '2025-08-21 04:16:42'),
(478, 19, 30, '2025-08-21 04:16:42'),
(479, 19, 31, '2025-08-21 04:16:42'),
(480, 19, 32, '2025-08-21 04:16:42'),
(481, 20, 1, '2025-08-21 04:16:42'),
(482, 20, 2, '2025-08-21 04:16:42'),
(483, 20, 3, '2025-08-21 04:16:42'),
(484, 20, 4, '2025-08-21 04:16:42'),
(485, 20, 5, '2025-08-21 04:16:42'),
(486, 20, 6, '2025-08-21 04:16:42'),
(487, 20, 7, '2025-08-21 04:16:42'),
(488, 20, 8, '2025-08-21 04:16:42'),
(489, 20, 9, '2025-08-21 04:16:42'),
(490, 20, 10, '2025-08-21 04:16:42'),
(491, 20, 11, '2025-08-21 04:16:42'),
(492, 20, 12, '2025-08-21 04:16:42'),
(493, 20, 13, '2025-08-21 04:16:42'),
(494, 20, 14, '2025-08-21 04:16:42'),
(495, 20, 15, '2025-08-21 04:16:42'),
(496, 20, 16, '2025-08-21 04:16:42'),
(497, 20, 17, '2025-08-21 04:16:42'),
(498, 20, 18, '2025-08-21 04:16:42'),
(499, 20, 19, '2025-08-21 04:16:42'),
(500, 20, 20, '2025-08-21 04:16:42'),
(501, 20, 21, '2025-08-21 04:16:42'),
(502, 20, 22, '2025-08-21 04:16:42'),
(503, 20, 23, '2025-08-21 04:16:42'),
(504, 20, 24, '2025-08-21 04:16:42'),
(505, 20, 25, '2025-08-21 04:16:42'),
(506, 20, 26, '2025-08-21 04:16:42'),
(507, 20, 27, '2025-08-21 04:16:42'),
(508, 20, 28, '2025-08-21 04:16:42'),
(509, 20, 29, '2025-08-21 04:16:42'),
(510, 20, 30, '2025-08-21 04:16:42'),
(511, 20, 31, '2025-08-21 04:16:42'),
(512, 20, 32, '2025-08-21 04:16:42'),
(513, 21, 1, '2025-08-21 04:16:42'),
(514, 21, 2, '2025-08-21 04:16:42'),
(515, 21, 3, '2025-08-21 04:16:42'),
(516, 21, 4, '2025-08-21 04:16:42'),
(517, 21, 5, '2025-08-21 04:16:42'),
(518, 21, 6, '2025-08-21 04:16:42'),
(519, 21, 7, '2025-08-21 04:16:42'),
(520, 21, 8, '2025-08-21 04:16:42'),
(521, 21, 9, '2025-08-21 04:16:42'),
(522, 21, 10, '2025-08-21 04:16:42'),
(523, 21, 11, '2025-08-21 04:16:42'),
(524, 21, 12, '2025-08-21 04:16:42'),
(525, 21, 13, '2025-08-21 04:16:42'),
(526, 21, 14, '2025-08-21 04:16:42'),
(527, 21, 15, '2025-08-21 04:16:42'),
(528, 21, 16, '2025-08-21 04:16:42'),
(529, 21, 17, '2025-08-21 04:16:42'),
(530, 21, 18, '2025-08-21 04:16:42'),
(531, 21, 19, '2025-08-21 04:16:42'),
(532, 21, 20, '2025-08-21 04:16:42'),
(533, 21, 21, '2025-08-21 04:16:42'),
(534, 21, 22, '2025-08-21 04:16:42'),
(535, 21, 23, '2025-08-21 04:16:42'),
(536, 21, 24, '2025-08-21 04:16:42'),
(537, 21, 25, '2025-08-21 04:16:42'),
(538, 21, 26, '2025-08-21 04:16:42'),
(539, 21, 27, '2025-08-21 04:16:42'),
(540, 21, 28, '2025-08-21 04:16:42'),
(541, 21, 29, '2025-08-21 04:16:42'),
(542, 21, 30, '2025-08-21 04:16:42'),
(543, 21, 31, '2025-08-21 04:16:42'),
(544, 21, 32, '2025-08-21 04:16:42'),
(545, 22, 1, '2025-08-21 04:16:42'),
(546, 22, 2, '2025-08-21 04:16:42'),
(547, 22, 3, '2025-08-21 04:16:42'),
(548, 22, 4, '2025-08-21 04:16:42'),
(549, 22, 5, '2025-08-21 04:16:42'),
(550, 22, 6, '2025-08-21 04:16:42'),
(551, 22, 7, '2025-08-21 04:16:42'),
(552, 22, 8, '2025-08-21 04:16:42'),
(553, 22, 9, '2025-08-21 04:16:42'),
(554, 22, 10, '2025-08-21 04:16:42'),
(555, 22, 11, '2025-08-21 04:16:42'),
(556, 22, 12, '2025-08-21 04:16:42'),
(557, 22, 13, '2025-08-21 04:16:42'),
(558, 22, 14, '2025-08-21 04:16:42'),
(559, 22, 15, '2025-08-21 04:16:42'),
(560, 22, 16, '2025-08-21 04:16:42'),
(561, 22, 17, '2025-08-21 04:16:42'),
(562, 22, 18, '2025-08-21 04:16:42'),
(563, 22, 19, '2025-08-21 04:16:42'),
(564, 22, 20, '2025-08-21 04:16:42'),
(565, 22, 21, '2025-08-21 04:16:42'),
(566, 22, 22, '2025-08-21 04:16:42'),
(567, 22, 23, '2025-08-21 04:16:42'),
(568, 22, 24, '2025-08-21 04:16:42'),
(569, 22, 25, '2025-08-21 04:16:42'),
(570, 22, 26, '2025-08-21 04:16:42'),
(571, 22, 27, '2025-08-21 04:16:42'),
(572, 22, 28, '2025-08-21 04:16:42'),
(573, 22, 29, '2025-08-21 04:16:42'),
(574, 22, 30, '2025-08-21 04:16:42'),
(575, 22, 31, '2025-08-21 04:16:42'),
(576, 22, 32, '2025-08-21 04:16:42'),
(577, 23, 1, '2025-08-21 04:16:42'),
(578, 23, 2, '2025-08-21 04:16:42'),
(579, 23, 3, '2025-08-21 04:16:42'),
(580, 23, 4, '2025-08-21 04:16:42'),
(581, 23, 5, '2025-08-21 04:16:42'),
(582, 23, 6, '2025-08-21 04:16:42'),
(583, 23, 7, '2025-08-21 04:16:42'),
(584, 23, 8, '2025-08-21 04:16:42'),
(585, 23, 9, '2025-08-21 04:16:42'),
(586, 23, 10, '2025-08-21 04:16:42'),
(587, 23, 11, '2025-08-21 04:16:42'),
(588, 23, 12, '2025-08-21 04:16:42'),
(589, 23, 13, '2025-08-21 04:16:42'),
(590, 23, 14, '2025-08-21 04:16:42'),
(591, 23, 15, '2025-08-21 04:16:42'),
(592, 23, 16, '2025-08-21 04:16:42'),
(593, 23, 17, '2025-08-21 04:16:42'),
(594, 23, 18, '2025-08-21 04:16:42'),
(595, 23, 19, '2025-08-21 04:16:42'),
(596, 23, 20, '2025-08-21 04:16:42'),
(597, 23, 21, '2025-08-21 04:16:42'),
(598, 23, 22, '2025-08-21 04:16:42'),
(599, 23, 23, '2025-08-21 04:16:42'),
(600, 23, 24, '2025-08-21 04:16:42'),
(601, 23, 25, '2025-08-21 04:16:42'),
(602, 23, 26, '2025-08-21 04:16:42'),
(603, 23, 27, '2025-08-21 04:16:42'),
(604, 23, 28, '2025-08-21 04:16:42'),
(605, 23, 29, '2025-08-21 04:16:42'),
(606, 23, 30, '2025-08-21 04:16:42'),
(607, 23, 31, '2025-08-21 04:16:42'),
(608, 23, 32, '2025-08-21 04:16:42'),
(609, 24, 1, '2025-08-21 04:16:42'),
(610, 24, 2, '2025-08-21 04:16:42'),
(611, 24, 3, '2025-08-21 04:16:42'),
(612, 24, 4, '2025-08-21 04:16:42'),
(613, 24, 5, '2025-08-21 04:16:42'),
(614, 24, 6, '2025-08-21 04:16:42'),
(615, 24, 7, '2025-08-21 04:16:42'),
(616, 24, 8, '2025-08-21 04:16:42'),
(617, 24, 9, '2025-08-21 04:16:42'),
(618, 24, 10, '2025-08-21 04:16:42'),
(619, 24, 11, '2025-08-21 04:16:42'),
(620, 24, 12, '2025-08-21 04:16:42'),
(621, 24, 13, '2025-08-21 04:16:42'),
(622, 24, 14, '2025-08-21 04:16:42'),
(623, 24, 15, '2025-08-21 04:16:42'),
(624, 24, 16, '2025-08-21 04:16:42'),
(625, 24, 17, '2025-08-21 04:16:42'),
(626, 24, 18, '2025-08-21 04:16:42'),
(627, 24, 19, '2025-08-21 04:16:42'),
(628, 24, 20, '2025-08-21 04:16:42'),
(629, 24, 21, '2025-08-21 04:16:42'),
(630, 24, 22, '2025-08-21 04:16:42'),
(631, 24, 23, '2025-08-21 04:16:42'),
(632, 24, 24, '2025-08-21 04:16:42'),
(633, 24, 25, '2025-08-21 04:16:42'),
(634, 24, 26, '2025-08-21 04:16:42'),
(635, 24, 27, '2025-08-21 04:16:42'),
(636, 24, 28, '2025-08-21 04:16:42'),
(637, 24, 29, '2025-08-21 04:16:42'),
(638, 24, 30, '2025-08-21 04:16:42'),
(639, 24, 31, '2025-08-21 04:16:42'),
(640, 24, 32, '2025-08-21 04:16:42'),
(1024, 13, 1, '2025-08-21 04:16:42'),
(1025, 25, 1, '2025-08-21 04:16:42'),
(1026, 26, 1, '2025-08-21 04:16:42'),
(1027, 27, 1, '2025-08-21 04:16:42'),
(1028, 28, 1, '2025-08-21 04:16:42'),
(1029, 29, 1, '2025-08-21 04:16:42'),
(1030, 30, 1, '2025-08-21 04:16:42'),
(1031, 31, 1, '2025-08-21 04:16:42'),
(1032, 32, 1, '2025-08-21 04:16:42'),
(1033, 13, 2, '2025-08-21 04:16:42'),
(1034, 25, 2, '2025-08-21 04:16:42'),
(1035, 26, 2, '2025-08-21 04:16:42'),
(1036, 27, 2, '2025-08-21 04:16:42'),
(1037, 28, 2, '2025-08-21 04:16:42'),
(1038, 29, 2, '2025-08-21 04:16:42'),
(1039, 30, 2, '2025-08-21 04:16:42'),
(1040, 31, 2, '2025-08-21 04:16:42'),
(1041, 32, 2, '2025-08-21 04:16:42'),
(1042, 13, 3, '2025-08-21 04:16:42'),
(1043, 25, 3, '2025-08-21 04:16:42'),
(1044, 26, 3, '2025-08-21 04:16:42'),
(1045, 27, 3, '2025-08-21 04:16:42'),
(1046, 28, 3, '2025-08-21 04:16:42'),
(1047, 29, 3, '2025-08-21 04:16:42'),
(1048, 30, 3, '2025-08-21 04:16:42'),
(1049, 31, 3, '2025-08-21 04:16:42'),
(1050, 32, 3, '2025-08-21 04:16:42'),
(1051, 13, 4, '2025-08-21 04:16:42'),
(1052, 25, 4, '2025-08-21 04:16:42'),
(1053, 26, 4, '2025-08-21 04:16:42'),
(1054, 27, 4, '2025-08-21 04:16:42'),
(1055, 28, 4, '2025-08-21 04:16:42'),
(1056, 29, 4, '2025-08-21 04:16:42'),
(1057, 30, 4, '2025-08-21 04:16:42'),
(1058, 31, 4, '2025-08-21 04:16:42'),
(1059, 32, 4, '2025-08-21 04:16:42'),
(1060, 13, 5, '2025-08-21 04:16:42'),
(1061, 25, 5, '2025-08-21 04:16:42'),
(1062, 26, 5, '2025-08-21 04:16:42'),
(1063, 27, 5, '2025-08-21 04:16:42'),
(1064, 28, 5, '2025-08-21 04:16:42'),
(1065, 29, 5, '2025-08-21 04:16:42'),
(1066, 30, 5, '2025-08-21 04:16:42'),
(1067, 31, 5, '2025-08-21 04:16:42'),
(1068, 32, 5, '2025-08-21 04:16:42'),
(1069, 13, 6, '2025-08-21 04:16:42'),
(1070, 25, 6, '2025-08-21 04:16:42'),
(1071, 26, 6, '2025-08-21 04:16:42'),
(1072, 27, 6, '2025-08-21 04:16:42'),
(1073, 28, 6, '2025-08-21 04:16:42'),
(1074, 29, 6, '2025-08-21 04:16:42'),
(1075, 30, 6, '2025-08-21 04:16:42'),
(1076, 31, 6, '2025-08-21 04:16:42'),
(1077, 32, 6, '2025-08-21 04:16:42'),
(1078, 13, 7, '2025-08-21 04:16:42'),
(1079, 25, 7, '2025-08-21 04:16:42'),
(1080, 26, 7, '2025-08-21 04:16:42'),
(1081, 27, 7, '2025-08-21 04:16:42'),
(1082, 28, 7, '2025-08-21 04:16:42'),
(1083, 29, 7, '2025-08-21 04:16:42'),
(1084, 30, 7, '2025-08-21 04:16:42'),
(1085, 31, 7, '2025-08-21 04:16:42'),
(1086, 32, 7, '2025-08-21 04:16:42'),
(1087, 13, 8, '2025-08-21 04:16:42'),
(1088, 25, 8, '2025-08-21 04:16:42'),
(1089, 26, 8, '2025-08-21 04:16:42'),
(1090, 27, 8, '2025-08-21 04:16:42'),
(1091, 28, 8, '2025-08-21 04:16:42'),
(1092, 29, 8, '2025-08-21 04:16:42'),
(1093, 30, 8, '2025-08-21 04:16:42'),
(1094, 31, 8, '2025-08-21 04:16:42'),
(1095, 32, 8, '2025-08-21 04:16:42'),
(1096, 13, 9, '2025-08-21 04:16:42'),
(1097, 25, 9, '2025-08-21 04:16:42'),
(1098, 26, 9, '2025-08-21 04:16:42'),
(1099, 27, 9, '2025-08-21 04:16:42'),
(1100, 28, 9, '2025-08-21 04:16:42'),
(1101, 29, 9, '2025-08-21 04:16:42'),
(1102, 30, 9, '2025-08-21 04:16:42'),
(1103, 31, 9, '2025-08-21 04:16:42'),
(1104, 32, 9, '2025-08-21 04:16:42'),
(1105, 12, 10, '2025-08-21 04:16:42'),
(1106, 33, 10, '2025-08-21 04:16:42'),
(1107, 34, 10, '2025-08-21 04:16:42'),
(1108, 35, 10, '2025-08-21 04:16:42'),
(1109, 36, 10, '2025-08-21 04:16:42'),
(1110, 37, 10, '2025-08-21 04:16:42'),
(1111, 38, 10, '2025-08-21 04:16:42'),
(1112, 39, 10, '2025-08-21 04:16:42'),
(1113, 40, 10, '2025-08-21 04:16:42'),
(1114, 12, 11, '2025-08-21 04:16:42'),
(1115, 33, 11, '2025-08-21 04:16:42'),
(1116, 34, 11, '2025-08-21 04:16:42'),
(1117, 35, 11, '2025-08-21 04:16:42'),
(1118, 36, 11, '2025-08-21 04:16:42'),
(1119, 37, 11, '2025-08-21 04:16:42'),
(1120, 38, 11, '2025-08-21 04:16:42'),
(1121, 39, 11, '2025-08-21 04:16:42'),
(1122, 40, 11, '2025-08-21 04:16:42'),
(1123, 12, 12, '2025-08-21 04:16:42'),
(1124, 33, 12, '2025-08-21 04:16:42'),
(1125, 34, 12, '2025-08-21 04:16:42'),
(1126, 35, 12, '2025-08-21 04:16:42'),
(1127, 36, 12, '2025-08-21 04:16:42'),
(1128, 37, 12, '2025-08-21 04:16:42'),
(1129, 38, 12, '2025-08-21 04:16:42'),
(1130, 39, 12, '2025-08-21 04:16:42'),
(1131, 40, 12, '2025-08-21 04:16:42'),
(1132, 12, 13, '2025-08-21 04:16:42'),
(1133, 33, 13, '2025-08-21 04:16:42'),
(1134, 34, 13, '2025-08-21 04:16:42'),
(1135, 35, 13, '2025-08-21 04:16:42'),
(1136, 36, 13, '2025-08-21 04:16:42'),
(1137, 37, 13, '2025-08-21 04:16:42'),
(1138, 38, 13, '2025-08-21 04:16:42'),
(1139, 39, 13, '2025-08-21 04:16:42'),
(1140, 40, 13, '2025-08-21 04:16:42'),
(1141, 12, 14, '2025-08-21 04:16:42'),
(1142, 33, 14, '2025-08-21 04:16:42'),
(1143, 34, 14, '2025-08-21 04:16:42'),
(1144, 35, 14, '2025-08-21 04:16:42'),
(1145, 36, 14, '2025-08-21 04:16:42'),
(1146, 37, 14, '2025-08-21 04:16:42'),
(1147, 38, 14, '2025-08-21 04:16:42'),
(1148, 39, 14, '2025-08-21 04:16:42'),
(1149, 40, 14, '2025-08-21 04:16:42'),
(1150, 12, 15, '2025-08-21 04:16:42'),
(1151, 33, 15, '2025-08-21 04:16:42'),
(1152, 34, 15, '2025-08-21 04:16:42'),
(1153, 35, 15, '2025-08-21 04:16:42'),
(1154, 36, 15, '2025-08-21 04:16:42'),
(1155, 37, 15, '2025-08-21 04:16:42'),
(1156, 38, 15, '2025-08-21 04:16:42'),
(1157, 39, 15, '2025-08-21 04:16:42'),
(1158, 40, 15, '2025-08-21 04:16:42'),
(1159, 12, 16, '2025-08-21 04:16:42'),
(1160, 33, 16, '2025-08-21 04:16:42'),
(1161, 34, 16, '2025-08-21 04:16:42'),
(1162, 35, 16, '2025-08-21 04:16:42'),
(1163, 36, 16, '2025-08-21 04:16:42'),
(1164, 37, 16, '2025-08-21 04:16:42'),
(1165, 38, 16, '2025-08-21 04:16:42'),
(1166, 39, 16, '2025-08-21 04:16:42'),
(1167, 40, 16, '2025-08-21 04:16:42'),
(1168, 12, 17, '2025-08-21 04:16:42'),
(1169, 33, 17, '2025-08-21 04:16:42'),
(1170, 34, 17, '2025-08-21 04:16:42'),
(1171, 35, 17, '2025-08-21 04:16:42'),
(1172, 36, 17, '2025-08-21 04:16:42'),
(1173, 37, 17, '2025-08-21 04:16:42'),
(1174, 38, 17, '2025-08-21 04:16:42'),
(1175, 39, 17, '2025-08-21 04:16:42'),
(1176, 40, 17, '2025-08-21 04:16:42'),
(1177, 12, 18, '2025-08-21 04:16:42'),
(1178, 33, 18, '2025-08-21 04:16:42'),
(1179, 34, 18, '2025-08-21 04:16:42'),
(1180, 35, 18, '2025-08-21 04:16:42'),
(1181, 36, 18, '2025-08-21 04:16:42'),
(1182, 37, 18, '2025-08-21 04:16:42'),
(1183, 38, 18, '2025-08-21 04:16:42'),
(1184, 39, 18, '2025-08-21 04:16:42'),
(1185, 40, 18, '2025-08-21 04:16:42'),
(1186, 11, 19, '2025-08-21 04:16:42'),
(1187, 41, 19, '2025-08-21 04:16:42'),
(1188, 42, 19, '2025-08-21 04:16:42'),
(1189, 43, 19, '2025-08-21 04:16:42'),
(1190, 44, 19, '2025-08-21 04:16:42'),
(1191, 45, 19, '2025-08-21 04:16:42'),
(1192, 46, 19, '2025-08-21 04:16:42'),
(1193, 47, 19, '2025-08-21 04:16:42'),
(1194, 48, 19, '2025-08-21 04:16:42'),
(1195, 11, 20, '2025-08-21 04:16:42'),
(1196, 41, 20, '2025-08-21 04:16:42'),
(1197, 42, 20, '2025-08-21 04:16:42'),
(1198, 43, 20, '2025-08-21 04:16:42'),
(1199, 44, 20, '2025-08-21 04:16:42'),
(1200, 45, 20, '2025-08-21 04:16:42'),
(1201, 46, 20, '2025-08-21 04:16:42'),
(1202, 47, 20, '2025-08-21 04:16:42'),
(1203, 48, 20, '2025-08-21 04:16:42'),
(1204, 11, 21, '2025-08-21 04:16:42'),
(1205, 41, 21, '2025-08-21 04:16:42'),
(1206, 42, 21, '2025-08-21 04:16:42'),
(1207, 43, 21, '2025-08-21 04:16:42'),
(1208, 44, 21, '2025-08-21 04:16:42'),
(1209, 45, 21, '2025-08-21 04:16:42'),
(1210, 46, 21, '2025-08-21 04:16:42'),
(1211, 47, 21, '2025-08-21 04:16:42'),
(1212, 48, 21, '2025-08-21 04:16:42'),
(1213, 11, 22, '2025-08-21 04:16:42'),
(1214, 41, 22, '2025-08-21 04:16:42'),
(1215, 42, 22, '2025-08-21 04:16:42'),
(1216, 43, 22, '2025-08-21 04:16:42'),
(1217, 44, 22, '2025-08-21 04:16:42'),
(1218, 45, 22, '2025-08-21 04:16:42'),
(1219, 46, 22, '2025-08-21 04:16:42'),
(1220, 47, 22, '2025-08-21 04:16:42'),
(1221, 48, 22, '2025-08-21 04:16:42'),
(1222, 11, 23, '2025-08-21 04:16:42'),
(1223, 41, 23, '2025-08-21 04:16:42'),
(1224, 42, 23, '2025-08-21 04:16:42'),
(1225, 43, 23, '2025-08-21 04:16:42'),
(1226, 44, 23, '2025-08-21 04:16:42'),
(1227, 45, 23, '2025-08-21 04:16:42'),
(1228, 46, 23, '2025-08-21 04:16:42'),
(1229, 47, 23, '2025-08-21 04:16:42'),
(1230, 48, 23, '2025-08-21 04:16:42'),
(1231, 11, 24, '2025-08-21 04:16:42'),
(1232, 41, 24, '2025-08-21 04:16:42'),
(1233, 42, 24, '2025-08-21 04:16:42'),
(1234, 43, 24, '2025-08-21 04:16:42'),
(1235, 44, 24, '2025-08-21 04:16:42'),
(1236, 45, 24, '2025-08-21 04:16:42'),
(1237, 46, 24, '2025-08-21 04:16:42'),
(1238, 47, 24, '2025-08-21 04:16:42'),
(1239, 48, 24, '2025-08-21 04:16:42'),
(1240, 11, 25, '2025-08-21 04:16:42'),
(1241, 41, 25, '2025-08-21 04:16:42'),
(1242, 42, 25, '2025-08-21 04:16:42'),
(1243, 43, 25, '2025-08-21 04:16:42'),
(1244, 44, 25, '2025-08-21 04:16:42'),
(1245, 45, 25, '2025-08-21 04:16:42'),
(1246, 46, 25, '2025-08-21 04:16:42'),
(1247, 47, 25, '2025-08-21 04:16:42'),
(1248, 48, 25, '2025-08-21 04:16:42'),
(1249, 14, 26, '2025-08-21 04:16:42'),
(1250, 49, 26, '2025-08-21 04:16:42'),
(1251, 50, 26, '2025-08-21 04:16:42'),
(1252, 51, 26, '2025-08-21 04:16:42'),
(1253, 52, 26, '2025-08-21 04:16:42'),
(1254, 53, 26, '2025-08-21 04:16:42'),
(1255, 54, 26, '2025-08-21 04:16:42'),
(1256, 55, 26, '2025-08-21 04:16:42'),
(1257, 56, 26, '2025-08-21 04:16:42'),
(1258, 14, 27, '2025-08-21 04:16:42'),
(1259, 49, 27, '2025-08-21 04:16:42'),
(1260, 50, 27, '2025-08-21 04:16:42'),
(1261, 51, 27, '2025-08-21 04:16:42'),
(1262, 52, 27, '2025-08-21 04:16:42'),
(1263, 53, 27, '2025-08-21 04:16:42'),
(1264, 54, 27, '2025-08-21 04:16:42'),
(1265, 55, 27, '2025-08-21 04:16:42'),
(1266, 56, 27, '2025-08-21 04:16:42'),
(1267, 14, 28, '2025-08-21 04:16:42'),
(1268, 49, 28, '2025-08-21 04:16:42'),
(1269, 50, 28, '2025-08-21 04:16:42'),
(1270, 51, 28, '2025-08-21 04:16:42'),
(1271, 52, 28, '2025-08-21 04:16:42'),
(1272, 53, 28, '2025-08-21 04:16:42'),
(1273, 54, 28, '2025-08-21 04:16:42'),
(1274, 55, 28, '2025-08-21 04:16:42'),
(1275, 56, 28, '2025-08-21 04:16:42'),
(1276, 14, 29, '2025-08-21 04:16:42'),
(1277, 49, 29, '2025-08-21 04:16:42'),
(1278, 50, 29, '2025-08-21 04:16:42'),
(1279, 51, 29, '2025-08-21 04:16:42'),
(1280, 52, 29, '2025-08-21 04:16:42'),
(1281, 53, 29, '2025-08-21 04:16:42'),
(1282, 54, 29, '2025-08-21 04:16:42'),
(1283, 55, 29, '2025-08-21 04:16:42'),
(1284, 56, 29, '2025-08-21 04:16:42'),
(1285, 14, 30, '2025-08-21 04:16:42'),
(1286, 49, 30, '2025-08-21 04:16:42'),
(1287, 50, 30, '2025-08-21 04:16:42'),
(1288, 51, 30, '2025-08-21 04:16:42'),
(1289, 52, 30, '2025-08-21 04:16:42'),
(1290, 53, 30, '2025-08-21 04:16:42'),
(1291, 54, 30, '2025-08-21 04:16:42'),
(1292, 55, 30, '2025-08-21 04:16:42'),
(1293, 56, 30, '2025-08-21 04:16:42'),
(1294, 14, 31, '2025-08-21 04:16:42'),
(1295, 49, 31, '2025-08-21 04:16:42'),
(1296, 50, 31, '2025-08-21 04:16:42'),
(1297, 51, 31, '2025-08-21 04:16:42'),
(1298, 52, 31, '2025-08-21 04:16:42'),
(1299, 53, 31, '2025-08-21 04:16:42'),
(1300, 54, 31, '2025-08-21 04:16:42'),
(1301, 55, 31, '2025-08-21 04:16:42'),
(1302, 56, 31, '2025-08-21 04:16:42'),
(1303, 14, 32, '2025-08-21 04:16:42'),
(1304, 49, 32, '2025-08-21 04:16:42'),
(1305, 50, 32, '2025-08-21 04:16:42'),
(1306, 51, 32, '2025-08-21 04:16:42'),
(1307, 52, 32, '2025-08-21 04:16:42'),
(1308, 53, 32, '2025-08-21 04:16:42'),
(1309, 54, 32, '2025-08-21 04:16:42'),
(1310, 55, 32, '2025-08-21 04:16:42'),
(1311, 56, 32, '2025-08-21 04:16:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `cuatrimestre` int(11) NOT NULL CHECK (`cuatrimestre` between 1 and 3),
  `nota` decimal(4,2) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notas`
--

INSERT INTO `notas` (`id`, `estudiante_id`, `materia_id`, `cuatrimestre`, `nota`, `observaciones`, `fecha_registro`, `usuario_id`) VALUES
(1, 1, 1, 1, 8.50, 'Excelente trabajo en álgebra', '2025-08-21 04:16:42', 1),
(2, 1, 2, 1, 9.00, 'Muy buena comprensión lectora', '2025-08-21 04:16:42', 1),
(3, 1, 25, 1, 8.00, 'Buen manejo de instrumentos', '2025-08-21 04:16:42', 1),
(4, 1, 5, 1, 7.50, 'Necesita mejorar en mecánica', '2025-08-21 04:16:42', 1),
(5, 1, 1, 2, 8.00, 'Progreso en geometría', '2025-08-21 04:16:42', 1),
(6, 1, 2, 2, 8.50, 'Mejoró en escritura', '2025-08-21 04:16:42', 1),
(7, 1, 25, 2, 8.50, 'Excelente en perspectivas', '2025-08-21 04:16:42', 1),
(8, 1, 5, 2, 8.00, 'Mejoró significativamente', '2025-08-21 04:16:42', 1),
(9, 2, 1, 1, 7.00, 'Necesita más práctica', '2025-08-21 04:16:42', 1),
(10, 2, 2, 1, 6.50, 'Debe mejorar ortografía', '2025-08-21 04:16:42', 1),
(11, 2, 25, 1, 8.50, 'Muy buen dibujante', '2025-08-21 04:16:42', 1),
(12, 2, 5, 1, 7.50, 'Comprensión aceptable', '2025-08-21 04:16:42', 1),
(13, 2, 1, 2, 7.50, 'Mejoró en álgebra', '2025-08-21 04:16:42', 1),
(14, 2, 2, 2, 7.00, 'Progreso en redacción', '2025-08-21 04:16:42', 1),
(15, 2, 25, 2, 9.00, 'Excelente trabajo', '2025-08-21 04:16:42', 1),
(16, 2, 5, 2, 8.00, 'Buen progreso', '2025-08-21 04:16:42', 1),
(17, 3, 1, 1, 9.50, 'Excelente en todos los temas', '2025-08-21 04:16:42', 1),
(18, 3, 2, 1, 9.00, 'Muy buena expresión', '2025-08-21 04:16:42', 1),
(19, 3, 25, 1, 8.00, 'Buen manejo técnico', '2025-08-21 04:16:42', 1),
(20, 3, 5, 1, 9.00, 'Excelente comprensión', '2025-08-21 04:16:42', 1),
(21, 3, 1, 2, 9.00, 'Mantiene excelente nivel', '2025-08-21 04:16:42', 1),
(22, 3, 2, 2, 9.50, 'Mejoró aún más', '2025-08-21 04:16:42', 1),
(23, 3, 25, 2, 8.50, 'Excelente progreso', '2025-08-21 04:16:42', 1),
(24, 3, 5, 2, 9.50, 'Destacado en laboratorio', '2025-08-21 04:16:42', 1),
(25, 16, 1, 1, 8.00, 'Buen manejo de lógica', '2025-08-21 04:16:42', 1),
(26, 16, 33, 1, 9.50, 'Excelente programador', '2025-08-21 04:16:42', 1),
(27, 16, 2, 1, 7.50, 'Comprensión aceptable', '2025-08-21 04:16:42', 1),
(28, 16, 5, 1, 8.50, 'Muy buen razonamiento', '2025-08-21 04:16:42', 1),
(29, 16, 1, 2, 8.50, 'Mejoró en geometría', '2025-08-21 04:16:42', 1),
(30, 16, 33, 2, 10.00, 'Perfecto en todos los proyectos', '2025-08-21 04:16:42', 1),
(31, 16, 2, 2, 8.00, 'Mejoró en redacción', '2025-08-21 04:16:42', 1),
(32, 16, 5, 2, 9.00, 'Excelente en laboratorio', '2025-08-21 04:16:42', 1),
(33, 17, 1, 1, 7.00, 'Necesita más práctica', '2025-08-21 04:16:42', 1),
(34, 17, 33, 1, 8.00, 'Buen potencial', '2025-08-21 04:16:42', 1),
(35, 17, 2, 1, 8.50, 'Muy buena expresión', '2025-08-21 04:16:42', 1),
(36, 17, 5, 1, 6.50, 'Debe mejorar', '2025-08-21 04:16:42', 1),
(37, 17, 1, 2, 7.50, 'Progreso notable', '2025-08-21 04:16:42', 1),
(38, 17, 33, 2, 8.50, 'Excelente progreso', '2025-08-21 04:16:42', 1),
(39, 17, 2, 2, 9.00, 'Mejoró significativamente', '2025-08-21 04:16:42', 1),
(40, 17, 5, 2, 7.00, 'Mejoró pero necesita más trabajo', '2025-08-21 04:16:42', 1),
(41, 2, 2, 3, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:33:39', 1),
(42, 5, 9, 1, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:38:00', 1),
(43, 5, 9, 2, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:38:31', 1),
(44, 5, 9, 3, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:38:31', 1),
(45, 5, 7, 1, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:41:20', 1),
(46, 5, 7, 2, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:41:38', 1),
(47, 5, 7, 3, 7.00, 'Aprobada desde materia previa', '2025-08-21 04:41:38', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `telefono_fijo` varchar(20) DEFAULT NULL,
  `telefono_celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `especialidad` varchar(200) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id`, `dni`, `apellido`, `nombre`, `fecha_nacimiento`, `domicilio`, `telefono_fijo`, `telefono_celular`, `email`, `titulo`, `especialidad`, `fecha_ingreso`, `activo`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, '12345678', 'González', 'María Elena', '1980-05-15', 'Av. San Martín 1234, CABA', '011-4567-8901', '15-1234-5678', 'maria.gonzalez@email.com', 'Profesora de Matemática', 'Matemática', '2015-03-01', 1, '2025-08-23 23:20:59', '2025-08-23 23:20:59'),
(2, '23456789', 'Rodríguez', 'Carlos Alberto', '1975-08-22', 'Belgrano 567, CABA', '011-4567-8902', '15-1234-5679', 'carlos.rodriguez@email.com', 'Profesor de Física', 'Física', '2010-02-15', 1, '2025-08-23 23:20:59', '2025-08-23 23:20:59'),
(3, '34567890', 'López', 'Ana María', '1982-12-10', 'Corrientes 890, CABA', '011-4567-8903', '15-1234-5680', 'ana.lopez@email.com', 'Profesora de Lengua', 'Lengua y Literatura', '2018-04-01', 1, '2025-08-23 23:20:59', '2025-08-23 23:20:59'),
(4, '45678901', 'Martínez', 'Roberto José', '1978-03-25', 'Rivadavia 234, CABA', '011-4567-8904', '15-1234-5681', 'roberto.martinez@email.com', 'Profesor de Historia', 'Historia', '2012-08-15', 1, '2025-08-23 23:20:59', '2025-08-23 23:20:59'),
(5, '56789012', 'Fernández', 'Lucía Beatriz', '1985-07-08', 'Córdoba 456, CABA', '011-4567-8905', '15-1234-5682', 'lucia.fernandez@email.com', 'Profesora de Inglés', 'Inglés', '2019-03-01', 1, '2025-08-23 23:20:59', '2025-08-23 23:20:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_curso`
--

CREATE TABLE `profesor_curso` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `anio_academico` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesor_curso`
--

INSERT INTO `profesor_curso` (`id`, `profesor_id`, `curso_id`, `anio_academico`, `fecha_asignacion`, `activo`) VALUES
(2, 2, 2, 2025, '2025-08-23 23:20:59', 1),
(3, 3, 3, 2025, '2025-08-23 23:20:59', 1),
(4, 4, 4, 2025, '2025-08-23 23:20:59', 1),
(5, 5, 5, 2025, '2025-08-23 23:20:59', 1),
(6, 5, 12, 2025, '2025-08-24 00:59:00', 1),
(9, 5, 1, 2025, '2025-08-24 01:30:12', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_materia`
--

CREATE TABLE `profesor_materia` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `anio_academico` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `profesor_materia`
--

INSERT INTO `profesor_materia` (`id`, `profesor_id`, `materia_id`, `anio_academico`, `fecha_asignacion`, `activo`) VALUES
(1, 1, 1, 2025, '2025-08-23 23:20:59', 1),
(2, 2, 2, 2025, '2025-08-23 23:20:59', 1),
(3, 3, 3, 2025, '2025-08-23 23:20:59', 1),
(4, 4, 4, 2025, '2025-08-23 23:20:59', 1),
(5, 5, 5, 2025, '2025-08-23 23:20:59', 1),
(6, 1, 3, 0, '2025-08-23 03:00:00', 0),
(8, 2, 1, 2025, '2025-08-23 03:00:00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `responsables`
--

CREATE TABLE `responsables` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `parentesco` varchar(50) DEFAULT NULL,
  `telefono_fijo` varchar(20) DEFAULT NULL,
  `telefono_celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `domicilio` text DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `es_contacto_emergencia` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `responsables`
--

INSERT INTO `responsables` (`id`, `estudiante_id`, `apellido`, `nombre`, `dni`, `parentesco`, `telefono_fijo`, `telefono_celular`, `email`, `domicilio`, `ocupacion`, `es_contacto_emergencia`) VALUES
(1, 1, 'García', 'Roberto', '20123456', 'Padre', '011-4567-8901', '15-1234-5678', 'roberto.garcia@email.com', 'Av. San Martín 1234', 'Ingeniero', 1),
(2, 1, 'García', 'María Elena', '25123456', 'Madre', '011-4567-8901', '15-1234-5679', 'maria.garcia@email.com', 'Av. San Martín 1234', 'Docente', 0),
(3, 2, 'López', 'Carlos Alberto', '20123457', 'Padre', '011-4567-8902', '15-1234-5680', 'carlos.lopez@email.com', 'Belgrano 567', 'Médico', 1),
(4, 3, 'Martínez', 'Ana María', '25123458', 'Madre', '011-4567-8903', '15-1234-5681', 'ana.martinez@email.com', 'Rivadavia 890', 'Abogada', 1),
(5, 4, 'Rodríguez', 'Miguel', '20123459', 'Padre', '011-4567-8904', '15-1234-5682', 'miguel.rodriguez@email.com', 'Mitre 234', 'Contador', 1),
(6, 5, 'Fernández', 'Lucía Beatriz', '25123460', 'Madre', '011-4567-8905', '15-1234-5683', 'lucia.fernandez@email.com', 'Sarmiento 456', 'Psicóloga', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suplencias`
--

CREATE TABLE `suplencias` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `suplente_id` int(11) DEFAULT NULL,
  `materia_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `estado` enum('activa','finalizada','cancelada') DEFAULT 'activa',
  `fuera_servicio` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suplencias`
--

INSERT INTO `suplencias` (`id`, `profesor_id`, `suplente_id`, `materia_id`, `fecha_inicio`, `fecha_fin`, `motivo`, `estado`, `fuera_servicio`, `fecha_creacion`, `usuario_id`) VALUES
(1, 1, 1, 1, '2025-01-15', '2025-08-23', 'Licencia m??dica', 'finalizada', 0, '2025-08-24 00:15:52', 1),
(2, 2, 2, 2, '2025-01-10', '2025-01-25', 'Capacitaci??n', 'activa', 0, '2025-08-24 00:15:52', 1),
(3, 3, NULL, 3, '2025-01-12', '2025-01-18', 'Ausencia personal', 'activa', 0, '2025-08-24 00:15:52', 1),
(5, 5, NULL, 13, '2025-08-24', '2025-08-23', 'w', 'finalizada', 1, '2025-08-24 00:33:14', 2),
(6, 1, 11, 1, '2025-08-24', NULL, 'cd', 'activa', 0, '2025-08-24 00:49:10', 2),
(7, 5, NULL, 5, '2025-08-24', NULL, 'w', 'activa', 1, '2025-08-24 02:00:11', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suplentes`
--

CREATE TABLE `suplentes` (
  `id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono_celular` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `especialidad` varchar(200) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `suplentes`
--

INSERT INTO `suplentes` (`id`, `dni`, `apellido`, `nombre`, `telefono_celular`, `email`, `especialidad`, `activo`, `fecha_creacion`, `fecha_modificacion`) VALUES
(1, '87654321', 'Pérez', 'Juan Carlos', '15-9876-5432', 'juan.perez@email.com', 'Matemática', 1, '2025-08-23 23:51:09', '2025-08-24 00:51:55'),
(2, '76543210', 'García', 'María In??s', '15-9876-5433', 'maria.garcia@email.com', 'Lengua y Literatura', 1, '2025-08-23 23:51:09', '2025-08-24 00:51:55'),
(3, '65432109', 'López', 'Roberto Daniel', '15-9876-5434', 'roberto.lopez@email.com', 'Física', 1, '2025-08-23 23:51:09', '2025-08-24 00:51:55'),
(4, '54321098', 'Martínez', 'Ana Sofía', '15-9876-5435', 'ana.martinez@email.com', 'Historia', 1, '2025-08-23 23:51:09', '2025-08-24 00:51:55'),
(5, '43210987', 'Fernández', 'Carlos Eduardo', '15-9876-5436', 'carlos.fernandez@email.com', 'Inglés', 1, '2025-08-23 23:51:09', '2025-08-24 00:51:55'),
(11, '32', 'Martínez', 'Alan Ezequiel', '223-15-678901', 'lucas.acosta@email.com', 'Matemática', 1, '2025-08-24 00:48:39', '2025-08-24 00:51:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suplente_horario`
--

CREATE TABLE `suplente_horario` (
  `id` int(11) NOT NULL,
  `suplente_id` int(11) NOT NULL,
  `horario_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `talleres`
--

CREATE TABLE `talleres` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especialidad_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `talleres`
--

INSERT INTO `talleres` (`id`, `nombre`, `especialidad_id`, `descripcion`, `activo`) VALUES
(1, 'Taller de Mecánica Industrial', 1, 'Taller práctico de mecánica industrial', 1),
(2, 'Taller de Instalaciones Eléctricas', 1, 'Taller de instalaciones eléctricas residenciales e industriales', 1),
(3, 'Taller de Automatización', 1, 'Taller de automatización y control industrial', 1),
(4, 'Taller de Programación', 2, 'Taller de programación básica y avanzada', 1),
(5, 'Taller de Redes y Conectividad', 2, 'Taller de redes de computadoras y conectividad', 1),
(6, 'Taller de Desarrollo Web', 2, 'Taller de desarrollo web y aplicaciones', 1),
(7, 'Taller de Laboratorio Químico', 3, 'Taller de laboratorio químico y análisis', 1),
(8, 'Taller de Control de Calidad', 3, 'Taller de control de calidad y procesos químicos', 1),
(9, 'Taller de Microbiología', 3, 'Taller de microbiología y biotecnología', 1),
(10, 'Taller de Construcción Civil', 4, 'Taller de construcción y estructuras civiles', 1),
(11, 'Taller de Topografía', 4, 'Taller de topografía y mediciones', 1),
(12, 'Taller de Instalaciones Sanitarias', 4, 'Taller de instalaciones sanitarias y plomería', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `taller_curso`
--

CREATE TABLE `taller_curso` (
  `id` int(11) NOT NULL,
  `taller_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `taller_curso`
--

INSERT INTO `taller_curso` (`id`, `taller_id`, `curso_id`, `fecha_asignacion`) VALUES
(1, 1, 1, '2025-08-21 04:16:42'),
(2, 2, 1, '2025-08-21 04:16:42'),
(3, 3, 1, '2025-08-21 04:16:42'),
(4, 1, 2, '2025-08-21 04:16:42'),
(5, 2, 2, '2025-08-21 04:16:42'),
(6, 3, 2, '2025-08-21 04:16:42'),
(7, 1, 3, '2025-08-21 04:16:42'),
(8, 2, 3, '2025-08-21 04:16:42'),
(9, 3, 3, '2025-08-21 04:16:42'),
(10, 1, 4, '2025-08-21 04:16:42'),
(11, 2, 4, '2025-08-21 04:16:42'),
(12, 3, 4, '2025-08-21 04:16:42'),
(13, 1, 5, '2025-08-21 04:16:42'),
(14, 2, 5, '2025-08-21 04:16:42'),
(15, 3, 5, '2025-08-21 04:16:42'),
(16, 1, 6, '2025-08-21 04:16:42'),
(17, 2, 6, '2025-08-21 04:16:42'),
(18, 3, 6, '2025-08-21 04:16:42'),
(19, 1, 7, '2025-08-21 04:16:42'),
(20, 2, 7, '2025-08-21 04:16:42'),
(21, 3, 7, '2025-08-21 04:16:42'),
(22, 1, 8, '2025-08-21 04:16:42'),
(23, 2, 8, '2025-08-21 04:16:42'),
(24, 3, 8, '2025-08-21 04:16:42'),
(25, 1, 9, '2025-08-21 04:16:42'),
(26, 2, 9, '2025-08-21 04:16:42'),
(27, 3, 9, '2025-08-21 04:16:42'),
(32, 4, 10, '2025-08-21 04:16:42'),
(33, 5, 10, '2025-08-21 04:16:42'),
(34, 6, 10, '2025-08-21 04:16:42'),
(35, 4, 11, '2025-08-21 04:16:42'),
(36, 5, 11, '2025-08-21 04:16:42'),
(37, 6, 11, '2025-08-21 04:16:42'),
(38, 4, 12, '2025-08-21 04:16:42'),
(39, 5, 12, '2025-08-21 04:16:42'),
(40, 6, 12, '2025-08-21 04:16:42'),
(41, 4, 13, '2025-08-21 04:16:42'),
(42, 5, 13, '2025-08-21 04:16:42'),
(43, 6, 13, '2025-08-21 04:16:42'),
(44, 4, 14, '2025-08-21 04:16:42'),
(45, 5, 14, '2025-08-21 04:16:42'),
(46, 6, 14, '2025-08-21 04:16:42'),
(47, 4, 15, '2025-08-21 04:16:42'),
(48, 5, 15, '2025-08-21 04:16:42'),
(49, 6, 15, '2025-08-21 04:16:42'),
(50, 4, 16, '2025-08-21 04:16:42'),
(51, 5, 16, '2025-08-21 04:16:42'),
(52, 6, 16, '2025-08-21 04:16:42'),
(53, 4, 17, '2025-08-21 04:16:42'),
(54, 5, 17, '2025-08-21 04:16:42'),
(55, 6, 17, '2025-08-21 04:16:42'),
(56, 4, 18, '2025-08-21 04:16:42'),
(57, 5, 18, '2025-08-21 04:16:42'),
(58, 6, 18, '2025-08-21 04:16:42'),
(63, 7, 19, '2025-08-21 04:16:42'),
(64, 8, 19, '2025-08-21 04:16:42'),
(65, 9, 19, '2025-08-21 04:16:42'),
(66, 7, 20, '2025-08-21 04:16:42'),
(67, 8, 20, '2025-08-21 04:16:42'),
(68, 9, 20, '2025-08-21 04:16:42'),
(69, 7, 21, '2025-08-21 04:16:42'),
(70, 8, 21, '2025-08-21 04:16:42'),
(71, 9, 21, '2025-08-21 04:16:42'),
(72, 7, 22, '2025-08-21 04:16:42'),
(73, 8, 22, '2025-08-21 04:16:42'),
(74, 9, 22, '2025-08-21 04:16:42'),
(75, 7, 23, '2025-08-21 04:16:42'),
(76, 8, 23, '2025-08-21 04:16:42'),
(77, 9, 23, '2025-08-21 04:16:42'),
(78, 7, 24, '2025-08-21 04:16:42'),
(79, 8, 24, '2025-08-21 04:16:42'),
(80, 9, 24, '2025-08-21 04:16:42'),
(81, 7, 25, '2025-08-21 04:16:42'),
(82, 8, 25, '2025-08-21 04:16:42'),
(83, 9, 25, '2025-08-21 04:16:42'),
(94, 10, 26, '2025-08-21 04:16:42'),
(95, 11, 26, '2025-08-21 04:16:42'),
(96, 12, 26, '2025-08-21 04:16:42'),
(97, 10, 27, '2025-08-21 04:16:42'),
(98, 11, 27, '2025-08-21 04:16:42'),
(99, 12, 27, '2025-08-21 04:16:42'),
(100, 10, 28, '2025-08-21 04:16:42'),
(101, 11, 28, '2025-08-21 04:16:42'),
(102, 12, 28, '2025-08-21 04:16:42'),
(103, 10, 29, '2025-08-21 04:16:42'),
(104, 11, 29, '2025-08-21 04:16:42'),
(105, 12, 29, '2025-08-21 04:16:42'),
(106, 10, 30, '2025-08-21 04:16:42'),
(107, 11, 30, '2025-08-21 04:16:42'),
(108, 12, 30, '2025-08-21 04:16:42'),
(109, 10, 31, '2025-08-21 04:16:42'),
(110, 11, 31, '2025-08-21 04:16:42'),
(111, 12, 31, '2025-08-21 04:16:42'),
(112, 10, 32, '2025-08-21 04:16:42'),
(113, 11, 32, '2025-08-21 04:16:42'),
(114, 12, 32, '2025-08-21 04:16:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `nombre`, `hora_inicio`, `hora_fin`) VALUES
(1, 'Mañana', '07:30:00', '12:30:00'),
(2, 'Tarde', '13:30:00', '18:30:00'),
(3, 'Contraturno', '18:30:00', '22:30:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rol` enum('admin','directivo','preceptor','secretaria') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `apellido`, `email`, `rol`, `activo`, `fecha_creacion`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', 'admin@eest2.edu.ar', 'admin', 1, '2025-08-21 04:16:23'),
(2, 'director', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Carlos', 'González', 'director@eest2.edu.ar', 'directivo', 1, '2025-08-21 04:16:41'),
(3, 'preceptor1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María Elena', 'Rodríguez', 'preceptor1@eest2.edu.ar', 'preceptor', 1, '2025-08-21 04:16:41'),
(4, 'preceptor2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Alberto', 'López', 'preceptor2@eest2.edu.ar', 'preceptor', 1, '2025-08-21 04:16:41'),
(5, 'secretaria1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana María', 'Fernández', 'secretaria@eest2.edu.ar', 'secretaria', 1, '2025-08-21 04:16:41'),
(7, 'preceptor3', '$2y$10$v2/XT4IxPr/CD2N1u97Bt.Vb/pGapT6dNkCWd7fWT96IAE3/WrD1W', 'as', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:10:08'),
(8, 'preceptor4', '$2y$10$aN0pw3MYKw69G3oY/F7cwunvBksqxoSv9btZ2GxdY1emSxyf84Mey', 'a', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:12:23'),
(9, 'preceptor5', '$2y$10$OoJHhUYsujC3JClo0wxlYOs23/CPjHCiFgyPyihuiJMeZ6hAA66yC', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:17:12'),
(10, 'preceptor6', '$2y$10$b2Y4KQSCLjk4RDq.UG.sc.fewVoroUkrbo0mEJDnhhwBRcCgIvow.', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:18:16'),
(11, 'preceptor7', '$2y$10$bJw5AJqzMLiDKvsrpXG94ueBVH877SlCFwEiD.3Oj9ezzeL5lYsQS', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:19:41'),
(12, 'preceptor8', '$2y$10$IktxBL9Sn6EaKX8vwHt8EeqD7wam7fL68hiWs4oOSeI56XzH07F3K', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:19:46'),
(14, 'preceptor9', '$2y$10$Z.9AbEi5BW735/dexCNnbO/E8NZCq0aorg29fTlRgjzOTIT5b.sOe', 'a', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:21:43'),
(15, 'preceptor10', '$2y$10$hJt1AGepp5P0XpzjOBuEzO.4Ovsu01MMFB80cJucpp2IySC2JSv6q', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 0, '2025-08-24 03:24:04'),
(16, 'preceptor11', '$2y$10$zLtIUtThUfKYRZ23HGh5s.3VwwHb3LP2xDwIcdE/Q3gWEzKRY0kia', 'Alan Ezequiel', 'Martínez', 'lucas.acosta@email.com', 'preceptor', 1, '2025-08-24 03:27:27');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estudiantes_completa`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_estudiantes_completa` (
`id` int(11)
,`dni` varchar(20)
,`apellido` varchar(100)
,`nombre` varchar(100)
,`fecha_nacimiento` date
,`edad` int(10)
,`grupo_sanguineo` varchar(10)
,`obra_social` varchar(100)
,`domicilio` text
,`telefono_celular` varchar(20)
,`email` varchar(100)
,`anio` int(11)
,`division` varchar(5)
,`especialidad` varchar(100)
,`turno` varchar(20)
,`grado` enum('inferior','superior')
,`activo` tinyint(1)
,`fecha_ingreso` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_inasistencias_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_inasistencias_resumen` (
`estudiante_id` int(11)
,`apellido` varchar(100)
,`nombre` varchar(100)
,`dni` varchar(20)
,`anio` int(11)
,`division` varchar(5)
,`especialidad` varchar(100)
,`total_inasistencias` bigint(21)
,`justificadas` decimal(22,0)
,`no_justificadas` decimal(22,0)
,`faltas_completas` decimal(22,0)
,`llegadas_tarde` decimal(22,0)
,`retiros_anticipados` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_llamados_resumen`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_llamados_resumen` (
`estudiante_id` int(11)
,`apellido` varchar(100)
,`nombre` varchar(100)
,`dni` varchar(20)
,`anio` int(11)
,`division` varchar(5)
,`especialidad` varchar(100)
,`total_llamados` bigint(21)
,`con_sancion` decimal(22,0)
,`sin_sancion` decimal(22,0)
,`ultimo_llamado` date
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estudiantes_completa`
--
DROP TABLE IF EXISTS `vista_estudiantes_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estudiantes_completa`  AS SELECT `e`.`id` AS `id`, `e`.`dni` AS `dni`, `e`.`apellido` AS `apellido`, `e`.`nombre` AS `nombre`, `e`.`fecha_nacimiento` AS `fecha_nacimiento`, floor((to_days(curdate()) - to_days(`e`.`fecha_nacimiento`)) / 365.25) AS `edad`, `e`.`grupo_sanguineo` AS `grupo_sanguineo`, `e`.`obra_social` AS `obra_social`, `e`.`domicilio` AS `domicilio`, `e`.`telefono_celular` AS `telefono_celular`, `e`.`email` AS `email`, `c`.`anio` AS `anio`, `c`.`division` AS `division`, `esp`.`nombre` AS `especialidad`, `t`.`nombre` AS `turno`, `c`.`grado` AS `grado`, `e`.`activo` AS `activo`, `e`.`fecha_ingreso` AS `fecha_ingreso` FROM (((`estudiantes` `e` left join `cursos` `c` on(`e`.`curso_id` = `c`.`id`)) left join `especialidades` `esp` on(`c`.`especialidad_id` = `esp`.`id`)) left join `turnos` `t` on(`c`.`turno_id` = `t`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_inasistencias_resumen`
--
DROP TABLE IF EXISTS `vista_inasistencias_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_inasistencias_resumen`  AS SELECT `e`.`id` AS `estudiante_id`, `e`.`apellido` AS `apellido`, `e`.`nombre` AS `nombre`, `e`.`dni` AS `dni`, `c`.`anio` AS `anio`, `c`.`division` AS `division`, `esp`.`nombre` AS `especialidad`, count(`i`.`id`) AS `total_inasistencias`, sum(case when `i`.`justificada` = 1 then 1 else 0 end) AS `justificadas`, sum(case when `i`.`justificada` = 0 then 1 else 0 end) AS `no_justificadas`, sum(case when `i`.`tipo` = 'completa' then 1 else 0 end) AS `faltas_completas`, sum(case when `i`.`tipo` = 'tarde' then 1 else 0 end) AS `llegadas_tarde`, sum(case when `i`.`tipo` = 'retiro_anticipado' then 1 else 0 end) AS `retiros_anticipados` FROM (((`estudiantes` `e` left join `cursos` `c` on(`e`.`curso_id` = `c`.`id`)) left join `especialidades` `esp` on(`c`.`especialidad_id` = `esp`.`id`)) left join `inasistencias` `i` on(`e`.`id` = `i`.`estudiante_id`)) WHERE `e`.`activo` = 1 GROUP BY `e`.`id`, `e`.`apellido`, `e`.`nombre`, `e`.`dni`, `c`.`anio`, `c`.`division`, `esp`.`nombre` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_llamados_resumen`
--
DROP TABLE IF EXISTS `vista_llamados_resumen`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_llamados_resumen`  AS SELECT `e`.`id` AS `estudiante_id`, `e`.`apellido` AS `apellido`, `e`.`nombre` AS `nombre`, `e`.`dni` AS `dni`, `c`.`anio` AS `anio`, `c`.`division` AS `division`, `esp`.`nombre` AS `especialidad`, count(`l`.`id`) AS `total_llamados`, sum(case when `l`.`sancion` is not null and `l`.`sancion` <> '' then 1 else 0 end) AS `con_sancion`, sum(case when `l`.`sancion` is null or `l`.`sancion` = '' then 1 else 0 end) AS `sin_sancion`, max(`l`.`fecha`) AS `ultimo_llamado` FROM (((`estudiantes` `e` left join `cursos` `c` on(`e`.`curso_id` = `c`.`id`)) left join `especialidades` `esp` on(`c`.`especialidad_id` = `esp`.`id`)) left join `llamados_atencion` `l` on(`e`.`id` = `l`.`estudiante_id`)) WHERE `e`.`activo` = 1 GROUP BY `e`.`id`, `e`.`apellido`, `e`.`nombre`, `e`.`dni`, `c`.`anio`, `c`.`division`, `esp`.`nombre` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_adjuntos`
--
ALTER TABLE `archivos_adjuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_fecha` (`fecha_subida`);

--
-- Indices de la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_curso` (`anio`,`division`,`turno_id`,`especialidad_id`),
  ADD KEY `taller_id` (`taller_id`),
  ADD KEY `idx_turno` (`turno_id`),
  ADD KEY `idx_especialidad` (`especialidad_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_cursos_especialidad_turno` (`especialidad_id`,`turno_id`);

--
-- Indices de la tabla `equipo_directivo`
--
ALTER TABLE `equipo_directivo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_cargo` (`cargo`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activa` (`activa`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_apellido` (`apellido`),
  ADD KEY `idx_curso` (`curso_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_estudiantes_apellido_nombre` (`apellido`,`nombre`);

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_publico` (`publico`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curso` (`curso_id`),
  ADD KEY `idx_materia` (`materia_id`),
  ADD KEY `idx_dia_semana` (`dia_semana`),
  ADD KEY `idx_contraturno` (`es_contraturno`);

--
-- Indices de la tabla `inasistencias`
--
ALTER TABLE `inasistencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_justificada` (`justificada`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_inasistencias_fecha_estudiante` (`fecha`,`estudiante_id`);

--
-- Indices de la tabla `llamados_atencion`
--
ALTER TABLE `llamados_atencion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_llamados_fecha_estudiante` (`fecha`,`estudiante_id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_especialidad` (`especialidad_id`),
  ADD KEY `idx_es_taller` (`es_taller`);

--
-- Indices de la tabla `materias_previas`
--
ALTER TABLE `materias_previas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_materia` (`materia_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `materia_curso`
--
ALTER TABLE `materia_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_materia_curso` (`materia_id`,`curso_id`),
  ADD KEY `idx_materia` (`materia_id`),
  ADD KEY `idx_curso` (`curso_id`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nota_cuatrimestre` (`estudiante_id`,`materia_id`,`cuatrimestre`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_materia` (`materia_id`),
  ADD KEY `idx_cuatrimestre` (`cuatrimestre`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_apellido_nombre` (`apellido`,`nombre`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `profesor_curso`
--
ALTER TABLE `profesor_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_profesor_curso_anio` (`profesor_id`,`curso_id`,`anio_academico`),
  ADD KEY `idx_profesor_id` (`profesor_id`),
  ADD KEY `idx_curso_id` (`curso_id`),
  ADD KEY `idx_anio_academico` (`anio_academico`);

--
-- Indices de la tabla `profesor_materia`
--
ALTER TABLE `profesor_materia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_profesor_materia_anio` (`profesor_id`,`materia_id`,`anio_academico`),
  ADD KEY `idx_profesor_id` (`profesor_id`),
  ADD KEY `idx_materia_id` (`materia_id`),
  ADD KEY `idx_anio_academico` (`anio_academico`);

--
-- Indices de la tabla `responsables`
--
ALTER TABLE `responsables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estudiante` (`estudiante_id`),
  ADD KEY `idx_contacto_emergencia` (`es_contacto_emergencia`);

--
-- Indices de la tabla `suplencias`
--
ALTER TABLE `suplencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_profesor_id` (`profesor_id`),
  ADD KEY `idx_suplente_id` (`suplente_id`),
  ADD KEY `idx_materia_id` (`materia_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`);

--
-- Indices de la tabla `suplentes`
--
ALTER TABLE `suplentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `idx_dni` (`dni`),
  ADD KEY `idx_apellido_nombre` (`apellido`,`nombre`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `suplente_horario`
--
ALTER TABLE `suplente_horario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suplente_id` (`suplente_id`),
  ADD KEY `idx_horario_id` (`horario_id`),
  ADD KEY `idx_fecha_inicio` (`fecha_inicio`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `talleres`
--
ALTER TABLE `talleres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_especialidad` (`especialidad_id`);

--
-- Indices de la tabla `taller_curso`
--
ALTER TABLE `taller_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_taller_curso` (`taller_id`,`curso_id`),
  ADD KEY `idx_taller` (`taller_id`),
  ADD KEY `idx_curso` (`curso_id`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_adjuntos`
--
ALTER TABLE `archivos_adjuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `equipo_directivo`
--
ALTER TABLE `equipo_directivo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `inasistencias`
--
ALTER TABLE `inasistencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `llamados_atencion`
--
ALTER TABLE `llamados_atencion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `materias_previas`
--
ALTER TABLE `materias_previas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materia_curso`
--
ALTER TABLE `materia_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1535;

--
-- AUTO_INCREMENT de la tabla `notas`
--
ALTER TABLE `notas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `profesor_curso`
--
ALTER TABLE `profesor_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `profesor_materia`
--
ALTER TABLE `profesor_materia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `responsables`
--
ALTER TABLE `responsables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `suplencias`
--
ALTER TABLE `suplencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `suplentes`
--
ALTER TABLE `suplentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `suplente_horario`
--
ALTER TABLE `suplente_horario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `talleres`
--
ALTER TABLE `talleres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `taller_curso`
--
ALTER TABLE `taller_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_adjuntos`
--
ALTER TABLE `archivos_adjuntos`
  ADD CONSTRAINT `archivos_adjuntos_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `archivos_adjuntos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `contactos_emergencia`
--
ALTER TABLE `contactos_emergencia`
  ADD CONSTRAINT `contactos_emergencia_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`),
  ADD CONSTRAINT `cursos_ibfk_2` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`),
  ADD CONSTRAINT `cursos_ibfk_3` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `equipo_directivo`
--
ALTER TABLE `equipo_directivo`
  ADD CONSTRAINT `equipo_directivo_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD CONSTRAINT `eventos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `horarios_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inasistencias`
--
ALTER TABLE `inasistencias`
  ADD CONSTRAINT `inasistencias_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inasistencias_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `llamados_atencion`
--
ALTER TABLE `llamados_atencion`
  ADD CONSTRAINT `llamados_atencion_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `llamados_atencion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `materias`
--
ALTER TABLE `materias`
  ADD CONSTRAINT `materias_ibfk_1` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `materias_previas`
--
ALTER TABLE `materias_previas`
  ADD CONSTRAINT `materias_previas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materias_previas_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `materia_curso`
--
ALTER TABLE `materia_curso`
  ADD CONSTRAINT `materia_curso_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `materia_curso_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `profesor_curso`
--
ALTER TABLE `profesor_curso`
  ADD CONSTRAINT `profesor_curso_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profesor_curso_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesor_materia`
--
ALTER TABLE `profesor_materia`
  ADD CONSTRAINT `profesor_materia_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `profesor_materia_ibfk_2` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `responsables`
--
ALTER TABLE `responsables`
  ADD CONSTRAINT `responsables_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `suplencias`
--
ALTER TABLE `suplencias`
  ADD CONSTRAINT `suplencias_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suplencias_ibfk_2` FOREIGN KEY (`suplente_id`) REFERENCES `suplentes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `suplencias_ibfk_3` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `suplente_horario`
--
ALTER TABLE `suplente_horario`
  ADD CONSTRAINT `suplente_horario_ibfk_1` FOREIGN KEY (`suplente_id`) REFERENCES `suplentes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `suplente_horario_ibfk_2` FOREIGN KEY (`horario_id`) REFERENCES `horarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `talleres`
--
ALTER TABLE `talleres`
  ADD CONSTRAINT `talleres_ibfk_1` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `taller_curso`
--
ALTER TABLE `taller_curso`
  ADD CONSTRAINT `taller_curso_ibfk_1` FOREIGN KEY (`taller_id`) REFERENCES `talleres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `taller_curso_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
