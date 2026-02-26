<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Schedule Model
 * Maneja horarios y materias
 */
class Schedule_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Obtiene horarios con datos de la materia.
     *
     * @param int|null $usuario_id Si null, devuelve todos
     * @param int|null $dia_semana  Si null, devuelve todos los días
     */
    public function get_horarios(?int $usuario_id, ?int $dia_semana = null): array
    {
        $this->db
            ->select('h.id, h.usuario_id, h.materia_id, h.dia_semana, h.hora_inicio, h.hora_fin, m.nombre, m.color_hex')
            ->from('horarios h')
            ->join('materias m', 'm.id = h.materia_id')
            ->where('h.activo', 1);

        if ($usuario_id !== null) {
            $this->db->where('h.usuario_id', $usuario_id);
        }
        if ($dia_semana !== null) {
            $this->db->where('h.dia_semana', $dia_semana);
        }

        return $this->db
            ->order_by('h.hora_inicio', 'ASC')
            ->get()
            ->result_array();
    }

    /** Busca un horario por ID */
    public function find(int $id)
    {
        return $this->db
            ->where('id', $id)
            ->where('activo', 1)
            ->get('horarios')
            ->row();
    }

    /** Crea un nuevo horario y retorna el ID */
    public function crear(array $datos): int
    {
        $this->db->insert('horarios', $datos);
        return $this->db->insert_id();
    }

    /** Actualiza un horario existente */
    public function actualizar(int $id, array $datos): void
    {
        $this->db->where('id', $id)->update('horarios', $datos);
    }

    /** Soft-delete de un horario */
    public function eliminar(int $id): void
    {
        $this->db->where('id', $id)->update('horarios', ['activo' => 0]);
    }

    /** Catálogo completo de materias */
    public function get_materias(): array
    {
        return $this->db
            ->where('activo', 1)
            ->order_by('nombre', 'ASC')
            ->get('materias')
            ->result_array();
    }

    /** Busca una materia por ID */
    public function find_materia(int $id)
    {
        return $this->db
            ->where('id', $id)
            ->where('activo', 1)
            ->get('materias')
            ->row();
    }

    /** Crea una nueva materia y retorna el ID */
    public function crear_materia(array $datos): int
    {
        $this->db->insert('materias', $datos);
        return $this->db->insert_id();
    }

    /** Actualiza una materia existente */
    public function actualizar_materia(int $id, array $datos): void
    {
        $this->db->where('id', $id)->update('materias', $datos);
    }

    /** Soft-delete de una materia */
    public function eliminar_materia(int $id): void
    {
        $this->db->where('id', $id)->update('materias', ['activo' => 0]);
    }
}
