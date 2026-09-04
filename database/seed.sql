-- SISTEMA DE GIMNASIO
-- DATOS INICIALES (SEED)
-- Ejecutar una sola vez sobre una base ya creada con schema.sql.

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
