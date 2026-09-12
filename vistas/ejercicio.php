<?php
/**
 * Vista de ejercicios (EjercicioController). $accion decide el contenido:
 * listar, ver, crear o editar.
 */
$usuarioSesion = $_SESSION['user'] ?? null;
$datos = $datos ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ejercicios - Gimnasio</title>
    <!-- Tipografía profesional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Paleta Rosa Claro & Sofisticada */
            --color-primario: #e83e8c;
            --color-primario-hover: #d62575;
            --color-primario-suave: #fdf2f6;
            --color-primario-borde: #fcc2d7;
            --color-acento: #ff6b8b;
            
            /* Fondos y Neutros Claros */
            --color-fondo: #faf7f8;
            --color-superficie: #ffffff;
            --color-borde-suave: #f1e4e8;
            
            /* Textos */
            --color-texto: #2d242a;
            --color-texto-suave: #796670;
            --color-texto-mutado: #a89aa1;
            
            /* Estados */
            --color-peligro: #ef4444;
            --color-peligro-claro: #fef2f2;
            --color-peligro-borde: #fecaca;
            --color-exito: #10b981;
            --color-exito-claro: #ecfdf5;

            /* Radios y Sombras */
            --radio-lg: 20px;
            --radio-md: 12px;
            --radio-sm: 8px;
            --sombra-suave: 0 4px 20px -2px rgba(232, 62, 140, 0.06), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
            --sombra-tarjeta: 0 12px 32px -4px rgba(232, 62, 140, 0.1), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
            --sombra-boton: 0 6px 18px rgba(232, 62, 140, 0.25);
            --transicion: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(180deg, #fdf6f8 0%, var(--color-fondo) 100%);
            color: var(--color-texto);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3 {
            font-weight: 700;
            color: var(--color-texto);
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        a {
            color: var(--color-primario);
            text-decoration: none;
            transition: var(--transicion);
        }

        /* Barra de Navegación */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 36px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--color-borde-suave);
            box-shadow: var(--sombra-suave);
        }

        .nav-marca {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--color-texto);
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
        }

        .nav-marca-icono {
            background: linear-gradient(135deg, #ff85a1 0%, var(--color-primario) 100%);
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(232, 62, 140, 0.25);
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .nav a {
            color: var(--color-texto-suave);
            font-size: 0.92rem;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: var(--radio-sm);
        }

        .nav a:hover,
        .nav a.activo {
            color: var(--color-primario);
            background: var(--color-primario-suave);
            text-decoration: none;
        }

        .nav-salir {
            color: var(--color-primario) !important;
            background: var(--color-primario-suave) !important;
            border: 1px solid var(--color-primario-borde);
        }

        .nav-salir:hover {
            background: var(--color-primario) !important;
            color: #ffffff !important;
        }

        /* Contenedor Principal */
        .contenedor {
            max-width: 1140px;
            width: 100%;
            margin: 0 auto;
            padding: 36px 24px 60px;
            flex: 1;
        }

        /* Tarjeta / Panel Principal */
        .panel {
            background: var(--color-superficie);
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-lg);
            box-shadow: var(--sombra-tarjeta);
            padding: 36px 40px;
            position: relative;
            overflow: hidden;
        }

        .panel-cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 26px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--color-borde-suave);
        }

        .panel-cabecera h1,
        .panel > h1 {
            font-size: 1.65rem;
            color: var(--color-texto);
        }

        /* Botones */
        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radio-md);
            background: linear-gradient(135deg, var(--color-primario) 0%, #ff5277 100%);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            box-shadow: var(--sombra-boton);
            transition: var(--transicion);
        }

        .boton:hover {
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(232, 62, 140, 0.35);
        }

        .boton-secundario {
            background: var(--color-superficie);
            color: var(--color-texto);
            border: 1px solid var(--color-borde-suave);
            box-shadow: var(--sombra-suave);
        }

        .boton-secundario:hover {
            background: var(--color-primario-suave);
            color: var(--color-primario);
            border-color: var(--color-primario-borde);
            transform: translateY(-2px);
        }

        .boton-peligro {
            background: var(--color-peligro);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        .boton-peligro:hover {
            background: #dc2626;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.35);
        }

        .boton-pequeno {
            padding: 6px 14px;
            font-size: 0.84rem;
            border-radius: var(--radio-sm);
        }

        /* Tablas */
        .tabla-envoltura {
            overflow-x: auto;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #faf4f7;
            color: var(--color-texto-suave);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 14px 18px;
            border-bottom: 1px solid var(--color-borde-suave);
        }

        td {
            padding: 14px 18px;
            font-size: 0.92rem;
            color: var(--color-texto);
            border-bottom: 1px solid var(--color-borde-suave);
            vertical-align: middle;
        }

        tbody tr {
            transition: var(--transicion);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: var(--color-primario-suave);
        }

        td .acciones {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Formularios */
        .formulario {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 18px;
        }

        .formulario-ancho {
            max-width: 720px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--color-texto);
        }

        .campo input[type="text"],
        .campo input[type="url"],
        .campo textarea,
        .campo select {
            padding: 12px 14px;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            font-size: 0.94rem;
            font-family: inherit;
            background: #ffffff;
            color: var(--color-texto);
            transition: var(--transicion);
        }

        .campo input:focus,
        .campo textarea:focus,
        .campo select:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 4px var(--color-primario-suave);
        }

        .campo textarea {
            resize: vertical;
        }

        /* Selector de grupos musculares (estilo cuadrícula de chips) */
        .selector-grupos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 4px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radio-sm);
            border: 1px solid var(--color-borde-suave);
            background: #faf7f8;
            cursor: pointer;
            transition: var(--transicion);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .checkbox-label:hover {
            border-color: var(--color-primario-borde);
            background: var(--color-primario-suave);
        }

        .checkbox-label input[type="checkbox"] {
            accent-color: var(--color-primario);
            width: 16px;
            height: 16px;
        }

        /* Vista de Detalle (Ver) */
        .detalle-bloque {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .detalle-item {
            padding: 14px 18px;
            border-radius: var(--radio-md);
            background: #faf7f8;
            border: 1px solid var(--color-borde-suave);
        }

        .detalle-item strong {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--color-primario);
            margin-bottom: 4px;
        }

        .detalle-item p {
            color: var(--color-texto);
            font-size: 0.95rem;
        }

        .chips-contenedor {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }

        .chip-grupo {
            background: var(--color-primario-suave);
            border: 1px solid var(--color-primario-borde);
            color: var(--color-primario-hover);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 600;
        }

        /* Alertas */
        .alerta {
            padding: 14px 18px;
            border-radius: var(--radio-md);
            margin-bottom: 20px;
            font-size: 0.92rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alerta-error {
            background: var(--color-peligro-claro);
            color: var(--color-peligro);
            border: 1px solid var(--color-peligro-borde);
        }

        /* Pie de página */
        .pie {
            margin-top: auto;
            padding: 26px;
            text-align: center;
            color: var(--color-texto-suave);
            font-size: 0.88rem;
            border-top: 1px solid var(--color-borde-suave);
            background: rgba(255, 255, 255, 0.7);
        }

        @media (max-width: 768px) {
            .nav {
                padding: 14px 20px;
            }
            .panel {
                padding: 24px 20px;
            }
            .selector-grupos {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php if ($usuarioSesion): ?>
<nav class="nav">
    <div class="nav-marca">
        <span class="nav-marca-icono">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        </span>
        Gimnasio
    </div>
    <div class="nav-enlaces">
        <a href="<?= '/index.php?' . http_build_query(['controller' => 'login', 'action' => 'index']) ?>">Inicio</a>
        <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'cliente', 'action' => 'index']) ?>">Clientes</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'instructor', 'action' => 'index']) ?>">Instructores</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'grupoMuscular', 'action' => 'index']) ?>">Grupos musculares</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>" class="activo">Ejercicios</a>
        <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'grupoMuscular', 'action' => 'index']) ?>">Grupos musculares</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>" class="activo">Ejercicios</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'registrar']) ?>">Registrar evaluación</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'index']) ?>">Rutinas</a>
        <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'index']) ?>">Mis rutinas</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'historial']) ?>">Mis evaluaciones</a>
        <?php endif; ?>
    </div>
    <div class="nav-enlaces">
        <?php if ($usuarioSesion['rol'] === 'cliente'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'cliente', 'action' => 'perfil']) ?>">Mi perfil</a>
        <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'instructor', 'action' => 'perfil']) ?>">Mi perfil</a>
        <?php endif; ?>
        <a href="<?= '/index.php?' . http_build_query(['controller' => 'login', 'action' => 'logout']) ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>
