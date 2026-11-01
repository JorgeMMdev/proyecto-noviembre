-- =====================================================================
--  Banco de Horas y Servicios · estructura de la base de datos + seeds
--  MySQL 8 · InnoDB · utf8mb4
--
--  Usuarios de prueba (contraseña de todos: Trueque2026!)
--    admin@bancohoras.com  → ADMIN
--    carlos@mail.com, lucia@mail.com, miguel@mail.com, ana@mail.com → PROFESIONAL
--
--  Este script solo se ejecuta la primera vez que se crea el volumen
--  de MySQL. Para volver a cargarlo:  docker compose down -v
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  ESTRUCTURA
-- ---------------------------------------------------------------------

CREATE TABLE categorias (
    id_categoria     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(100) NOT NULL,
    descripcion      TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuarios (
    id_usuario       INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100) NOT NULL,
    apellidos        VARCHAR(100) NOT NULL,
    correo           VARCHAR(150) NOT NULL UNIQUE,
    contrasena       VARCHAR(255) NOT NULL,                       -- hash (password_hash)
    fecha_nacimiento DATE NOT NULL,
    rol              ENUM('ADMIN', 'PROFESIONAL') NOT NULL DEFAULT 'PROFESIONAL',
    valoracion_media DECIMAL(3,2) NULL,                           -- NULL = aún sin valoraciones
    saldo            INT NOT NULL DEFAULT 50                      -- puntos de bienvenida
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE servicios (
    id_servicio            INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria           INT NOT NULL,
    nombre_servicio        VARCHAR(150) NOT NULL,
    precio_medio_estimado  INT NOT NULL DEFAULT 10,               -- puntos/hora de referencia
    tiempo_medio_estimado  DECIMAL(4,2) NOT NULL DEFAULT 1.00,    -- horas
    descripcion            TEXT,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ofertas_profesionales (
    id_oferta            INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario           INT NOT NULL,
    id_servicio          INT NOT NULL,
    puntos_hora          INT NOT NULL,                            -- tarifa elegida por el profesional
    descripcion_personal TEXT,
    activo               BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario)   ON DELETE CASCADE,
    FOREIGN KEY (id_servicio) REFERENCES servicios(id_servicio) ON DELETE CASCADE,
    CONSTRAINT uk_usuario_servicio UNIQUE (id_usuario, id_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE solicitudes (
    id_solicitud         INT AUTO_INCREMENT PRIMARY KEY,
    id_oferta            INT NOT NULL,
    id_solicitante       INT NOT NULL,                            -- cliente que pide el servicio
    precio_final         INT NOT NULL,                            -- puntos totales (puntos_hora × horas)
    tiempo_final         DECIMAL(4,2) NOT NULL,                   -- horas
    fecha_propuesta      DATETIME NOT NULL,
    estado               ENUM('SOLICITADO', 'ACEPTADO', 'RECHAZADO',
                              'EN_EJECUCION', 'COMPLETADO', 'CANCELADO')
                         NOT NULL DEFAULT 'SOLICITADO',
    confirmado_cliente   BOOLEAN NOT NULL DEFAULT FALSE,
    confirmado_prestador BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_creacion       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_oferta)      REFERENCES ofertas_profesionales(id_oferta) ON DELETE CASCADE,
    FOREIGN KEY (id_solicitante) REFERENCES usuarios(id_usuario)             ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE valoraciones (
    id_valoracion    INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud     INT NOT NULL UNIQUE,                         -- como máximo una por solicitud
    puntuacion       TINYINT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario       TEXT,
    fecha_valoracion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_solicitud) REFERENCES solicitudes(id_solicitud) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
--  DATOS DE PRUEBA (seeds)
-- ---------------------------------------------------------------------

INSERT INTO categorias (nombre_categoria, descripcion) VALUES
    ('Reparaciones',  'Fontanería, electricidad y arreglos del hogar'),
    ('Enseñanza',     'Clases particulares y formación'),
    ('Informática',   'Soporte técnico y desarrollo web'),
    ('Bienestar',     'Deporte, relajación y cuidado personal');

-- Contraseña de todos los usuarios: Trueque2026!
INSERT INTO usuarios (nombre, apellidos, correo, contrasena, fecha_nacimiento, rol, valoracion_media, saldo) VALUES
    ('Admin',  'Sistema',        'admin@bancohoras.com', '$2b$10$n2Knrw9XfTgOuNWRIA5yPuiri7CgKEG8Tq8n4/ogj.nFKM7gwL.NK', '1980-01-01', 'ADMIN',       NULL, 0),
    ('Carlos', 'Ruiz Gómez',     'carlos@mail.com',      '$2b$10$n2Knrw9XfTgOuNWRIA5yPuiri7CgKEG8Tq8n4/ogj.nFKM7gwL.NK', '1985-03-12', 'PROFESIONAL', 5.00, 64),
    ('Lucía',  'Fernández Mora', 'lucia@mail.com',       '$2b$10$n2Knrw9XfTgOuNWRIA5yPuiri7CgKEG8Tq8n4/ogj.nFKM7gwL.NK', '1990-07-25', 'PROFESIONAL', 4.00, 36),
    ('Miguel', 'Torres Sanz',    'miguel@mail.com',      '$2b$10$n2Knrw9XfTgOuNWRIA5yPuiri7CgKEG8Tq8n4/ogj.nFKM7gwL.NK', '1978-11-02', 'PROFESIONAL', NULL, 50),
    ('Ana',    'Beltrán Ríos',   'ana@mail.com',         '$2b$10$n2Knrw9XfTgOuNWRIA5yPuiri7CgKEG8Tq8n4/ogj.nFKM7gwL.NK', '1993-05-30', 'PROFESIONAL', NULL, 50);

INSERT INTO servicios (id_categoria, nombre_servicio, precio_medio_estimado, tiempo_medio_estimado, descripcion) VALUES
    (1, 'Reparación de tuberías',      12, 1.50, 'Arreglo de fugas, desatascos y cambio de grifería'),
    (1, 'Instalación eléctrica',       14, 2.00, 'Puntos de luz, cuadros y revisión de instalaciones'),
    (1, 'Arreglar enchufe',            10, 1.00, 'Sustitución y reparación de enchufes e interruptores'),
    (2, 'Clases de matemáticas ESO',   10, 1.00, 'Refuerzo de matemáticas para Secundaria'),
    (2, 'Clases de inglés',            10, 1.00, 'Conversación y gramática, todos los niveles'),
    (3, 'Reparación de ordenadores',   12, 1.50, 'Diagnóstico, limpieza y reinstalación de sistemas'),
    (3, 'Creación de página web',      15, 4.00, 'Sitio web sencillo y adaptable a móvil'),
    (4, 'Clases de yoga',               8, 1.00, 'Sesiones de yoga para principiantes');

INSERT INTO ofertas_profesionales (id_usuario, id_servicio, puntos_hora, descripcion_personal, activo) VALUES
    (2, 1, 12, 'Fontanero con 15 años de experiencia en viviendas y comunidades.', TRUE),
    (4, 2, 14, 'Electricista instalador autorizado. Presupuesto sin compromiso.',  TRUE),
    (4, 3, 10, 'Reparaciones eléctricas rápidas en el día.',                       TRUE),
    (3, 4, 10, 'Profesora de Secundaria. Explicaciones claras y con ejercicios.',  TRUE),
    (3, 5, 11, 'Nivel C1, preparación de exámenes oficiales.',                     TRUE),
    (5, 6, 12, 'Técnica de sistemas. Recuperación de datos y puesta a punto.',     TRUE),
    (5, 7, 16, 'Desarrolladora web full stack.',                                   TRUE),
    (3, 8,  8, 'Instructora de yoga (temporalmente sin disponibilidad).',          FALSE);

INSERT INTO solicitudes (id_oferta, id_solicitante, precio_final, tiempo_final, fecha_propuesta,
                         estado, confirmado_cliente, confirmado_prestador) VALUES
    (1, 3, 24, 2.00, '2026-10-15 10:00:00', 'COMPLETADO',   TRUE,  TRUE),   -- Lucía contrata a Carlos
    (4, 2, 10, 1.00, '2026-10-20 17:00:00', 'COMPLETADO',   TRUE,  TRUE),   -- Carlos contrata a Lucía
    (2, 5, 28, 2.00, '2026-11-10 09:00:00', 'ACEPTADO',     FALSE, FALSE),  -- Ana contrata a Miguel
    (6, 4, 18, 1.50, '2026-11-12 16:00:00', 'SOLICITADO',   FALSE, FALSE),  -- Miguel contrata a Ana
    (7, 2, 64, 4.00, '2026-11-14 10:00:00', 'RECHAZADO',    FALSE, FALSE),  -- Carlos pide una web a Ana
    (3, 3, 10, 1.00, '2026-11-05 12:00:00', 'EN_EJECUCION', TRUE,  FALSE);  -- Lucía contrata a Miguel

INSERT INTO valoraciones (id_solicitud, puntuacion, comentario) VALUES
    (1, 5, 'Muy profesional y puntual. Dejó todo limpio.'),
    (2, 4, 'Buena explicación, mi sobrino ha mejorado bastante.');