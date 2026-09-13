-- ============================================================
-- SISTEMA DE GESTIÓN DE GIMNASIO
-- BASE DE DATOS: PostgreSQL
-- ============================================================

-- ============================================================
-- 1. TABLA CLIENTE
-- ============================================================

CREATE TABLE CLIENTE (
    id_cliente          SERIAL PRIMARY KEY,
    ci                  VARCHAR(12)  NOT NULL UNIQUE,
    nombres             VARCHAR(100) NOT NULL,
    apellidos           VARCHAR(100) NOT NULL,
    fecha_nacimiento    DATE         NOT NULL,
    correo              VARCHAR(60)  NOT NULL UNIQUE,
    password_hash       VARCHAR(60)  NOT NULL,
    fecha_registro      DATE         NOT NULL DEFAULT CURRENT_DATE,
    altura              DECIMAL(5,2),
    peso                DECIMAL(5,2),
    estado              BOOLEAN      NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 2. TABLA INSTRUCTOR
-- ============================================================

CREATE TABLE INSTRUCTOR (
    id_instructor       SERIAL PRIMARY KEY,
    ci                  VARCHAR(12)  NOT NULL UNIQUE,
    nombres             VARCHAR(100) NOT NULL,
    apellidos           VARCHAR(100) NOT NULL,
    fecha_nacimiento    DATE         NOT NULL,
    correo              VARCHAR(60)  NOT NULL UNIQUE,
    password_hash       VARCHAR(60)  NOT NULL,
    especialidad        VARCHAR(100) NOT NULL,
    estado              BOOLEAN      NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 3. EVALUACION_FISICA
-- Registra las evaluaciones realizadas por un Instructor
-- a un Cliente.
-- ============================================================

CREATE TABLE EVALUACION_FISICA (
    id_evaluacion_fisica    SERIAL PRIMARY KEY,
    fecha                   DATE         NOT NULL DEFAULT CURRENT_DATE,
    peso                    DECIMAL(5,2) NOT NULL,
    altura                  DECIMAL(5,2) NOT NULL,
    objetivo                VARCHAR(200),
    porcentaje_grasa        DECIMAL(5,2),
    masa_muscular           DECIMAL(5,2),
    flexibilidad            VARCHAR(200),
    observaciones           TEXT,

    id_cliente              INTEGER NOT NULL,
    id_instructor           INTEGER NOT NULL,

    CONSTRAINT fk_eval_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES CLIENTE(id_cliente)
        ON DELETE RESTRICT,

    CONSTRAINT fk_eval_instructor
        FOREIGN KEY (id_instructor)
        REFERENCES INSTRUCTOR(id_instructor)
        ON DELETE RESTRICT
);


-- ============================================================
-- 4. GRUPO_MUSCULAR
-- ============================================================

CREATE TABLE GRUPO_MUSCULAR (
    id_grupo_muscular   SERIAL PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL UNIQUE,
    descripcion         TEXT
);


-- ============================================================
-- 5. EJERCICIO
-- ============================================================

CREATE TABLE EJERCICIO (
    id_ejercicio        SERIAL PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL UNIQUE,
    descripcion         TEXT,
    beneficio           TEXT,
    indicaciones        TEXT,
    url_video           VARCHAR(255),
    url_imagen_1        VARCHAR(255),
    url_imagen_2        VARCHAR(255)
);


-- ============================================================
-- 6. EJERCICIO_GRUPO_MUSCULAR
-- Relación N:M entre EJERCICIO y GRUPO_MUSCULAR.
-- ============================================================

CREATE TABLE EJERCICIO_GRUPO_MUSCULAR (
    id_ejercicio        INTEGER NOT NULL,
    id_grupo_muscular   INTEGER NOT NULL,

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


-- ============================================================
-- 7. RUTINA
-- Cada rutina pertenece a un Cliente y es creada
-- por un Instructor.
-- ============================================================

CREATE TABLE RUTINA (
    id_rutina           SERIAL PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    tipo                VARCHAR(50),
    fecha_inicio        DATE         NOT NULL,
    fecha_fin           DATE,

    estado              VARCHAR(20) NOT NULL DEFAULT 'activa'
        CHECK (estado IN ('activa', 'completada', 'cancelada')),

    id_cliente          INTEGER NOT NULL,
    id_instructor       INTEGER NOT NULL,

    CONSTRAINT fk_rutina_cliente
        FOREIGN KEY (id_cliente)
        REFERENCES CLIENTE(id_cliente)
        ON DELETE RESTRICT,

    CONSTRAINT fk_rutina_instructor
        FOREIGN KEY (id_instructor)
        REFERENCES INSTRUCTOR(id_instructor)
        ON DELETE RESTRICT,

    CONSTRAINT chk_rutina_fechas
        CHECK (
            fecha_fin IS NULL
            OR fecha_fin >= fecha_inicio
        )
);


-- ============================================================
-- 8. DETALLE_RUTINA
-- Contiene los ejercicios que forman parte de una rutina.
-- ============================================================
CREATE TABLE DETALLE_RUTINA (
    id_detalle          SERIAL,
    id_rutina           INTEGER NOT NULL,
    id_ejercicio        INTEGER NOT NULL,

    dia_semana          VARCHAR(10) NOT NULL,
    series              INTEGER NOT NULL,
    repeticiones        INTEGER NOT NULL,
    peso                DECIMAL(6,2),
    tiempo_descanso     INTEGER NOT NULL,
    orden               INTEGER NOT NULL,

    PRIMARY KEY (
        id_detalle,
        id_rutina
    ),

    CONSTRAINT chk_detalle_series
        CHECK (series > 0),

    CONSTRAINT chk_detalle_repeticiones
        CHECK (repeticiones > 0),

    CONSTRAINT chk_detalle_peso
        CHECK (peso IS NULL OR peso >= 0),

    CONSTRAINT chk_detalle_descanso
        CHECK (tiempo_descanso >= 0),

    CONSTRAINT chk_detalle_orden
        CHECK (orden >= 0),

    CONSTRAINT fk_detalle_rutina
        FOREIGN KEY (id_rutina)
        REFERENCES RUTINA(id_rutina)
        ON DELETE CASCADE,

    CONSTRAINT fk_detalle_ejercicio
        FOREIGN KEY (id_ejercicio)
        REFERENCES EJERCICIO(id_ejercicio)
        ON DELETE RESTRICT
);

-- ============================================================
-- FIN DE LA BASE DE DATOS
-- ============================================================