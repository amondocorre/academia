<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DailyLog Model
 * Maneja registros diarios y evidencias (fotos)
 */
class DailyLog_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Lista registros del hijo, con nombre de materia.
     *
     * @param int         $usuario_id
     * @param string|null $fecha Filtra por fecha específica (Y-m-d)
     */
    public function get_registros(int $usuario_id, ?string $fecha = null): array
    {
        $this->db
            ->select('rd.*, m.nombre AS materia_nombre, m.color_hex')
            ->from('registros_diarios rd')
            ->join('materias m', 'm.id = rd.materia_id')
            ->where('rd.usuario_id', $usuario_id);

        if ($fecha) {
            $this->db->where('rd.fecha', $fecha);
        }

        return $this->db
            ->order_by('rd.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /** Busca un registro por ID */
    public function find(int $id)
    {
        return $this->db
            ->where('id', $id)
            ->limit(1)
            ->get('registros_diarios')
            ->row();
    }

    /**
     * Obtiene registros incompletos de fechas anteriores al día de hoy
     */
    public function get_retrasos(int $usuario_id): array
    {
        return $this->db
            ->select('rd.*, m.nombre AS materia_nombre, m.color_hex')
            ->from('registros_diarios rd')
            ->join('materias m', 'm.id = rd.materia_id')
            ->where('rd.usuario_id', $usuario_id)
            ->where('rd.completado', 0)
            ->where('rd.fecha <', date('Y-m-d'))
            ->order_by('rd.fecha', 'ASC')
            ->get()
            ->result_array();
    }

    /**
     * Verifica si ya existe un registro para (usuario, materia, fecha).
     * Retorna el objeto si existe, null si no.
     */
    public function existe(int $usuario_id, int $materia_id, string $fecha)
    {
        return $this->db
            ->where('usuario_id', $usuario_id)
            ->where('materia_id', $materia_id)
            ->where('fecha', $fecha)
            ->limit(1)
            ->get('registros_diarios')
            ->row();
    }

    /** Crea un nuevo registro y retorna su ID */
    public function crear(array $datos): int
    {
        $this->db->insert('registros_diarios', $datos);
        return $this->db->insert_id();
    }

    /** Actualiza un registro existente */
    public function actualizar(int $id, array $datos): void
    {
        $this->db->where('id', $id)->update('registros_diarios', $datos);
    }

    /** Guarda referencia de una foto en la tabla evidencias */
    public function guardar_evidencia(int $registro_id, string $url_foto, string $tipo = 'tarea'): int
    {
        $this->db->insert('evidencias', [
            'registro_id' => $registro_id,
            'url_foto'    => $url_foto,
            'tipo'        => $tipo
        ]);
        return $this->db->insert_id();
    }

    /**
     * Lista evidencias (fotos) con datos del alumno y materia.
     * Usado por el padre para visualizar galería. Filtrado por sus hijos.
     */
    public function get_evidencias(int $padre_id, ?int $hijo_id = null, ?int $materia_id = null, ?string $fecha_inicio = null, ?string $fecha_fin = null, int $limit = 12, int $offset = 0): array
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

        $this->db
            ->select('e.id, e.url_foto, e.created_at, e.tipo,
                      rd.fecha, rd.usuario_id,
                      m.nombre AS materia_nombre, m.color_hex,
                      u.nombre AS alumno_nombre')
            ->from('evidencias e')
            ->join('registros_diarios rd', 'rd.id = e.registro_id')
            ->join('materias m', 'm.id = rd.materia_id')
            ->join('usuarios u', 'u.id = rd.usuario_id');

        // Filtro por hijos permitidos del padre
        if ($hijo_id) {
            if (in_array($hijo_id, $hijos_ids)) {
                $this->db->where('rd.usuario_id', $hijo_id);
            } else {
                return []; // Intenta buscar un hijo que no es suyo
            }
        } else {
            $this->db->where_in('rd.usuario_id', $hijos_ids);
        }

        if ($materia_id) {
            $this->db->where('rd.materia_id', $materia_id);
        }

        if ($fecha_inicio) {
            $this->db->where('rd.fecha >=', $fecha_inicio);
        }
        
        if ($fecha_fin) {
            $this->db->where('rd.fecha <=', $fecha_fin);
        }

        return $this->db
            ->order_by('rd.fecha', 'DESC')
            ->order_by('e.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->result_array();
    }

    /**
     * Lista evidencias de un registro en específico.
     */
    public function get_evidencias_por_registro(int $registro_id): array
    {
        return $this->db
            ->where('registro_id', $registro_id)
            ->order_by('created_at', 'ASC')
            ->get('evidencias')
            ->result_array();
    }
}
