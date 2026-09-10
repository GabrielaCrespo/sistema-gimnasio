-- SISTEMA DE GIMNASIO
-- DISEÑO FÍSICO - POSTGRESQL

-- 1. USUARIO
-- Contiene la información común de todos los usuarios.
-- Los roles disponibles son: administrador,  instructor,  cliente

CREATE TABLE USUARIO (
    id_usuario          SERIAL PRIMARY KEY,
    ci                  VARCHAR(12)  NOT NULL UNIQUE,
    nombres             VARCHAR(100) NOT NULL,
    apellidos           VARCHAR(100) NOT NULL,
    fecha_nacimiento    DATE         NOT NULL,
    correo              VARCHAR(60) NOT NULL UNIQUE,
    password_hash       VARCHAR(60) NOT NULL,

    rol                 VARCHAR(20)  NOT NULL
        CHECK (rol IN ('administrador', 'instructor', 'cliente')),

	estado              BOOLEAN      NOT NULL DEFAULT TRUE
);


-- 2. INSTRUCTOR
-- Herencia de USUARIO.
-- id_usuario es PK y FK al mismo tiempo.

CREATE TABLE INSTRUCTOR (
    id_usuario      INTEGER PRIMARY KEY,
    especialidad    VARCHAR(100) NOT NULL,

    CONSTRAINT fk_instructor_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES USUARIO(id_usuario)
        ON DELETE RESTRICT
);


-- 3. CLIENTE
-- Herencia de USUARIO.
-- id_usuario es PK y FK al mismo tiempo.

CREATE TABLE CLIENTE (
    id_usuario      INTEGER PRIMARY KEY,
    fecha_registro  DATE         NOT NULL DEFAULT CURRENT_DATE,
    altura          DECIMAL(5,2),
    peso            DECIMAL(5,2),

    CONSTRAINT fk_cliente_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES USUARIO(id_usuario)
        ON DELETE RESTRICT
);


-- 4. EVALUACION_FISICA
-- Registra las evaluaciones físicas realizadas a los clientes
-- por los instructores.

CREATE TABLE EVALUACION_FISICA (
    id_evaluacion_fisica    SERIAL PRIMARY KEY,
    fecha                   DATE         NOT NULL DEFAULT CURRENT_DATE,
    peso                    DECIMAL(5,2) NOT NULL,
    altura                  DECIMAL(5,2) NOT NULL,
    objetivo                VARCHAR(200),
    porcentaje_grasa        DECIMAL(5,2),
    masa_muscular           DECIMAL(5,2),
    flexibilidad            DECIMAL(5,2),
    observaciones           TEXT,

    id_cliente              INTEGER NOT NULL,
    id_instructor           INTEGER NOT NULL,

    CONSTRAINT fk_eval_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES CLIENTE(id_usuario),

    CONSTRAINT fk_eval_instructor
        FOREIGN KEY (id_instructor)
        REFERENCES INSTRUCTOR(id_usuario)
);


-- 6. RUTINA
-- Cada rutina pertenece a un cliente y es creada por
-- un instructor.

CREATE TABLE RUTINA (
    id_rutina       SERIAL PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    tipo            VARCHAR(50),
    fecha_inicio    DATE         NOT NULL,
    fecha_fin       DATE,

    estado          VARCHAR(20) NOT NULL DEFAULT 'activa'
        CHECK (estado IN ('activa', 'completada', 'cancelada')),

    id_cliente      INTEGER NOT NULL,
    id_instructor   INTEGER NOT NULL,

    CONSTRAINT fk_rutina_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES CLIENTE(id_usuario),

    CONSTRAINT fk_rutina_instructor
        FOREIGN KEY (id_instructor)
        REFERENCES INSTRUCTOR(id_usuario),

    CONSTRAINT chk_rutina_fechas
        CHECK (
            fecha_fin IS NULL
            OR fecha_fin >= fecha_inicio
        )
);


-- 7. GRUPO_MUSCULAR

CREATE TABLE GRUPO_MUSCULAR (
    id_grupo_muscular    SERIAL PRIMARY KEY,
    nombre               VARCHAR(100) NOT NULL UNIQUE,
    descripcion          TEXT
);


-- 8. TABLA EJERCICIO

CREATE TABLE EJERCICIO (
    id_ejercicio      SERIAL PRIMARY KEY,
    nombre            VARCHAR(100) NOT NULL UNIQUE,
    descripcion       TEXT,
    beneficio         TEXT,
    indicaciones      TEXT,
    url_video         VARCHAR(255)
);


-- 9. EJERCICIO_GRUPO_MUSCULAR
-- Tabla intermedia para representar la relación N:M entre
-- EJERCICIO y GRUPO_MUSCULAR.
-- PK compuesta: id_ejercicio + id_grupo_muscular

CREATE TABLE EJERCICIO_GRUPO_MUSCULAR (
    id_ejercicio       INTEGER NOT NULL,
    id_grupo_muscular  INTEGER NOT NULL,

    PRIMARY KEY (
        id_ejercicio,
        id_grupo_muscular
    ),

    CONSTRAINT fk_egm_ejercicio
        FOREIGN KEY (id_ejercicio)
        REFERENCES EJERCICIO(id_ejercicio)
        ON DELETE CASCADE,

    CONSTRAINT fk_egm_grupo
        FOREIGN KEY (id_grupo_muscular)
        REFERENCES GRUPO_MUSCULAR(id_grupo_muscular)
        ON DELETE CASCADE
);


-- 10. TABLA DETALLE_RUTINA
-- Contiene los ejercicios que forman parte de una rutina.
-- PK COMPUESTA: id_detalle + id_rutina

