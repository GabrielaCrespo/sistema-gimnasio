<?php

/**
 * EvaluacionFisicaController
 *
 * CU05 - Gestionar evaluación física: registrar evaluaciones (solo
 * instructor) y consultar el historial. El instructor puede consultar el
 * historial de cualquier cliente; el cliente solo el suyo propio.
 */
class EvaluacionFisicaController extends Controller
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
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        $clientes = $this->clienteModelo->listarTodos();
        $this->render('evaluacion_fisica/registrar', ['error' => null, 'datos' => [], 'clientes' => $clientes]);
    }

    /** Procesa el registro de una nueva evaluación física. */
    public function guardar(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor']);

        if (!$request->esPost()) {
            $this->redirect('evaluacionFisica', 'registrar');
            return;
        }

        $datos = [
            'id_cliente' => (int) $request->input('id_cliente', 0),
            'peso' => $request->input('peso', ''),
            'altura' => $request->input('altura', ''),
            'objetivo' => $request->input('objetivo', '') ?: null,
            'porcentaje_grasa' => $request->input('porcentaje_grasa', ''),
            'masa_muscular' => $request->input('masa_muscular', ''),
            'flexibilidad' => $request->input('flexibilidad', ''),
            'observaciones' => $request->input('observaciones', '') ?: null,
        ];

        $error = $this->validar($datos);

        if ($error !== null) {
            $this->render('evaluacion_fisica/registrar', [
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
        $this->clienteModelo->actualizar($datos['id_cliente'], (float) $datos['altura'], (float) $datos['peso']);

        $this->redirect('evaluacionFisica', 'historial', ['id' => $datos['id_cliente']]);
    }

    /**
     * CU05: muestra el historial de evaluaciones. Un instructor puede
     * consultar el de cualquier cliente (elige uno de una lista si no
     * indica id); un cliente solo puede ver el suyo propio, sin importar
     * qué id venga en la URL.
     */
    public function historial(Request $request): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::handle(['instructor', 'cliente']);

        if ($_SESSION['user']['rol'] === 'cliente') {
            $idCliente = (int) $_SESSION['user']['id'];
            $this->render('evaluacion_fisica/historial', [
                'evaluaciones' => $this->evaluacionModelo->listarPorCliente($idCliente),
                'clientes' => null,
                'clienteSeleccionado' => null,
            ]);
            return;
        }

        // Rol instructor: si no se especificó cliente, se muestra el
        // selector en vez de una tabla vacía.
        $idCliente = (int) $request->input('id', 0);
        $clientes = $this->clienteModelo->listarTodos();

        if ($idCliente === 0) {
            $this->render('evaluacion_fisica/historial', [
                'evaluaciones' => [],
                'clientes' => $clientes,
                'clienteSeleccionado' => null,
            ]);
            return;
        }

        $clienteSeleccionado = $this->clienteModelo->buscarPorId($idCliente);
        $evaluaciones = $clienteSeleccionado ? $this->evaluacionModelo->listarPorCliente($idCliente) : [];

        $this->render('evaluacion_fisica/historial', [
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
}
