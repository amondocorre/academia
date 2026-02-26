<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report Model
 * Cálculo de porcentaje de cumplimiento semanal
 */
class Report_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Calcula el reporte semanal de un hijo.
     *
     * Lógica:
     * - "total" = número de clases en el horario × días del rango que correspondan
     * - "completadas" = registros con completado=1 en el período
     *
     * @return array { total, completadas, registros[] }
     */
    public function reporte_semanal(int $usuario_id, string $fecha_inicio, string $fecha_fin): array
    {
        // Días de la semana en el rango (para cruzar con horario)
        $dias_rango = $this->_dias_semana_en_rango($fecha_inicio, $fecha_fin);

        // Contar clases esperadas en el horario para esos días
        $total = 0;
        foreach ($dias_rango as $dia) {
            $count = $this->db
                ->where('usuario_id', $usuario_id)
                ->where('dia_semana', $dia)
                ->where('activo', 1)
                ->count_all_results('horarios');
            $total += $count;
        }

        // Registros completados en el período
        $completadas = $this->db
            ->where('usuario_id', $usuario_id)
            ->where('completado', 1)
            ->where('fecha >=', $fecha_inicio)
            ->where('fecha <=', $fecha_fin)
            ->count_all_results('registros_diarios');

        // Registros recientes con detalle (para mostrar en la tarjeta)
        $registros = $this->db
            ->select('rd.id, rd.fecha, rd.completado, rd.no_hubo_tarea, rd.profesor_falto,
                      rd.avance_texto, rd.tarea_descripcion,
                      m.nombre AS materia_nombre, m.color_hex')
            ->from('registros_diarios rd')
            ->join('materias m', 'm.id = rd.materia_id')
            ->where('rd.usuario_id', $usuario_id)
            ->where('rd.fecha >=', $fecha_inicio)
            ->where('rd.fecha <=', $fecha_fin)
            ->order_by('rd.fecha', 'DESC')
            ->limit(5)
            ->get()
            ->result_array();

        // Próximos eventos (máximo 3 para el dashboard)
        $hoy = date('Y-m-d');
        $eventos = $this->db
            ->select('id, titulo, tipo, fecha')
            ->from('calendario_eventos')
            ->where('usuario_id', $usuario_id)
            ->where('fecha >=', $hoy)
            ->order_by('fecha', 'ASC')
            ->limit(3)
            ->get()
            ->result_array();

        return [
            'total'       => $total,
            'completadas' => $completadas,
            'registros'   => $registros,
            'eventos'     => $eventos,
        ];
    }

    /**
     * Retorna los números de día de semana (0-6) presentes en el rango de fechas.
     * Solo considera Lunes-Viernes (1-5).
     */
    private function _dias_semana_en_rango(string $fecha_inicio, string $fecha_fin): array
    {
        $dias       = [];
        $current    = new DateTime($fecha_inicio);
        $fin        = new DateTime($fecha_fin);

        while ($current <= $fin) {
            $dia = (int) $current->format('w'); // 0=Dom, 5=Vie
            if ($dia >= 1 && $dia <= 5) {
                $dias[] = $dia;
            }
            $current->modify('+1 day');
        }

        return $dias;
    }

    /**
     * Obtiene el listado detallado de progresos, agrupando todas las actividades y 
     * priorizando visualmente las tareas pendientes.
     */
    public function obtener_progreso_detallado(int $padre_id, ?int $hijo_id, ?string $fecha_inicio, ?string $fecha_fin): array
    {
        $this->db->select('rd.id, rd.fecha, rd.completado, rd.no_hubo_tarea, rd.profesor_falto, 
                           rd.avance_texto, rd.tarea_descripcion,
                           m.nombre AS materia_nombre, m.color_hex,
                           u.id AS hijo_id, u.nombre AS hijo_nombre');
        $this->db->from('registros_diarios rd');
        $this->db->join('materias m', 'm.id = rd.materia_id');
        $this->db->join('usuarios u', 'u.id = rd.usuario_id');
        // Validar que el hijo pertenece al padre logueado
        $this->db->where('u.padre_id', $padre_id);

        if ($hijo_id) {
            $this->db->where('rd.usuario_id', $hijo_id);
        }

        if ($fecha_inicio && $fecha_fin) {
            $this->db->where('rd.fecha >=', $fecha_inicio);
            $this->db->where('rd.fecha <=', $fecha_fin);
        }

        // Ordenar primero por completado ascendente (0=Pendiente, 1=Completado)
        // luego por fecha descendente (lo más reciente primero)
        $this->db->order_by('rd.completado', 'ASC');
        $this->db->order_by('rd.fecha', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Obtiene el reporte de materias donde el profesor faltó.
     * Filtra por padre_id, o por un hijo en específico, y por rango de fechas.
     */
    public function obtener_inasistencias_profesores(int $padre_id, ?int $hijo_id, ?string $fecha_inicio, ?string $fecha_fin): array
    {
        // 1. Obtener la lista de hijos (id) asociados al padre para evitar ver registros de otros
        $this->db->select('id');
        $this->db->where('padre_id', $padre_id);
        $this->db->where('rol', 'hijo');
        $hijos_query = $this->db->get('usuarios')->result_array();
        
        $hijos_ids = array_column($hijos_query, 'id');
        if (empty($hijos_ids)) {
            return []; // El padre no tiene hijos registrados
        }

        // 2. Construir la consulta principal
        $this->db->select('
            rd.id, 
            rd.fecha, 
            rd.avance_texto, 
            rd.tarea_descripcion, 
            rd.completado, 
            rd.no_hubo_tarea,
            rd.profesor_falto,
            rd.usuario_id as hijo_id,
            m.nombre as materia_nombre, 
            m.color_hex,
            u.nombre as hijo_nombre
        ');
        $this->db->from('registros_diarios rd');
        $this->db->join('materias m', 'm.id = rd.materia_id');
        $this->db->join('usuarios u', 'u.id = rd.usuario_id');
        
        // Filtro por hijos permitidos del padre
        if ($hijo_id) {
            if (in_array($hijo_id, $hijos_ids)) {
                $this->db->where('rd.usuario_id', $hijo_id);
            } else {
                return []; // Si intenta buscar un hijo que no es suyo
            }
        } else {
            $this->db->where_in('rd.usuario_id', $hijos_ids);
        }

        // Filtro de profesor faltó
        $this->db->where('rd.profesor_falto', 1);

        // Filtros de fecha
        if ($fecha_inicio) $this->db->where('rd.fecha >=', $fecha_inicio);
        if ($fecha_fin) $this->db->where('rd.fecha <=', $fecha_fin);

        // Ordenamos por fecha descendente
        $this->db->order_by('rd.fecha', 'DESC');
        
        return $this->db->get()->result_array();
    }
}