CREATE TABLE DETALLE_RUTINA (
    id_detalle        SERIAL,
    id_rutina         INTEGER NOT NULL,
    id_ejercicio      INTEGER NOT NULL,

    dia_semana        VARCHAR(10) NOT NULL,
    series            INTEGER NOT NULL,
    repeticiones      INTEGER NOT NULL,
    tiempo_descanso   INTEGER NOT NULL,
    orden             INTEGER NOT NULL,

    -- Clave primaria compuesta
    PRIMARY KEY (
        id_detalle,
        id_rutina
    ),

    -- Validaciones
    CONSTRAINT chk_detalle_series
        CHECK (series > 0),

    CONSTRAINT chk_detalle_repeticiones
        CHECK (repeticiones > 0),

    CONSTRAINT chk_detalle_descanso
        CHECK (tiempo_descanso >= 0),

    CONSTRAINT chk_detalle_orden
        CHECK (orden >= 0),

    -- Relación con RUTINA
    CONSTRAINT fk_detalle_rutina
        FOREIGN KEY (id_rutina)
        REFERENCES RUTINA(id_rutina)
        ON DELETE CASCADE,

    -- Relación con EJERCICIO
    CONSTRAINT fk_detalle_ejercicio
        FOREIGN KEY (id_ejercicio)
        REFERENCES EJERCICIO(id_ejercicio)
        ON DELETE RESTRICT
);


-- 11. ÍNDICES
-- Los índices facilitan las consultas sobre las principales
-- relaciones de la base de datos.

CREATE INDEX idx_rutina_cliente
    ON RUTINA(id_cliente);

CREATE INDEX idx_rutina_instructor
    ON RUTINA(id_instructor);

CREATE INDEX idx_evaluacion_cliente
    ON EVALUACION_FISICA(id_cliente);

CREATE INDEX idx_evaluacion_instructor
    ON EVALUACION_FISICA(id_instructor);

CREATE INDEX idx_detalle_rutina
    ON DETALLE_RUTINA(id_rutina);

CREATE INDEX idx_detalle_ejercicio
    ON DETALLE_RUTINA(id_ejercicio);


-- FIN DEL ESQUEMA


-- =====================================================
-- DATOS INICIALES (SEED)
-- Ejecutar una sola vez sobre el esquema recién creado.
-- =====================================================

-- 1. Administrador inicial
-- Correo: admin@gimnasio.com
-- Contraseña: Admin123!  (guardada como hash bcrypt generado con password_hash())
INSERT INTO USUARIO (ci, nombres, apellidos, fecha_nacimiento, correo, password_hash, rol, estado)
VALUES (
    '0000001',
    'Admin',
    'Sistema',
    '1990-01-01',
    'admin@gimnasio.com',
    '$2y$12$x4I/tMWPcIohj1zmWrjJkutdyfO5eT1xKlJHqbZ6TDlEjtS50i5N2',
    'administrador',
    TRUE
);

-- 2. Catálogo de grupos musculares
INSERT INTO GRUPO_MUSCULAR (nombre, descripcion) VALUES
    ('Pecho', 'Músculos pectorales'),
    ('Espalda', 'Dorsales, trapecio y lumbares'),
    ('Piernas', 'Cuádriceps, isquiotibiales y glúteos'),
    ('Hombros', 'Deltoides anterior, medio y posterior'),
    ('Brazos', 'Bíceps y tríceps'),
    ('Abdomen', 'Recto abdominal y oblicuos');

-- 3. Catálogo de ejercicios
INSERT INTO EJERCICIO (nombre, descripcion, beneficio, indicaciones) VALUES
    ('Press de banca', 'Ejercicio con barra en banco plano', 'Desarrolla fuerza en el pecho, hombros y tríceps', 'Mantener los pies firmes en el suelo y controlar el descenso de la barra'),
    ('Sentadilla', 'Ejercicio compuesto con barra libre', 'Fortalece piernas y glúteos, mejora la estabilidad del core', 'Bajar hasta que los muslos queden paralelos al suelo, manteniendo la espalda recta'),
    ('Peso muerto', 'Levantamiento de barra desde el suelo', 'Fortalece espalda baja, glúteos e isquiotibiales', 'Mantener la barra cerca del cuerpo durante todo el movimiento'),
    ('Dominadas', 'Ejercicio con el propio peso corporal en barra fija', 'Desarrolla la espalda y los bíceps', 'Evitar el balanceo del cuerpo durante la ejecución'),
    ('Press militar', 'Empuje de barra o mancuernas sobre la cabeza', 'Fortalece los hombros y el tríceps', 'No arquear excesivamente la espalda baja'),
    ('Plancha abdominal', 'Isométrico apoyado en antebrazos y pies', 'Fortalece el core y mejora la estabilidad', 'Mantener el cuerpo alineado sin elevar la cadera');

-- 4. Relación ejercicio - grupo muscular (N:M)
INSERT INTO EJERCICIO_GRUPO_MUSCULAR (id_ejercicio, id_grupo_muscular)
SELECT e.id_ejercicio, g.id_grupo_muscular
FROM EJERCICIO e
JOIN GRUPO_MUSCULAR g ON (e.nombre, g.nombre) IN (
    ('Press de banca', 'Pecho'),
    ('Press de banca', 'Brazos'),
    ('Sentadilla', 'Piernas'),
    ('Peso muerto', 'Espalda'),
    ('Peso muerto', 'Piernas'),
    ('Dominadas', 'Espalda'),
    ('Dominadas', 'Brazos'),
    ('Press militar', 'Hombros'),
    ('Plancha abdominal', 'Abdomen')
);

-- FIN