<?php endif; ?>

<main class="contenedor">

<?php if ($accion === 'listar'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Catálogo de Ejercicios</h1>
            <a class="boton" href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'crear']) ?>">
                <span>+ Nuevo ejercicio</span>
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th style="width: 220px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ejercicios)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--color-texto-suave); padding: 30px;">
                                No hay ejercicios registrados aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ejercicios as $ejercicio): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--color-texto);"><?= htmlspecialchars((string) $ejercicio['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="color: var(--color-texto-suave);"><?= htmlspecialchars((string) ($ejercicio['descripcion'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="acciones" style="justify-content: flex-end;">
                                        <a class="boton boton-pequeno boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'ver', 'id' => $ejercicio['id_ejercicio']]) ?>">Ver</a>
                                        <a class="boton boton-pequeno boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'editar', 'id' => $ejercicio['id_ejercicio']]) ?>">Editar</a>
                                        <form method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'eliminar']) ?>" onsubmit="return confirm('¿Eliminar este ejercicio?');" style="margin: 0;">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $ejercicio['id_ejercicio'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="boton boton-pequeno boton-peligro">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($accion === 'ver'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1><?= htmlspecialchars((string) $ejercicio['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
            <a class="boton boton-secundario" href="<?= $usuarioSesion['rol'] === 'cliente' ? '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'index']) : '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>">← Volver</a>
        </div>

        <div class="detalle-bloque">
            <?php if (!empty($ejercicio['url_video'])): ?>
                <div class="detalle-item">
                    <strong>Demostración en Video</strong>
                    <video controls preload="metadata" style="max-width: 100%; border-radius: var(--radio-md); margin-top: 8px;">
                        <source src="<?= htmlspecialchars((string) ('/videos/' . $ejercicio['url_video']), ENT_QUOTES, 'UTF-8') ?>">
                        Tu navegador no soporta la reproducción de video.
                    </video>
                </div>
            <?php endif; ?>

            <div class="detalle-item">
                <strong>Descripción</strong>
                <p><?= nl2br(htmlspecialchars((string) ($ejercicio['descripcion'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>

            <div class="detalle-item">
                <strong>Beneficio</strong>
                <p><?= nl2br(htmlspecialchars((string) ($ejercicio['beneficio'] ?? 'No especificado'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>

            <div class="detalle-item">
                <strong>Indicaciones Técnicas</strong>
                <p><?= nl2br(htmlspecialchars((string) ($ejercicio['indicaciones'] ?? 'No especificadas'), ENT_QUOTES, 'UTF-8')) ?></p>
            </div>

            <div class="detalle-item">
                <strong>Grupos Musculares Asociados</strong>
                <?php if (empty($grupos)): ?>
                    <p style="color: var(--color-texto-suave);">No tiene grupos musculares asignados.</p>
                <?php else: ?>
                    <div class="chips-contenedor">
                        <?php foreach ($grupos as $grupo): ?>
                            <span class="chip-grupo"><?= htmlspecialchars((string) $grupo['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Nuevo Ejercicio</h1>
            <a class="boton boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>">← Volver</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'guardar']) ?>" enctype="multipart/form-data">
            <div class="campo">
                <label for="nombre">Nombre del ejercicio</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars((string) ($datos['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Press de banca plano" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Breve descripción del movimiento..."><?= htmlspecialchars((string) ($datos['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3" placeholder="¿Qué aporta al atleta?"><?= htmlspecialchars((string) ($datos['beneficio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones posturales y ejecución</label>
                <textarea id="indicaciones" name="indicaciones" rows="3" placeholder="Espalda apoyada, retracción escapular..."><?= htmlspecialchars((string) ($datos['indicaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <label for="video">Video demostrativo (opcional)</label>
                <input type="file" id="video" name="video" accept="video/mp4,video/webm,video/ogg,.mov">
            </div>
            <div class="campo">
                <label>Grupos musculares involucrados</label>
                <div class="selector-grupos">
                    <?php foreach ($gruposDisponibles as $grupo): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="grupos[]" value="<?= htmlspecialchars((string) $grupo['id_grupo_muscular'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados ?? [], true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars((string) $grupo['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <button type="submit" class="boton">Guardar ejercicio</button>
            </div>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Editar Ejercicio</h1>
            <a class="boton boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>">← Volver</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'actualizar']) ?>" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $ejercicio['id_ejercicio'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="campo">
                <label for="nombre">Nombre del ejercicio</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars((string) $ejercicio['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars((string) ($ejercicio['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3"><?= htmlspecialchars((string) ($ejercicio['beneficio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones posturales y ejecución</label>
                <textarea id="indicaciones" name="indicaciones" rows="3"><?= htmlspecialchars((string) ($ejercicio['indicaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="campo">
                <?php if (!empty($ejercicio['url_video'])): ?>
                    <label>Video actual</label>
                    <video controls preload="metadata" style="max-width: 100%; border-radius: var(--radio-md); margin-bottom: 10px;">
                        <source src="<?= htmlspecialchars((string) ('/videos/' . $ejercicio['url_video']), ENT_QUOTES, 'UTF-8') ?>">
                    </video>
                <?php endif; ?>
                <label for="video"><?= !empty($ejercicio['url_video']) ? 'Reemplazar video (opcional)' : 'Video demostrativo (opcional)' ?></label>
                <input type="file" id="video" name="video" accept="video/mp4,video/webm,video/ogg,.mov">
            </div>
            <div class="campo">
                <label>Grupos musculares involucrados</label>
                <div class="selector-grupos">
                    <?php foreach ($gruposDisponibles as $grupo): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="grupos[]" value="<?= htmlspecialchars((string) $grupo['id_grupo_muscular'], ENT_QUOTES, 'UTF-8') ?>"
                                <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados ?? [], true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars((string) $grupo['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <button type="submit" class="boton">Guardar cambios</button>
            </div>
        </form>
    </section>

<?php endif; ?>

</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>