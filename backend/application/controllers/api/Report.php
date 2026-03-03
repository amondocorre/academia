<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * Report Controller
 * GET /api/report/weekly?offset=0  → Reporte semanal por hijo
 *
 * - offset=0  → semana actual
 * - offset=-1 → semana anterior
 *
 * Devuelve para cada hijo:
 * { completadas, total, porcentaje, registros[] }
 */
class Report extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Report_model', 'User_model']);
    }

    public function weekly(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        // Parameter 'rango' define el filtro de tiempo
        $rango = $this->input->get('rango') ?: 'semana_actual';

        $hoy = new DateTime();

        if ($rango === 'semana_actual' || $rango === 'semana_anterior') {
            $offset = ($rango === 'semana_anterior') ? -1 : 0;
            $ajuste = $offset * 7;
            $ajusteStr = $ajuste >= 0 ? "+{$ajuste} days" : "{$ajuste} days";
            
            $lunes = clone $hoy;
            $lunes->modify("this week monday {$ajusteStr}");
            $domingo = clone $lunes;
            $domingo->modify('+6 days');

            $fecha_inicio = $lunes->format('Y-m-d');
            $fecha_fin    = $domingo->format('Y-m-d');
        } elseif ($rango === 'mes_actual') {
            $inicioMes = clone $hoy;
            $inicioMes->modify('first day of this month');
            $finMes = clone $hoy;
            $finMes->modify('last day of this month');
            
            $fecha_inicio = $inicioMes->format('Y-m-d');
            $fecha_fin    = $finMes->format('Y-m-d');
        } else {
            // Asume que es una fecha específica 'YYYY-MM-DD'
            // Validación básica de fecha
            $fechaObj = DateTime::createFromFormat('Y-m-d', $rango);
            if ($fechaObj && $fechaObj->format('Y-m-d') === $rango) {
                $fecha_inicio = $rango;
                $fecha_fin    = $rango;
            } else {
                // Fallback a hoy si la fecha es inválida
                $fecha_inicio = $hoy->format('Y-m-d');
                $fecha_fin    = $hoy->format('Y-m-d');
            }
        }

        // Obtener hijos del padre
        $hijos = $this->User_model->get_hijos($payload['sub']);

        $reportes = [];
        foreach ($hijos as $hijo) {
            $datos = $this->Report_model->reporte_semanal(
                $hijo['id'],
                $fecha_inicio,
                $fecha_fin
            );

            // Calcular porcentaje
            $total      = (int) $datos['total'];
            $completadas = (int) $datos['completadas'];
            $porcentaje  = $total > 0 ? round(($completadas / $total) * 100) : 0;

            $horario = [];
            if ($fecha_inicio === $fecha_fin) {
                $dia_semana = date('w', strtotime($fecha_inicio));
                $this->db->select('h.id, h.usuario_id, h.materia_id, h.dia_semana, h.hora_inicio, h.hora_fin, m.nombre as materia_nombre, m.color_hex');
                $this->db->from('horarios h');
                $this->db->join('materias m', 'm.id = h.materia_id');
                $this->db->where('h.usuario_id', $hijo['id']);
                $this->db->where('h.dia_semana', $dia_semana);
                $this->db->where('h.activo', 1);
                $this->db->order_by('h.hora_inicio', 'ASC');
                $horario = $this->db->get()->result_array();
            }

            $reportes[$hijo['id']] = [
                'completadas' => $completadas,
                'total'       => $total,
                'porcentaje'  => $porcentaje,
                'registros'   => $datos['registros'],
                'eventos'     => $datos['eventos'],
                'horario'     => $horario,
            ];
        }

        $this->json_ok([
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin'    => $fecha_fin,
            'hijos'        => $hijos,
            'reportes'     => $reportes,
        ]);
    }

    /**
     * Endpoint para obtener el reporte de avance detallado por materia,
     * priorizando visualmente las tareas pendientes.
     * GET /api/report/progreso?hijo_id=1&fecha_inicio=2023-10-01&fecha_fin=2023-10-31
     */
    public function progreso_detallado(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $padre_id = $payload['sub'];

        $hijo_id = $this->input->get('hijo_id');
        $fecha_inicio = $this->input->get('fecha_inicio');
        $fecha_fin = $this->input->get('fecha_fin');

        $hijo_id = is_numeric($hijo_id) && $hijo_id > 0 ? (int)$hijo_id : null;
        
        $registros = $this->Report_model->obtener_progreso_detallado($padre_id, $hijo_id, $fecha_inicio, $fecha_fin);

        $this->json_ok([
            'registros' => $registros
        ]);
    }

    /**
     * Endpoint para obtener el reporte de maestros ausentes.
     * GET /api/report/inasistencias?hijo_id=1&fecha_inicio=2023-10-01&fecha_fin=2023-10-31
     */
    public function inasistencias(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $padre_id = $payload['sub'];

        $hijo_id = $this->input->get('hijo_id');
        $fecha_inicio = $this->input->get('fecha_inicio') ?: null;
        $fecha_fin = $this->input->get('fecha_fin') ?: null;

        $hijo_id = (is_numeric($hijo_id) && $hijo_id > 0) ? (int)$hijo_id : null;

        $registros = $this->Report_model->obtener_inasistencias_profesores($padre_id, $hijo_id, $fecha_inicio, $fecha_fin);

        $this->json_ok(['registros' => $registros]);
    }
}
