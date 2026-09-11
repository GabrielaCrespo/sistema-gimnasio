<?php
/**
 * Vista de portada / panel de bienvenida (LoginController::index()).
 * Sin sesión activa muestra un landing público; con sesión activa muestra
 * un panel de bienvenida con accesos rápidos según el rol.
 */
$usuarioSesion = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Gimnasio</title>
    <!-- Tipografía profesional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Paleta Rosa Claro & Sofisticada */
            --color-primario: #e83e8c;         /* Rosa vibrante principal */
            --color-primario-hover: #d62575;   /* Rosa intenso para hover */
            --color-primario-suave: #fdf2f6;   /* Fondo rosa muy suave */
            --color-primario-borde: #fcc2d7;   /* Borde rosa pastel */
            --color-acento: #ff6b8b;           /* Rosa coral sutil */
            
            /* Fondos y Neutros Claros */
            --color-fondo: #faf7f8;            /* Fondo base perla cálido */
            --color-superficie: #ffffff;       /* Blanco puro */
            --color-borde-suave: #f1e4e8;      /* Gris con tinte rosado sutil */
            
            /* Textos */
            --color-texto: #2d242a;            /* Carbón suave cálido (mucho mejor que negro puro) */
            --color-texto-suave: #796670;      /* Malva grisáceo para textos secundarios */
            --color-texto-mutado: #a89aa1;
            
            /* Radios y Sombras Claras */
            --radio-lg: 20px;
            --radio-md: 12px;
            --radio-sm: 8px;
            --sombra-suave: 0 4px 20px -2px rgba(232, 62, 140, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.03);
            --sombra-tarjeta: 0 12px 32px -4px rgba(232, 62, 140, 0.12), 0 4px 12px -2px rgba(0, 0, 0, 0.03);
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

        /* Barra de Navegación Clara */
        .nav {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 36px;
            background: rgba(255, 255, 255, 0.85);
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

        .nav a:hover {
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
            padding: 44px 24px;
            flex: 1;
        }

        /* Landing / Hero Claro */
        .landing {
            position: relative;
            background: #ffffff;
            border-radius: var(--radio-lg);
            padding: 56px 48px;
            overflow: hidden;
            border: 1px solid var(--color-borde-suave);
            box-shadow: var(--sombra-tarjeta);
        }

        /* Destellos decorativos rosas de fondo */
        .landing::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -100px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(255, 133, 161, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .landing::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -80px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(232, 62, 140, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .landing-header {
            position: relative;
            max-width: 620px;
            z-index: 2;
        }

        .landing h1 {
            font-size: 2.35rem;
            margin-bottom: 12px;
            font-weight: 800;
        }

        .landing h1 span {
            background: linear-gradient(135deg, var(--color-primario) 0%, #ff6584 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .landing p {
            color: var(--color-texto-suave);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        /* Chip / Badge del Rol */
        .perfil-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--color-primario-suave);
            border: 1px solid var(--color-primario-borde);
            color: var(--color-primario-hover);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }

        .perfil-badge-punto {
            width: 8px;
            height: 8px;
            background: var(--color-primario);
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(232, 62, 140, 0.6);
        }

        /* Botones */
        .landing-acciones {
            display: flex;
            gap: 14px;
            margin-top: 32px;
            flex-wrap: wrap;
            position: relative;
            z-index: 2;
        }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radio-md);
            background: linear-gradient(135deg, var(--color-primario) 0%, #ff5277 100%);
            color: #ffffff;
            font-size: 0.96rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            box-shadow: var(--sombra-boton);
            transition: var(--transicion);
        }

        .boton:hover {
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(232, 62, 140, 0.38);
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

        /* Tarjetas de Acciones Rápidas Claras */
        .tarjetas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-top: 36px;
            position: relative;
            z-index: 2;
        }

        .tarjeta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 24px;
            background: #ffffff;
            border: 1px solid var(--color-borde-suave);
            border-left: 4px solid var(--color-primario-borde);
            border-radius: var(--radio-md);
            color: var(--color-texto);
            font-weight: 600;
            font-size: 0.98rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            transition: var(--transicion);
        }

        .tarjeta .flecha {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--color-primario-suave);
            color: var(--color-primario);
            font-weight: bold;
            transition: var(--transicion);
        }

        .tarjeta:hover {
            border-color: var(--color-primario-borde);
            border-left-color: var(--color-primario);
            transform: translateY(-3px);
            color: var(--color-primario-hover);
            box-shadow: var(--sombra-tarjeta);
            text-decoration: none;
        }

        .tarjeta:hover .flecha {
            background: var(--color-primario);
            color: #ffffff;
            transform: translateX(4px);
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

        /* Adaptabilidad móvil */
        @media (max-width: 768px) {
            .nav {
                padding: 14px 20px;
            }
            .landing {
                padding: 34px 22px;
            }
            .landing h1 {
                font-size: 1.85rem;
            }
            .landing-acciones {
                flex-direction: column;
            }
            .boton {
                width: 100%;
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
                <a href="<?= url('usuario') ?>">Usuarios</a>
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
            <a href="<?= url('usuario', 'perfil') ?>">Mi perfil</a>
            <a href="<?= url('login', 'logout') ?>" class="nav-salir">Cerrar sesión</a>
        </div>
    </nav>
<?php endif; ?>

<main class="contenedor">
    <section class="landing">
        <?php if ($usuarioSesion): ?>
            <div class="perfil-badge">
                <span class="perfil-badge-punto"></span>
                Rol: <?= e(ucfirst($usuarioSesion['rol'])) ?>
            </div>
            
            <div class="landing-header">
                <h1>Hola de nuevo, <span><?= e($usuarioSesion['nombre']) ?></span> ✨</h1>
                <p>Bienvenido al panel de gestión. Accede rápidamente a tus módulos principales a continuación.</p>
            </div>

            <div class="tarjetas">
                <?php if ($usuarioSesion['rol'] === 'administrador'): ?>
                    <a class="tarjeta" href="<?= url('usuario') ?>">
                        <span>Gestionar usuarios</span>
                        <span class="flecha">→</span>
                    </a>
                    <a class="tarjeta" href="<?= url('grupoMuscular') ?>">
                        <span>Grupos musculares</span>
                        <span class="flecha">→</span>
                    </a>
                    <a class="tarjeta" href="<?= url('ejercicio') ?>">
                        <span>Catálogo de ejercicios</span>
                        <span class="flecha">→</span>
                    </a>
                <?php elseif ($usuarioSesion['rol'] === 'instructor'): ?>
                    <a class="tarjeta" href="<?= url('evaluacionFisica', 'registrar') ?>">
                        <span>Registrar evaluación</span>
                        <span class="flecha">→</span>
                    </a>
                    <a class="tarjeta" href="<?= url('rutina') ?>">
                        <span>Gestionar rutinas</span>
                        <span class="flecha">→</span>
                    </a>
                    <a class="tarjeta" href="<?= url('ejercicio') ?>">
                        <span>Catálogo de ejercicios</span>
                        <span class="flecha">→</span>
                    </a>
                <?php elseif ($usuarioSesion['rol'] === 'cliente'): ?>
                    <a class="tarjeta" href="<?= url('rutina') ?>">
                        <span>Mis rutinas activas</span>
                        <span class="flecha">→</span>
                    </a>
                    <a class="tarjeta" href="<?= url('evaluacionFisica', 'historial') ?>">
                        <span>Historial de evaluaciones</span>
                        <span class="flecha">→</span>
                    </a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="landing-header">
                <h1>Tu bienestar y progreso en <span>un solo lugar</span></h1>
                <p>Una plataforma moderna y sencilla para gestionar rutinas, control de socios, progresos físicos y ejercicios de entrenamiento.</p>
            </div>
            <div class="landing-acciones">
                <a class="boton" href="<?= url('login', 'login') ?>">
                    Iniciar sesión
                </a>
                <a class="boton boton-secundario" href="<?= url('login', 'register') ?>">
                    Crear cuenta
                </a>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>