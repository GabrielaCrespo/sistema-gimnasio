<?php

/**
 * EvaluacionFisicaController
 *
 * CU05 - Gestionar evaluación física: registrar evaluaciones (solo
 * instructor) y consultar el historial. El instructor puede consultar el
 * historial de cualquier cliente; el cliente solo el suyo propio.
 */
class EvaluacionFisicaController
{
    private EvaluacionFisica $evaluacionModelo;
    private Cliente $clienteModelo;

    public function __construct()
    {
        $this->evaluacionModelo = new EvaluacionFisica();
        $this->clienteModelo = new Cliente();
    }

    /** Muestra el formulario para registrar una evaluación, con la lista de clientes a elegir. */
    public function registrar(): void
    {
        $this->requireRole(['instructor']);

        $clientes = $this->clienteModelo->listarTodos();
        $this->render('registrar', ['error' => null, 'datos' => [], 'clientes' => $clientes]);
    }

    /** Procesa el registro de una nueva evaluación física. */
    public function guardar(): void
    {
        $this->requireRole(['instructor']);

        if (!(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
            $this->redirect('evaluacionFisica', 'registrar');
            return;
        }

        $idCliente = $_POST['id_cliente'] ?? $_GET['id_cliente'] ?? 0;
        if (is_string($idCliente)) { $idCliente = trim($idCliente); }
        $idCliente = (int) $idCliente;
        $peso = $_POST['peso'] ?? $_GET['peso'] ?? '';
        if (is_string($peso)) { $peso = trim($peso); }
        $altura = $_POST['altura'] ?? $_GET['altura'] ?? '';
        if (is_string($altura)) { $altura = trim($altura); }
        $objetivo = $_POST['objetivo'] ?? $_GET['objetivo'] ?? '';
        if (is_string($objetivo)) { $objetivo = trim($objetivo); }
        $porcentajeGrasa = $_POST['porcentaje_grasa'] ?? $_GET['porcentaje_grasa'] ?? '';
        if (is_string($porcentajeGrasa)) { $porcentajeGrasa = trim($porcentajeGrasa); }
        $masaMuscular = $_POST['masa_muscular'] ?? $_GET['masa_muscular'] ?? '';
        if (is_string($masaMuscular)) { $masaMuscular = trim($masaMuscular); }
        $flexibilidad = $_POST['flexibilidad'] ?? $_GET['flexibilidad'] ?? '';
        if (is_string($flexibilidad)) { $flexibilidad = trim($flexibilidad); }
        $observaciones = $_POST['observaciones'] ?? $_GET['observaciones'] ?? '';
        if (is_string($observaciones)) { $observaciones = trim($observaciones); }

        $datos = [
            'id_cliente' => $idCliente,
            'peso' => $peso,
            'altura' => $altura,
            'objetivo' => $objetivo ?: null,
            'porcentaje_grasa' => $porcentajeGrasa,
            'masa_muscular' => $masaMuscular,
            'flexibilidad' => $flexibilidad,
            'observaciones' => $observaciones ?: null,
        ];

        $error = $this->validar($datos);

        if ($error !== null) {
            $this->render('registrar', [
                'error' => $error,
                'datos' => $datos,
                'clientes' => $this->clienteModelo->listarTodos(),
            ]);
            return;
        }

        $this->evaluacionModelo->crear([
            'id_cliente' => $datos['id_cliente'],
            'id_instructor' => (int) $_SESSION['user']['id'],
            'peso' => $datos['peso'],
            'altura' => $datos['altura'],
            'objetivo' => $datos['objetivo'],
            'porcentaje_grasa' => $datos['porcentaje_grasa'] !== '' ? $datos['porcentaje_grasa'] : null,
            'masa_muscular' => $datos['masa_muscular'] !== '' ? $datos['masa_muscular'] : null,
            'flexibilidad' => $datos['flexibilidad'] !== '' ? $datos['flexibilidad'] : null,
            'observaciones' => $datos['observaciones'],
        ]);

        // La evaluación trae el peso/altura más recientes del cliente:
        // se reflejan también en CLIENTE, que es lo que se muestra en su perfil.
        $this->clienteModelo->actualizarMedidas($datos['id_cliente'], (float) $datos['altura'], (float) $datos['peso']);

        $this->redirect('evaluacionFisica', 'historial', ['id' => $datos['id_cliente']]);
    }

    /**
     * CU05: muestra el historial de evaluaciones. Un instructor puede
     * consultar el de cualquier cliente (elige uno de una lista si no
     * indica id); un cliente solo puede ver el suyo propio, sin importar
     * qué id venga en la URL.
     */
    public function historial(): void
    {
        $this->requireRole(['instructor', 'cliente']);

        if ($_SESSION['user']['rol'] === 'cliente') {
            $idCliente = (int) $_SESSION['user']['id'];
            $this->render('historial', [
                'evaluaciones' => $this->evaluacionModelo->listarPorCliente($idCliente),
                'clientes' => null,
                'clienteSeleccionado' => null,
            ]);
            return;
        }

        // Rol instructor: si no se especificó cliente, se muestra el
        // selector en vez de una tabla vacía.
        $idCliente = $_POST['id'] ?? $_GET['id'] ?? 0;
        if (is_string($idCliente)) { $idCliente = trim($idCliente); }
        $idCliente = (int) $idCliente;
        $clientes = $this->clienteModelo->listarTodos();

        if ($idCliente === 0) {
            $this->render('historial', [
                'evaluaciones' => [],
                'clientes' => $clientes,
                'clienteSeleccionado' => null,
            ]);
            return;
        }

        $clienteSeleccionado = $this->clienteModelo->buscarPorId($idCliente);
        $evaluaciones = $clienteSeleccionado ? $this->evaluacionModelo->listarPorCliente($idCliente) : [];

        $this->render('historial', [
            'evaluaciones' => $evaluaciones,
            'clientes' => $clientes,
            'clienteSeleccionado' => $clienteSeleccionado,
        ]);
    }

    /** Valida los campos obligatorios de una evaluación física. */
    private function validar(array $datos): ?string
    {
        if ($datos['id_cliente'] === 0) {
            return 'Selecciona un cliente.';
        }

        if ($datos['peso'] === '' || $datos['altura'] === '') {
            return 'El peso y la altura son obligatorios.';
        }

        if (!is_numeric($datos['peso']) || (float) $datos['peso'] <= 0) {
            return 'El peso debe ser un número mayor a 0.';
        }

        if (!is_numeric($datos['altura']) || (float) $datos['altura'] <= 0) {
            return 'La altura debe ser un número mayor a 0.';
        }

        return null;
    }

    /** Exige sesión activa; si no la hay, redirige al login y detiene la ejecución. */
    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('login', 'login');
        }
    }

    /** Exige sesión activa y que el rol en sesión esté entre los permitidos; si no, corta con 403. */
    private function requireRole(array $rolesPermitidos): void
    {
        $this->requireAuth();

        $rolActual = $_SESSION['user']['rol'] ?? null;

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Sistema de Gestión de Gimnasio</title></head><body style="margin:0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;background:#f6f5f2;color:#24211c;"><main style="max-width:1100px;margin:0 auto;padding:32px 20px 56px;"><section style="text-align:center;padding:72px 20px;background:#fff;border:1px solid #e6e2da;border-top:3px solid #d9782e;border-radius:10px;box-shadow:0 1px 2px rgba(20,15,10,.08);"><h1 style="font-size:1.6rem;margin-bottom:8px;">403 &mdash; Acceso denegado</h1><p style="color:#6c6459;margin-bottom:20px;">No tienes permisos para acceder a esta sección del sistema.</p><a style="display:inline-flex;padding:10px 18px;border-radius:6px;background:#d9782e;color:#fff;font-weight:700;text-decoration:none;" href="/index.php">Volver al inicio</a></section></main></body></html>';
            exit;
        }
    }

    /** Muestra la vista de evaluación física (vistas/evaluacion.php decide el contenido según $accion). */
    private function render(string $accion, array $datos = []): void
    {
        extract($datos);
        require BASE_PATH . '/vistas/evaluacion.php';
    }

    /** Redirige a otra acción interna y detiene la ejecución del script actual. */
    private function redirect(string $controlador, string $accion = 'index', array $parametros = []): void
    {
        header('Location: ' . '/index.php?' . http_build_query(array_merge(['controller' => $controlador, 'action' => $accion], $parametros)));
        exit;
    }
}
