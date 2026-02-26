<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Model
 * Maneja la tabla `usuarios`
 */
class User_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /** Busca un usuario por email */
    public function find_by_email(string $email)
    {
        return $this->db
            ->where('email', $email)
            ->limit(1)
            ->get('usuarios')
            ->row();
    }

    /** Busca un usuario por ID */
    public function find(int $id)
    {
        return $this->db
            ->where('id', $id)
            ->limit(1)
            ->get('usuarios')
            ->row();
    }

    /**
     * Retorna los hijos asociados a un padre.
     * Devuelve array de arrays con: id, nombre, email, fecha_nacimiento
     */
    public function get_hijos(int $usuario_id): array
    {
        $familia_id = $this->get_familia_id($usuario_id);

        return $this->db
            ->select('id, nombre, email, fecha_nacimiento, foto_perfil')
            ->where('padre_id', $familia_id)
            ->where('rol', 'hijo')
            ->where('activo', 1)
            ->order_by('fecha_nacimiento', 'DESC') // más joven primero
            ->get('usuarios')
            ->result_array();
    }

    /**
     * Obtiene el ID principal de la familia (el padre_id si es un secundario, o su propio ID si es el primario)
     */
    public function get_familia_id(int $user_id): int
    {
        $user = $this->find($user_id);
        if (!$user) return $user_id;

        // Si ya tiene un padre_id asignado, esa es la "cabeza" de la familia
        if (!empty($user->padre_id)) {
            return (int) $user->padre_id;
        }

        // Si no tiene, él mismo es la cabeza de la familia
        return $user_id;
    }

    /**
     * Retorna los familiares asociados a una cuenta principal (Padres e Hijos).
     * Excluye al propio usuario que hace la solicitud para que no se edite a sí mismo desde el subgestor.
     */
    public function get_familiares(int $familia_id, int $exclude_user_id): array
    {
        // Un familiar pertenece a esta familia si su ID es el familia_id, o su padre_id es el familia_id.
        return $this->db
            ->select('id, nombre, email, fecha_nacimiento, rol, foto_perfil')
            ->group_start()
                ->where('id', $familia_id)
                ->or_where('padre_id', $familia_id)
            ->group_end()
            ->where('id !=', $exclude_user_id)
            ->where('activo', 1)
            ->order_by('rol', 'ASC') // padres primero, luego hijos ('hijo' > 'padre' alfabéticamente)
            ->order_by('fecha_nacimiento', 'DESC')
            ->get('usuarios')
            ->result_array();
    }
    /**
     * Crea un nuevo usuario y retorna su ID
     */
    public function create_user(array $datos): int
    {
        $this->db->insert('usuarios', $datos);
        return $this->db->insert_id();
    }

    /**
     * Actualiza un usuario
     */
    public function update_user(int $id, array $datos): void
    {
        $this->db->where('id', $id)->update('usuarios', $datos);
    }

    /**
     * Soft-delete de un usuario
     */
    public function delete_user(int $id): void
    {
        $this->db->where('id', $id)->update('usuarios', ['activo' => 0]);
    }
}
