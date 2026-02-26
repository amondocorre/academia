<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * Schedule Controller (Horarios)
 * Sólo el padre puede crear/editar/eliminar.
 * El hijo puede leer su propio horario.
 */
class Schedule extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Schedule_model', 'User_model']);
    }

    /**
     * GET /api/schedule
     * ?hijo_id=2   → horario completo del hijo (padre)
     * ?dia=1       → sólo clases del día (hijo, usa su propio ID)
     */
    public function index(): void
    {
        $payload  = $this->require_auth();
        		$hijo_id  = $this->input->get('hijo_id');
		$dia      = $this->input->get('dia');

		$familia_id = $this->User_model->get_familia_id($payload['sub']);

		if ($payload['rol'] === 'hijo') {
			// El hijo solo ve su propio horario
			$usuario_id = $payload['sub'];
		} else {
			// El padre puede ver el de cualquier hijo/padre de su misma familia
			// Verificar permiso sobre $hijo_id
			if ($hijo_id) {
				$target_familia_id = $this->User_model->get_familia_id($hijo_id);
				if ($target_familia_id !== $familia_id) {
					$this->json_error('No tienes acceso a este horario', 403);
				}
				$usuario_id = $hijo_id;
			} else {
				$usuario_id = null;
			}
		}

		$horarios = $this->Schedule_model->get_horarios($usuario_id, $dia);
        $this->json_ok($horarios);
    }

    /**
     * POST /api/schedule
     * Crea una nueva entrada en el horario (solo padre).
     */
    public function create(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        $body = $this->json_body();
        $campos = ['usuario_id', 'materia_id', 'dia_semana', 'hora_inicio', 'hora_fin'];
        foreach ($campos as $c) {
            if (empty($body[$c]) && $body[$c] !== 0) {
                $this->json_error("Campo requerido: $c", 422);
            }
        }

        $dia = (int) $body['dia_semana'];
        if ($dia < 0 || $dia > 6) $this->json_error('dia_semana debe ser 0-6', 422);

        $id = $this->Schedule_model->crear([
            'usuario_id'  => (int) $body['usuario_id'],
            'materia_id'  => (int) $body['materia_id'],
            'dia_semana'  => $dia,
            'hora_inicio' => $body['hora_inicio'],
            'hora_fin'    => $body['hora_fin'],
        ]);

        $this->json_ok(['id' => $id], 'Horario creado', 201);
    }

    /**
     * PUT /api/schedule/:id
     * Actualiza una entrada del horario (solo padre).
     */
    public function update(int $id): void
    {
        $this->require_auth();
        $this->require_rol('padre');

        $body = $this->json_body();
        $existe = $this->Schedule_model->find($id);
        if (!$existe) $this->json_error('Horario no encontrado', 404);

        $this->Schedule_model->actualizar($id, [
            'materia_id'  => (int) ($body['materia_id'] ?? $existe->materia_id),
            'dia_semana'  => (int) ($body['dia_semana'] ?? $existe->dia_semana),
            'hora_inicio' => $body['hora_inicio'] ?? $existe->hora_inicio,
            'hora_fin'    => $body['hora_fin'] ?? $existe->hora_fin,
        ]);

        $this->json_ok([], 'Horario actualizado');
    }

    /**
     * DELETE /api/schedule/:id
     * Elimina una entrada del horario (solo padre).
     */
    public function destroy(int $id): void
    {
        $this->require_auth();
        $this->require_rol('padre');

        $existe = $this->Schedule_model->find($id);
        if (!$existe) $this->json_error('Horario no encontrado', 404);

        $this->Schedule_model->eliminar($id);
        $this->json_ok([], 'Horario eliminado');
    }

    /** GET /api/schedule/hijos → lista de hijos del padre */
    public function hijos(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $lista = $this->User_model->get_hijos($payload['sub']);
        $this->json_ok($lista);
    }

    /** GET /api/schedule/materias → catálogo de materias */
    public function materias(): void
    {
        $this->require_auth();
        $lista = $this->Schedule_model->get_materias();
        $this->json_ok($lista);
    }

    /** GET /api/schedule/materias-hoy → materias del hijo para el día actual */
    public function materias_hoy(): void
    {
        $payload = $this->require_auth();
        $dia     = (int) date('w'); // 0=Dom … 6=Sáb
        $lista   = $this->Schedule_model->get_horarios($payload['sub'], $dia);
        $this->json_ok($lista);
    }

    /**
     * POST /api/schedule/materias
     * Crea una nueva materia (solo padre).
     */
    public function create_materia(): void
    {
        $this->require_auth();
        $this->require_rol('padre');

        $body = $this->json_body();
        if (empty($body['nombre'])) {
            $this->json_error("Campo requerido: nombre", 422);
        }

        $id = $this->Schedule_model->crear_materia([
            'nombre'    => $body['nombre'],
            'color_hex' => $body['color_hex'] ?? '#3b82f6', // Color por defecto si no lo envían
        ]);

        $this->json_ok(['id' => $id], 'Materia creada', 201);
    }

    /**
     * PUT /api/schedule/materias/:id
     * Actualiza una materia (solo padre).
     */
    public function update_materia(int $id): void
    {
        $this->require_auth();
        $this->require_rol('padre');

        $body = $this->json_body();
        $existe = $this->Schedule_model->find_materia($id);
        if (!$existe) $this->json_error('Materia no encontrada', 404);

        $this->Schedule_model->actualizar_materia($id, [
            'nombre'    => $body['nombre'] ?? $existe->nombre,
            'color_hex' => $body['color_hex'] ?? $existe->color_hex,
        ]);

        $this->json_ok([], 'Materia actualizada');
    }

    /**
     * DELETE /api/schedule/materias/:id
     * Elimina una materia (solo padre).
     */
    public function destroy_materia(int $id): void
    {
        $this->require_auth();
        $this->require_rol('padre');

        $existe = $this->Schedule_model->find_materia($id);
        if (!$existe) $this->json_error('Materia no encontrada', 404);

        $this->Schedule_model->eliminar_materia($id);
        $this->json_ok([], 'Materia eliminada');
    }
}
