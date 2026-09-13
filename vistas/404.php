<?php
/**
 * Página de error 404 (recurso no encontrado).
 *
 * La cargan el front controller (index.php) cuando el controlador o la acción
 * pedidos no existen, y cualquier controlador cuando el id solicitado no
 * corresponde a ningún registro. Quien fija el código HTTP 404 es el
 * controlador; esta vista solo muestra el mensaje.
 *
 * No recibe variables del controlador.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Sistema de Gimnasio</title>
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

            /* Fondos y Neutros Claros */
            --color-fondo: #faf7f8;
            --color-superficie: #ffffff;
            --color-borde-suave: #f1e4e8;

            /* Textos */
            --color-texto: #2d242a;
            --color-texto-suave: #796670;

            /* Radios y Sombras */
            --radio-lg: 22px;
            --radio-md: 12px;
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

        .panel-error {
            width: 100%;
            max-width: 520px;
            text-align: center;
            background: var(--color-superficie);
            border: 1px solid var(--color-borde-suave);
            border-radius: var(--radio-lg);
            box-shadow: var(--sombra-tarjeta);
            padding: 52px 40px;
        }

        .error-icono {
            background: linear-gradient(135deg, #ff85a1 0%, var(--color-primario) 100%);
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(232, 62, 140, 0.3);
            margin-bottom: 18px;
        }

        .error-codigo {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--color-primario);
            margin-bottom: 6px;
        }

        .panel-error h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 10px;
        }

        .panel-error p {
            color: var(--color-texto-suave);
            font-size: 0.95rem;
            margin-bottom: 26px;
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
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            box-shadow: var(--sombra-boton);
            transition: var(--transicion);
        }

        .boton:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(232, 62, 140, 0.38);
        }

        /* Pie de página */
        .pie {
            padding: 24px;
            text-align: center;
            color: var(--color-texto-suave);
            font-size: 0.88rem;
            border-top: 1px solid var(--color-borde-suave);
            background: rgba(255, 255, 255, 0.7);
        }

        @media (max-width: 640px) {
            .panel-error {
                padding: 36px 22px;
            }
        }
    </style>
</head>
<body>

<main class="contenedor">
    <section class="panel-error">
        <span class="error-icono">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <span class="error-codigo">Error 404</span>
        <h1>Página no encontrada</h1>
        <p>La página que buscas no existe o el registro que pediste ya no está disponible.</p>
        <a class="boton" href="<?= '/index.php?' . http_build_query(['controller' => 'login', 'action' => 'index']) ?>">Volver al inicio</a>
    </section>
</main>

<footer class="pie">
    <p>&copy; <?= date('Y') ?> Sistema de Gestión de Gimnasio. Todos los derechos reservados.</p>
</footer>

</body>
</html>
