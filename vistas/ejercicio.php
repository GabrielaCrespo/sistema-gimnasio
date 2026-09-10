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
    <title>Sistema de Gestión de Gimnasio</title>
    <style>
        :root {
            --color-primario: #d9782e;
            --color-primario-oscuro: #a85820;
            --color-primario-claro: #fbe9d9;
            --color-oscuro: #21242b;
            --color-oscuro-2: #2d323b;
            --color-secundario: #58626c;
            --color-fondo: #f6f5f2;
            --color-superficie: #ffffff;
            --color-borde: #e6e2da;
            --color-texto: #24211c;
            --color-texto-suave: #6c6459;
            --color-peligro: #c0362c;
            --color-peligro-claro: #fbebea;
            --color-exito: #2f8a4e;
            --color-exito-claro: #e8f5ec;
            --radio: 10px;
            --radio-chico: 6px;
            --sombra: 0 1px 2px rgba(20, 15, 10, .08);
            --sombra-media: 0 14px 30px -12px rgba(20, 15, 10, .3);
            --transicion: 150ms ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3 {
            line-height: 1.25;
            letter-spacing: -0.01em;
        }

        a {
            color: var(--color-primario-oscuro);
            text-decoration: none;
            transition: color var(--transicion);
        }

        a:hover {
            color: var(--color-primario);
            text-decoration: underline;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid var(--color-primario);
            outline-offset: 2px;
        }

        .contenedor {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        /* Navegación */
        .nav {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--color-oscuro);
            border-bottom: 3px solid var(--color-primario);
            padding: 14px 24px;
            box-shadow: var(--sombra-media);
        }

        .nav-marca {
            color: #fff;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.01em;
            margin-right: 16px;
        }

        .nav-marca::before {
            content: "🏋 ";
        }

        .nav-enlaces {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #c7ccd4;
            font-size: 0.94rem;
            font-weight: 500;
            padding: 4px 2px;
            border-bottom: 2px solid transparent;
        }

        .nav a:hover {
            color: #fff;
            text-decoration: none;
            border-bottom-color: var(--color-primario);
        }

        .nav-salir {
            color: #fff;
            font-weight: 600;
        }

        /* Landing / dashboard: banda oscura tipo "hero" */
        .landing {
            background: linear-gradient(135deg, var(--color-oscuro), var(--color-oscuro-2));
            color: #fff;
            border-radius: var(--radio);
            box-shadow: var(--sombra-media);
            padding: 48px 36px;
            text-align: center;
        }

        .landing h1 {
            margin: 0 0 8px;
            font-size: 2.1rem;
        }

        .landing p {
            color: #c7ccd4;
            font-size: 1.02rem;
            max-width: 46em;
            margin: 0 auto;
        }

        .landing-acciones {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 26px;
            flex-wrap: wrap;
        }

        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 28px;
            text-align: left;
        }

        .tarjeta {
            display: block;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .16);
            border-left: 3px solid var(--color-primario);
            border-radius: var(--radio);
            padding: 20px;
            font-weight: 600;
            color: #fff;
            transition: transform var(--transicion), background var(--transicion), border-color var(--transicion);
        }

        .tarjeta:hover {
            background: rgba(255, 255, 255, .12);
            border-color: var(--color-primario);
            transform: translateY(-2px);
            text-decoration: none;
        }

        /* Botones */
        .boton {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: var(--radio-chico);
            background: var(--color-primario);
            color: #fff;
            border: 1px solid transparent;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            box-shadow: var(--sombra);
            transition: background var(--transicion), transform var(--transicion), box-shadow var(--transicion);
        }

        .boton:hover {
            background: var(--color-primario-oscuro);
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: var(--sombra-media);
        }

        .boton:active {
            transform: translateY(0);
        }

        .boton-secundario {
            background: transparent;
            color: #fff;
            border-color: rgba(255, 255, 255, .35);
        }

        .boton-secundario:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
            border-color: #fff;
        }

        /* Fuera del hero oscuro (paneles claros), el botón secundario usa tinta oscura */
        .panel .boton-secundario,
        .panel-cabecera .boton-secundario {
            color: var(--color-primario-oscuro);
            border-color: var(--color-borde);
        }

        .panel .boton-secundario:hover,
        .panel-cabecera .boton-secundario:hover {
            background: var(--color-primario-claro);
            border-color: var(--color-primario);
        }

        .boton-peligro {
            background: var(--color-peligro);
        }

        .boton-peligro:hover {
            background: #8a1d17;
        }

        .boton-pequeno {
            padding: 6px 12px;
            font-size: 0.83rem;
            box-shadow: none;
        }

        /* Tarjetas de contenido / formularios */
        .panel {
            background: var(--color-superficie);
            border: 1px solid var(--color-borde);
            border-top: 3px solid var(--color-primario);
            border-radius: var(--radio);
            box-shadow: var(--sombra);
            padding: 26px 28px;
            margin-top: 24px;
        }

        .panel h1 {
            font-size: 1.4rem;
            margin: 0;
        }

        .panel-cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .formulario {
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-width: 560px;
        }

        .formulario-ancho {
            max-width: none;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        /* El atributo HTML "hidden" necesita más especificidad que
           ".campo { display: flex }" para ocultar el bloque de verdad. */
        .campo[hidden] {
            display: none;
        }

        .campo label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--color-secundario);
        }

        .campo input,
        .campo select,
        .campo textarea {
            padding: 10px 12px;
            border: 1px solid var(--color-borde);
            border-radius: var(--radio-chico);
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--color-superficie);
            color: var(--color-texto);
            transition: border-color var(--transicion), box-shadow var(--transicion);
        }

        .campo input:hover,
        .campo select:hover,
        .campo textarea:hover {
            border-color: #cfc9bd;
        }

        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px var(--color-primario-claro);
        }

        .campo input:disabled {
            background: #f2f1ed;
            color: var(--color-texto-suave);
        }

        .campo textarea {
            resize: vertical;
        }

        .fila-formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        /* Tablas */
        .tabla-envoltura {
            overflow-x: auto;
            margin-top: 16px;
            border: 1px solid var(--color-borde);
            border-radius: var(--radio);
            scrollbar-width: thin;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--color-superficie);
        }

        th, td {
            text-align: left;
            padding: 12px 14px;
            font-size: 0.9rem;
        }

        th {
            background: var(--color-oscuro);
            color: #fff;
            font-weight: 700;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        tbody tr {
            border-bottom: 1px solid var(--color-borde);
            transition: background var(--transicion);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:nth-child(even) {
            background: #faf9f6;
        }

        tbody tr:hover {
            background: var(--color-primario-claro);
        }

        td .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Mensajes / alertas */
        .alerta {
            padding: 12px 16px;
            border-radius: var(--radio-chico);
            margin-bottom: 16px;
            font-size: 0.92rem;
            border-left: 3px solid transparent;
        }

        .alerta-error {
            background: var(--color-peligro-claro);
            color: var(--color-peligro);
            border-left-color: var(--color-peligro);
        }

        .alerta-error::before {
            content: "⚠ ";
        }

        .alerta-exito {
            background: var(--color-exito-claro);
            color: var(--color-exito);
            border-left-color: var(--color-exito);
        }

        .alerta-exito::before {
            content: "✓ ";
        }

        /* Insignias de estado */
        .insignia {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .insignia::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .insignia-activo {
            background: var(--color-exito-claro);
            color: var(--color-exito);
        }

        .insignia-inactivo {
            background: #f0efeb;
            color: var(--color-texto-suave);
        }

        .insignia-rol {
            background: var(--color-primario-claro);
            color: var(--color-primario-oscuro);
        }

        /* Páginas de error */
        .error-pagina {
            text-align: center;
            padding: 72px 20px;
            background: var(--color-superficie);
            border: 1px solid var(--color-borde);
            border-top: 3px solid var(--color-primario);
            border-radius: var(--radio);
            box-shadow: var(--sombra);
            margin-top: 24px;
        }

        .error-pagina h1 {
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .error-pagina p {
            color: var(--color-texto-suave);
            margin-bottom: 20px;
        }

        /* Auth (login/registro) */
        .auth-panel {
            max-width: 420px;
            margin: 56px auto;
            border-top: 3px solid var(--color-primario);
        }

        .auth-panel h1 {
            font-size: 1.4rem;
            margin: 0 0 18px;
            text-align: center;
        }

        .auth-pie {
            margin-top: 16px;
            font-size: 0.9rem;
            text-align: center;
            color: var(--color-texto-suave);
        }

        /* Pie de página */
        .pie {
            margin-top: 40px;
            padding: 20px 16px 36px;
            text-align: center;
            color: var(--color-texto-suave);
            font-size: 0.85rem;
        }

        /* Utilidades */
        .texto-suave {
            color: var(--color-texto-suave);
        }

        .espacio-superior {
            margin-top: 16px;
        }

        /* Responsivo */
        @media (max-width: 640px) {
            .contenedor {
                padding: 20px 14px 40px;
            }

            .nav {
                padding: 12px 16px;
            }

            .landing {
                padding: 32px 22px;
            }

            .landing h1 {
                font-size: 1.7rem;
            }

            .landing-acciones {
                flex-direction: column;
            }

            .landing-acciones .boton {
                width: 100%;
                justify-content: center;
            }

            .panel {
                padding: 20px;
            }

            .auth-panel {
                margin: 24px auto;
            }
        }
    </style>
</head>
<body>
<nav class="nav">
    <span class="nav-marca">Gimnasio</span>
    <div class="nav-enlaces">
        <a href="<?= url('login') ?>">Inicio</a>
        <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
            <a href="<?= url('usuario') ?>">Usuarios</a>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
        <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
            <a href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
            <a href="<?= url('rutina') ?>">Rutinas</a>
        <?php endif; ?>
    </div>
    <div class="nav-enlaces">
        <a href="<?= url('usuario', 'perfil') ?>">Mi perfil</a>
        <a href="<?= url('login', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>
<main class="contenedor">

<?php if ($accion === 'listar'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Ejercicios</h1>
            <a class="boton" href="<?= url('ejercicio', 'crear') ?>">Nuevo ejercicio</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejercicios as $ejercicio): ?>
                        <tr>
                            <td><?= e($ejercicio['nombre']) ?></td>
                            <td><?= e($ejercicio['descripcion'] ?? '') ?></td>
                            <td class="acciones">
                                <a class="boton boton-pequeno boton-secundario" href="<?= url('ejercicio', 'ver', ['id' => $ejercicio['id_ejercicio']]) ?>">Ver</a>
                                <a class="boton boton-pequeno boton-secundario" href="<?= url('ejercicio', 'editar', ['id' => $ejercicio['id_ejercicio']]) ?>">Editar</a>
                                <form method="post" action="<?= url('ejercicio', 'eliminar') ?>" onsubmit="return confirm('¿Eliminar este ejercicio?');">
                                    <input type="hidden" name="id" value="<?= e($ejercicio['id_ejercicio']) ?>">
                                    <button type="submit" class="boton boton-pequeno boton-peligro">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($accion === 'ver'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1><?= e($ejercicio['nombre']) ?></h1>
            <a class="boton boton-secundario" href="<?= url('ejercicio', 'index') ?>">Volver</a>
        </div>

        <p><strong>Descripción:</strong> <?= e($ejercicio['descripcion'] ?? 'Sin descripción') ?></p>
        <p><strong>Beneficio:</strong> <?= e($ejercicio['beneficio'] ?? 'No especificado') ?></p>
        <p><strong>Indicaciones:</strong> <?= e($ejercicio['indicaciones'] ?? 'No especificadas') ?></p>
        <?php if (!empty($ejercicio['url_video'])): ?>
            <p><strong>Video:</strong> <a href="<?= e($ejercicio['url_video']) ?>" target="_blank" rel="noopener">Ver video</a></p>
        <?php endif; ?>

        <p><strong>Grupos musculares:</strong></p>
        <?php if (empty($grupos)): ?>
            <p class="texto-suave">No tiene grupos musculares asociados.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($grupos as $grupo): ?>
                    <li><?= e($grupo['nombre']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <h1>Nuevo ejercicio</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'guardar') ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= e($datos['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3"><?= e($datos['beneficio'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones</label>
                <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($datos['indicaciones'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="url_video">URL de video (opcional)</label>
                <input type="url" id="url_video" name="url_video" value="<?= e($datos['url_video'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Grupos musculares</label>
                <?php foreach ($gruposDisponibles as $grupo): ?>
                    <label>
                        <input type="checkbox" name="grupos[]" value="<?= e($grupo['id_grupo_muscular']) ?>"
                            <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados, true) ? 'checked' : '' ?>>
                        <?= e($grupo['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="boton">Guardar</button>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <h1>Editar ejercicio</h1>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="formulario formulario-ancho" method="post" action="<?= url('ejercicio', 'actualizar') ?>">
            <input type="hidden" name="id" value="<?= e($ejercicio['id_ejercicio']) ?>">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?= e($ejercicio['nombre']) ?>" required>
            </div>
            <div class="campo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3"><?= e($ejercicio['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="beneficio">Beneficio</label>
                <textarea id="beneficio" name="beneficio" rows="3"><?= e($ejercicio['beneficio'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="indicaciones">Indicaciones</label>
                <textarea id="indicaciones" name="indicaciones" rows="3"><?= e($ejercicio['indicaciones'] ?? '') ?></textarea>
            </div>
            <div class="campo">
                <label for="url_video">URL de video (opcional)</label>
                <input type="url" id="url_video" name="url_video" value="<?= e($ejercicio['url_video'] ?? '') ?>">
            </div>
            <div class="campo">
                <label>Grupos musculares</label>
                <?php foreach ($gruposDisponibles as $grupo): ?>
                    <label>
                        <input type="checkbox" name="grupos[]" value="<?= e($grupo['id_grupo_muscular']) ?>"
                            <?= in_array((int) $grupo['id_grupo_muscular'], $gruposSeleccionados, true) ? 'checked' : '' ?>>
                        <?= e($grupo['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="boton">Guardar cambios</button>
        </form>
    </section>

<?php endif; ?>

</main>
<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio</p>
</footer>
</body>
</html>
