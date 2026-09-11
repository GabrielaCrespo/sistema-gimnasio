<?php
/**
 * Vista de autenticación (login + registro público de clientes).
 * LoginController decide con $accion cuál de los dos formularios mostrar.
 * No incluye barra de navegación: solo se llega aquí sin sesión activa.
 */
$datos = $datos ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $accion === 'register' ? 'Crear Cuenta' : 'Iniciar Sesión' ?> - Gimnasio</title>
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
            --radio-lg: 22px;
            --radio-md: 12px;
            --radio-sm: 8px;
            --sombra-suave: 0 4px 20px -2px rgba(232, 62, 140, 0.06), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
            --sombra-tarjeta: 0 16px 36px -4px rgba(232, 62, 140, 0.12), 0 4px 14px -2px rgba(0, 0, 0, 0.04);
            --sombra-boton: 0 6px 18px rgba(232, 62, 140, 0.28);
            --transicion: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 50% 0%, #fff0f4 0%, var(--color-fondo) 100%);
            color: var(--color-texto);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        a {
            color: var(--color-primario);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transicion);
        }

        a:hover {
            color: var(--color-primario-hover);
            text-decoration: underline;
        }

        .contenedor {
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 20px 48px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Tarjeta de Autenticación */
        .auth-panel {
            width: 100%;
            background: var(--color-superficie);
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-lg);
            box-shadow: var(--sombra-tarjeta);
            padding: 42px 40px;
            position: relative;
        }

        .auth-panel-login {
            max-width: 440px;
        }

        .auth-panel-registro {
            max-width: 620px;
        }

        /* Encabezado con Ícono */
        .auth-cabecera {
            text-align: center;
            margin-bottom: 28px;
        }

        .auth-marca-icono {
            background: linear-gradient(135deg, #ff85a1 0%, var(--color-primario) 100%);
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(232, 62, 140, 0.3);
            margin-bottom: 14px;
        }

        .auth-cabecera h1 {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--color-texto);
            letter-spacing: -0.02em;
        }

        .auth-cabecera p {
            color: var(--color-texto-suave);
            font-size: 0.92rem;
            margin-top: 4px;
        }

        /* Formularios y Campos */
        .formulario {
            display: flex;
            flex-direction: column;
            gap: 18px;
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

        .campo input {
            padding: 12px 14px;
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-md);
            font-size: 0.94rem;
            font-family: inherit;
            background: #ffffff;
            color: var(--color-texto);
            transition: var(--transicion);
        }

        .campo input:focus {
            outline: none;
            border-color: var(--color-primario);
            box-shadow: 0 0 0 4px var(--color-primario-suave);
        }

        .fila-formulario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        /* Botón de Enviar */
        .boton-auth {
            margin-top: 8px;
            width: 100%;
            padding: 12px 20px;
            border-radius: var(--radio-md);
            background: linear-gradient(135deg, var(--color-primario) 0%, #ff5277 100%);
            color: #ffffff;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            border: none;
            box-shadow: var(--sombra-boton);
            transition: var(--transicion);
        }

        .boton-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(232, 62, 140, 0.38);
        }

        /* Pie de la Tarjeta */
        .auth-pie {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--color-borde-suave);
            font-size: 0.91rem;
            text-align: center;
            color: var(--color-texto-suave);
        }

        .auth-volver {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 0.86rem;
            color: var(--color-texto-suave);
            font-weight: 500;
        }

        .auth-volver:hover {
            color: var(--color-primario);
            text-decoration: none;
        }

        /* Alerta de Error */
        .alerta {
            padding: 12px 16px;
            border-radius: var(--radio-md);
            margin-bottom: 20px;
            font-size: 0.9rem;
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

        /* Pie de Página */
        .pie {
            padding: 24px;
            text-align: center;
            color: var(--color-texto-suave);
            font-size: 0.88rem;
            border-top: 1px solid var(--color-borde-suave);
            background: rgba(255, 255, 255, 0.7);
        }

        @media (max-width: 640px) {
            .auth-panel {
                padding: 30px 20px;
            }
            .fila-formulario {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<main class="contenedor">

<?php if ($accion === 'register'): ?>

    <section class="auth-panel auth-panel-registro">
        <div class="auth-cabecera">
            <span class="auth-marca-icono">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
            </span>
            <h1>Crear cuenta de cliente</h1>
            <p>Empieza a entrenar y da seguimiento a tu progreso físico</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('login', 'crearCuenta') ?>">
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

            <div class="fila-formulario">
                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>
                <div class="campo">
                    <label for="password_confirmacion">Confirmar contraseña</label>
                    <input type="password" id="password_confirmacion" name="password_confirmacion" placeholder="Repite tu contraseña" required minlength="6">
                </div>
            </div>

            <button type="submit" class="boton-auth">Crear cuenta</button>
        </form>

        <p class="auth-pie">¿Ya tienes cuenta? <a href="<?= url('login', 'login') ?>">Inicia sesión</a></p>
        <a href="<?= url('login') ?>" class="auth-volver">← Volver al inicio</a>
    </section>

<?php else: ?>

    <section class="auth-panel auth-panel-login">
        <div class="auth-cabecera">
            <span class="auth-marca-icono">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18 22 4-4"/><path d="m2 6 4-4"/><path d="m3 10 7-7"/><path d="m14 21 7-7"/></svg>
            </span>
            <h1>Iniciar sesión</h1>
            <p>Ingresa tus credenciales para acceder a tu panel</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alerta alerta-error">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="post" action="<?= url('login', 'autenticar') ?>">
            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" placeholder="nombre@ejemplo.com" required autofocus>
            </div>
            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="boton-auth">Ingresar al sistema</button>
        </form>

        <p class="auth-pie">¿No tienes cuenta? <a href="<?= url('login', 'register') ?>">Regístrate como cliente</a></p>
        <a href="<?= url('login') ?>" class="auth-volver">← Volver al inicio</a>
    </section>

<?php endif; ?>

</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>