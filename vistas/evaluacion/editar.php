<?php
/**
 * Formulario con el que el instructor edita una evaluación física ya registrada (EvaluacionFisicaController::editar).
 *
 * Recibe del controlador: $usuarioSesion, $evaluacion, $cliente, $error.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluaciones Físicas - Sistema de Gimnasio</title>
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

        /* Panel Principal */
        .panel {
            background: var(--color-superficie);
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-lg);
            box-shadow: var(--sombra-tarjeta);
            padding: 36px 40px;
            position: relative;
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
            padding: 11px 22px;
            border-radius: var(--radio-md);
            background: linear-gradient(135deg, var(--color-primario) 0%, #ff5277 100%);
            color: #ffffff;
            font-size: 0.94rem;
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

        /* Formularios */
        .formulario {
            display: flex;
            flex-direction: column;
            gap: 22px;
            margin-top: 10px;
        }

        .formulario-ancho {
            max-width: 820px;
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
        .campo input[type="number"],
        .campo select,
        .campo textarea {
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
        .campo select:focus,
        .campo textarea:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 4px var(--color-primario-suave);
        }

        .campo input:disabled {
            background: #f8fafc;
            color: var(--color-texto-suave);
            border-color: var(--color-borde-suave);
            cursor: not-allowed;
        }

        .campo textarea {
            resize: vertical;
        }

        .fila-formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        /* Alertas */
        .alerta {
            padding: 14px 18px;
            border-radius: var(--radio-md);
            margin-bottom: 22px;
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

        .texto-suave {
            color: var(--color-texto-suave);
            font-size: 0.95rem;
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
                padding: 24px 18px;
            }
            .fila-formulario {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-marca">
        <span class="nav-marca-icono">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
        </span>
        Gimnasio
    </div>
    <div class="nav-enlaces">
        <a href="<?= '/index.php?' . http_build_query(['controller' => 'login', 'action' => 'index']) ?>">Inicio</a>
        <?php if ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'grupoMuscular', 'action' => 'index']) ?>">Grupos musculares</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>">Ejercicios</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'historial']) ?>" class="activo">Evaluaciones físicas</a>
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

<main class="contenedor">

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h1>Editar Evaluación Física</h1>
                <p class="texto-suave">Cliente: <strong><?= htmlspecialchars((string) ($cliente['nombres'] . ' ' . $cliente['apellidos']), ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <a class="boton boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'historial', 'id' => $evaluacion['id_cliente']]) ?>">← Volver al historial</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'actualizar']) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $evaluacion['id_evaluacion_fisica'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="campo">
                <label for="cliente_nombre">Cliente evaluado</label>
                <input type="text" id="cliente_nombre" value="<?= htmlspecialchars((string) ($cliente['nombres'] . ' ' . $cliente['apellidos']), ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="peso">Peso corporal (kg)</label>
                    <input type="number" step="0.01" id="peso" name="peso" placeholder="Ej: 72.50" value="<?= htmlspecialchars((string) ($evaluacion['peso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="campo">
                    <label for="altura">Estatura / Altura (m)</label>
                    <input type="number" step="0.01" id="altura" name="altura" placeholder="Ej: 1.75" value="<?= htmlspecialchars((string) ($evaluacion['altura'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="porcentaje_grasa">% Grasa corporal</label>
                    <input type="number" step="0.01" id="porcentaje_grasa" name="porcentaje_grasa" placeholder="Ej: 16.5" value="<?= htmlspecialchars((string) ($evaluacion['porcentaje_grasa'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="campo">
                    <label for="masa_muscular">Masa muscular (kg)</label>
                    <input type="number" step="0.01" id="masa_muscular" name="masa_muscular" placeholder="Ej: 34.2" value="<?= htmlspecialchars((string) ($evaluacion['masa_muscular'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="campo">
                    <label for="flexibilidad">Flexibilidad (cm)</label>
                    <input type="number" step="0.01" id="flexibilidad" name="flexibilidad" placeholder="Ej: 5.0" value="<?= htmlspecialchars((string) ($evaluacion['flexibilidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="campo">
                <label for="objetivo">Objetivo principal</label>
                <input type="text" id="objetivo" name="objetivo" placeholder="Ej: Hipertrofia, recomposición corporal, pérdida de grasa..." value="<?= htmlspecialchars((string) ($evaluacion['objetivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="campo">
                <label for="observaciones">Observaciones y notas adicionales</label>
                <textarea id="observaciones" name="observaciones" rows="3" placeholder="Comentarios clínicos, antecedentes de lesiones o indicaciones dietéticas..."><?= htmlspecialchars((string) ($evaluacion['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="margin-top: 8px;">
                <button type="submit" class="boton">Guardar cambios</button>
            </div>
        </form>
    </section>

</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>
