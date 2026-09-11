<?php
/**
 * Vista de usuarios (UsuarioController). $accion decide el contenido:
 * listar, crear, editar o perfil.
 */
$usuarioSesion = $_SESSION['user'] ?? null;
$datos = $datos ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Gimnasio</title>
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

        /* Insignias de Rol y Estado */
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

        .insignia-activo {
            background: var(--color-exito-claro);
            color: var(--color-exito);
            border: 1px solid var(--color-exito-borde);
        }

        .insignia-inactivo {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .insignia-rol {
            background: var(--color-primario-suave);
            color: var(--color-primario-hover);
            border: 1px solid var(--color-primario-borde);
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
            gap: 20px;
            margin-top: 16px;
            max-width: 680px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .campo[hidden] {
            display: none;
        }

        .campo label {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--color-texto);
        }

        .campo input[type="text"],
        .campo input[type="email"],
        .campo input[type="password"],
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

        .campo input:disabled {
            background: #f8fafc;
            color: var(--color-texto-suave);
            border-color: var(--color-borde-suave);
            cursor: not-allowed;
        }

        .fila-formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
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

        .alerta-exito {
            background: var(--color-exito-claro);
            color: var(--color-exito);
            border: 1px solid var(--color-exito-borde);
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
        <a href="<?= url('login') ?>">Inicio</a>
        <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
            <a href="<?= url('usuario') ?>" class="<?= $accion === 'listar' || $accion === 'crear' || $accion === 'editar' ? 'activo' : '' ?>">Usuarios</a>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
        <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
            <a href="<?= url('grupoMuscular') ?>">Grupos musculares</a>
            <a href="<?= url('ejercicio') ?>">Ejercicios</a>
            <a href="<?= url('evaluacionFisica', 'registrar') ?>">Registrar evaluación</a>
            <a href="<?= url('rutina') ?>">Rutinas</a>
        <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
            <a href="<?= url('rutina') ?>">Mis rutinas</a>
            <a href="<?= url('evaluacionFisica', 'historial') ?>">Mis evaluaciones</a>
        <?php endif; ?>
    </div>
    <div class="nav-enlaces">
        <a href="<?= url('usuario', 'perfil') ?>" class="<?= $accion === 'perfil' ? 'activo' : '' ?>">Mi perfil</a>
        <a href="<?= url('login', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
    </div>
</nav>
<?php endif; ?>

<main class="contenedor">

<?php if ($accion === 'listar'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Gestión de Usuarios</h1>
            <a class="boton" href="<?= url('usuario', 'crear') ?>">
                <span>+ Crear usuario</span>
            </a>
        </div>

        <div class="tabla-envoltura">
            <table>
                <thead>
                    <tr>
                        <th>C.I.</th>
                        <th>Nombre completo</th>
                        <th>Correo electrónico</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--color-texto);"><?= e($usuario['ci']) ?></td>
                            <td><?= e($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></td>
                            <td style="color: var(--color-texto-suave);"><?= e($usuario['correo']) ?></td>
                            <td><span class="insignia insignia-rol"><?= e(ucfirst($usuario['rol'])) ?></span></td>
                            <td>
                                <?php if ($usuario['estado']): ?>
                                    <span class="insignia insignia-activo">Activo</span>
                                <?php else: ?>
                                    <span class="insignia insignia-inactivo">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="acciones" style="justify-content: flex-end;">
                                    <a class="boton boton-pequeno boton-secundario" href="<?= url('usuario', 'editar', ['id' => $usuario['id_usuario']]) ?>">Editar</a>
                                    <?php if ((int) $usuario['id_usuario'] !== (int) ($_SESSION['user']['id'] ?? 0)): ?>
                                        <form method="post" action="<?= url('usuario', 'cambiarEstado') ?>" style="margin: 0;">
                                            <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">
                                            <input type="hidden" name="estado" value="<?= $usuario['estado'] ? '0' : '1' ?>">
                                            <button type="submit" class="boton boton-pequeno <?= $usuario['estado'] ? 'boton-peligro' : '' ?>">
                                                <?= $usuario['estado'] ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($accion === 'crear'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Crear Nuevo Usuario</h1>
            <a class="boton boton-secundario" href="<?= url('usuario', 'index') ?>">← Volver</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'guardar') ?>">
            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($datos['nombres'] ?? '') ?>" placeholder="Ej: Valentina" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($datos['apellidos'] ?? '') ?>" placeholder="Ej: Gómez Rojas" required>
                </div>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($datos['ci'] ?? '') ?>" placeholder="Número de documento" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($datos['fecha_nacimiento'] ?? '') ?>" required>
                </div>
            </div>

            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($datos['correo'] ?? '') ?>" placeholder="nombre@ejemplo.com" required>
            </div>

            <div class="campo">
                <label for="password">Contraseña inicial</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
            </div>

            <div class="campo">
                <label for="rol">Rol del usuario</label>
                <select id="rol" name="rol" required>
                    <option value="">Selecciona un rol</option>
                    <option value="administrador" <?= ($datos['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="instructor" <?= ($datos['rol'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                    <option value="cliente" <?= ($datos['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                </select>
            </div>

            <div class="campo" id="campo-especialidad" <?= ($datos['rol'] ?? '') === 'instructor' ? '' : 'hidden' ?>>
                <label for="especialidad">Especialidad técnica (Instructor)</label>
                <input type="text" id="especialidad" name="especialidad" value="<?= e($datos['especialidad'] ?? '') ?>" placeholder="Ej: Musculación, Crossfit, Funcional...">
            </div>

            <div style="margin-top: 8px;">
                <button type="submit" class="boton">Guardar usuario</button>
            </div>
        </form>
    </section>

<?php elseif ($accion === 'editar'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Editar Usuario</h1>
            <a class="boton boton-secundario" href="<?= url('usuario', 'index') ?>">← Volver</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'actualizar') ?>">
            <input type="hidden" name="id" value="<?= e($usuario['id_usuario']) ?>">

            <div class="campo">
                <label>Rol de cuenta</label>
                <input type="text" value="<?= e(ucfirst($usuario['rol'])) ?>" disabled>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($usuario['nombres']) ?>" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($usuario['apellidos']) ?>" required>
                </div>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($usuario['ci']) ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>" required>
                </div>
            </div>

            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($usuario['correo']) ?>" required>
            </div>

            <?php if ($usuario['rol'] === 'instructor'): ?>
                <div class="campo">
                    <label for="especialidad">Especialidad</label>
                    <input type="text" id="especialidad" name="especialidad" value="<?= e($especialidad) ?>" required>
                </div>
            <?php endif; ?>

            <div style="margin-top: 8px;">
                <button type="submit" class="boton">Guardar cambios</button>
            </div>
        </form>
    </section>

<?php elseif ($accion === 'perfil'): ?>

    <section class="panel">
        <div class="panel-cabecera">
            <h1>Mi Perfil</h1>
            <span class="insignia insignia-rol">Cuenta: <?= e(ucfirst($usuario['rol'])) ?></span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($exito)): ?>
            <div class="alerta alerta-exito">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                <?= e($exito) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('usuario', 'actualizarPerfil') ?>">
            <div class="campo">
                <label>Rol asignado</label>
                <input type="text" value="<?= e(ucfirst($usuario['rol'])) ?>" disabled>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" value="<?= e($usuario['nombres']) ?>" required>
                </div>
                <div class="campo">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?= e($usuario['apellidos']) ?>" required>
                </div>
            </div>

            <div class="fila-formulario">
                <div class="campo">
                    <label for="ci">Cédula de identidad</label>
                    <input type="text" id="ci" name="ci" value="<?= e($usuario['ci']) ?>" required>
                </div>
                <div class="campo">
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($usuario['fecha_nacimiento']) ?>" required>
                </div>
            </div>

            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?= e($usuario['correo']) ?>" required>
            </div>

            <?php if ($usuario['rol'] === 'cliente'): ?>
                <div class="fila-formulario">
                    <div class="campo">
                        <label for="altura">Estatura / Altura (m)</label>
                        <input type="number" step="0.01" id="altura" name="altura" value="<?= e($clienteInfo['altura'] ?? '') ?>" placeholder="Ej: 1.75">
                    </div>
                    <div class="campo">
                        <label for="peso">Peso actual (kg)</label>
                        <input type="number" step="0.01" id="peso" name="peso" value="<?= e($clienteInfo['peso'] ?? '') ?>" placeholder="Ej: 70.50">
                    </div>
                </div>

            <?php elseif ($usuario['rol'] === 'instructor'): ?>
                <div class="campo">
                    <label for="especialidad">Especialidad deportiva</label>
                    <input type="text" id="especialidad" name="especialidad" value="<?= e($instructorInfo['especialidad'] ?? '') ?>" required>
                </div>
            <?php endif; ?>

            <div style="margin-top: 8px;">
                <button type="submit" class="boton">Guardar cambios</button>
            </div>
        </form>
    </section>

<?php endif; ?>

</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

<script>
    // Control dinámico del campo de especialidad para rol instructor
    var selectRol = document.getElementById('rol');
    var campoEspecialidad = document.getElementById('campo-especialidad');
    if (selectRol && campoEspecialidad) {
        selectRol.addEventListener('change', function () {
            campoEspecialidad.hidden = this.value !== 'instructor';
        });
    }
</script>
</body>
</html>