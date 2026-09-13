<?php
/**
 * Pantalla para agregar y quitar ejercicios de una rutina (RutinaController::asignar).
 *
 * Recibe del controlador: $usuarioSesion, $rutina, $detalle, $ejercicios, $dias, $error.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutinas - Sistema de Gimnasio</title>
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
            --color-exito-borde: #a7f3d0;

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
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--color-borde-suave);
        }

        .panel-cabecera h1,
        .panel > h1 {
            font-size: 1.65rem;
            color: var(--color-texto);
        }

        /* Subtarjeta / Sección Destacada */
        .subseccion-tarjeta {
            background: #fdfbfb;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            padding: 24px;
            margin-top: 26px;
        }

        .subseccion-tarjeta-rosa {
            background: var(--color-primario-suave);
            border-color: var(--color-primario-borde);
        }

        .subseccion-titulo {
            font-size: 1.15rem;
            color: var(--color-texto);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Insignias de Estado */
        .insignia {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .insignia::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .insignia-activa {
            background: var(--color-exito-claro);
            color: var(--color-exito);
            border: 1px solid var(--color-exito-borde);
        }

        .insignia-completada {
            background: var(--color-primario-suave);
            color: var(--color-primario-hover);
            border: 1px solid var(--color-primario-borde);
        }

        .insignia-cancelada {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        /* Tablas */
        .tabla-envoltura {
            overflow-x: auto;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            background: #ffffff;
            margin-top: 14px;
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
            gap: 18px;
            margin-top: 10px;
            max-width: 680px;
        }

        .formulario-full {
            max-width: none;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .campo label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--color-texto);
        }

        .campo input[type="text"],
        .campo input[type="date"],
        .campo input[type="number"],
        .campo select {
            padding: 11px 14px;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            font-size: 0.94rem;
            font-family: inherit;
            background: #ffffff;
            color: var(--color-texto);
            transition: var(--transicion);
        }

        .campo input:focus,
        .campo select:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 4px var(--color-primario-suave);
        }

        .fila-formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
        }

        /* Metadatos / Grid de Resumen en Vista 'Ver' */
        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .resumen-card {
            background: #faf7f8;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            padding: 14px 18px;
        }

        .resumen-card strong {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--color-primario);
            margin-bottom: 4px;
        }

        .resumen-card span {
            font-size: 0.96rem;
            font-weight: 600;
            color: var(--color-texto);
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

        .texto-suave {
            color: var(--color-texto-suave);
            font-size: 0.94rem;
        }

        .espacio-superior {
            margin-top: 24px;
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
        <?php if ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'grupoMuscular', 'action' => 'index']) ?>">Grupos musculares</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'index']) ?>">Ejercicios</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'evaluacionFisica', 'action' => 'historial']) ?>">Evaluaciones físicas</a>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'index']) ?>" class="activo">Rutinas</a>
        <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
            <a href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'index']) ?>" class="activo">Mis rutinas</a>
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

    <section class="panel">
        <div class="panel-cabecera">
            <div>
                <h1>Ejercicios de "<?= htmlspecialchars((string) $rutina['nombre'], ENT_QUOTES, 'UTF-8') ?>"</h1>
                <p class="texto-suave">Cliente: <strong><?= htmlspecialchars((string) ($rutina['cliente_nombres'] . ' ' . $rutina['cliente_apellidos']), ENT_QUOTES, 'UTF-8') ?></strong></p>
            </div>
            <a class="boton boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'ver', 'id' => $rutina['id_rutina']]) ?>">Ver detalle general</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (empty($detalle)): ?>
            <p class="texto-suave" style="padding: 12px 0;">Esta rutina no tiene ejercicios programados aún. Utiliza el formulario inferior para agregar el primero.</p>
        <?php else: ?>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Ejercicio</th>
                            <th>Series</th>
                            <th>Reps</th>
                            <th>Peso</th>
                            <th>Descanso</th>
                            <th>Orden</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle as $fila): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--color-primario);"><?= htmlspecialchars((string) $fila['dia_semana'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="font-weight: 600;"><a href="<?= '/index.php?' . http_build_query(['controller' => 'ejercicio', 'action' => 'ver', 'id' => $fila['id_ejercicio']]) ?>"><?= htmlspecialchars((string) $fila['ejercicio_nombre'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars((string) $fila['series'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $fila['repeticiones'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $fila['peso'] !== null ? htmlspecialchars((string) $fila['peso'], ENT_QUOTES, 'UTF-8') . ' kg' : '—' ?></td>
                                <td><?= htmlspecialchars((string) $fila['tiempo_descanso'], ENT_QUOTES, 'UTF-8') ?>s</td>
                                <td>#<?= htmlspecialchars((string) $fila['orden'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="acciones" style="justify-content: flex-end;">
                                        <a class="boton boton-pequeno boton-secundario" href="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'editarEjercicio', 'id_rutina' => $rutina['id_rutina'], 'id_detalle' => $fila['id_detalle']]) ?>">Editar</a>
                                        <form method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'quitarEjercicio']) ?>" onsubmit="return confirm('¿Quitar este ejercicio de la rutina?');" style="margin: 0;">
                                            <input type="hidden" name="id_rutina" value="<?= htmlspecialchars((string) $rutina['id_rutina'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id_detalle" value="<?= htmlspecialchars((string) $fila['id_detalle'], ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="boton boton-pequeno boton-peligro">Quitar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="subseccion-tarjeta subseccion-tarjeta-rosa">
            <h2 class="subseccion-titulo">
                <span>+</span> Agregar nuevo ejercicio a la rutina
            </h2>
            <form class="formulario formulario-full" method="post" action="<?= '/index.php?' . http_build_query(['controller' => 'rutina', 'action' => 'agregarEjercicio']) ?>">
                <input type="hidden" name="id_rutina" value="<?= htmlspecialchars((string) $rutina['id_rutina'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="fila-formulario">
                    <div class="campo" style="flex: 2;">
                        <label for="id_ejercicio">Ejercicio</label>
                        <select id="id_ejercicio" name="id_ejercicio" required>
                            <option value="">Selecciona un ejercicio</option>
                            <?php foreach ($ejercicios as $ejercicio): ?>
                                <option value="<?= htmlspecialchars((string) $ejercicio['id_ejercicio'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $ejercicio['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo" style="flex: 1;">
                        <label for="dia_semana">Día de la semana</label>
                        <select id="dia_semana" name="dia_semana" required>
                            <?php foreach ($dias as $dia): ?>
                                <option value="<?= htmlspecialchars((string) $dia, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $dia, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="fila-formulario">
                    <div class="campo">
                        <label for="series">Series</label>
                        <input type="number" id="series" name="series" min="1" placeholder="Ej: 4" required>
                    </div>
                    <div class="campo">
                        <label for="repeticiones">Repeticiones</label>
                        <input type="number" id="repeticiones" name="repeticiones" min="1" placeholder="Ej: 10" required>
                    </div>
                    <div class="campo">
                        <label for="peso">Peso (kg, opcional)</label>
                        <input type="number" id="peso" name="peso" min="0" step="0.01" placeholder="Ej: 40">
                    </div>
                    <div class="campo">
                        <label for="tiempo_descanso">Descanso (seg)</label>
                        <input type="number" id="tiempo_descanso" name="tiempo_descanso" min="0" placeholder="Ej: 60" required>
                    </div>
                    <div class="campo">
                        <label for="orden">Orden de ejecución</label>
                        <input type="number" id="orden" name="orden" min="0" placeholder="Ej: 1" required>
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    <button type="submit" class="boton">Agregar a la rutina</button>
                </div>
            </form>
        </div>
    </section>

</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>
