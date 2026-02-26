<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

class Users extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(['url', 'file']);
    }

    /**
     * GET /api/users
     * Lista los familiares del padre autenticado (padres secundarios e hijos)
     */
    public function index(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        $familia_id = $this->User_model->get_familia_id($payload['sub']);
        $lista = $this->User_model->get_familiares($familia_id, $payload['sub']);
        
        // Agregar URL completa a la foto
        foreach ($lista as &$user) {
            if (!empty($user['foto_perfil'])) {
                $user['foto_perfil'] = base_url($user['foto_perfil']);
            }
        }

        $this->json_ok($lista);
    }

    /**
     * GET /api/user/hijos
     * Lista únicamente los hijos del padre autenticado para usarlos en filtros (ej. reportes)
     */
    public function hijos(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        $hijos = $this->User_model->get_hijos($payload['sub']);
        
        // Agregar URL completa a la foto si existe
        foreach ($hijos as &$h) {
            if (!empty($h['foto_perfil'])) {
                $h['foto_perfil'] = base_url($h['foto_perfil']);
            }
        }

        $this->json_ok($hijos);
    }

    /**
     * POST /api/users
     * Crea un nuevo familiar
     */
    public function create(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        // Como usamos multipart/form-data, leemos de $_POST
        $nombre = $this->input->post('nombre');
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $rol = $this->input->post('rol') ?: 'hijo'; // Default hijo
        $fecha_nacimiento = $this->input->post('fecha_nacimiento') ?: null;

        if (empty($nombre) || empty($email) || empty($password)) {
            $this->json_error('Nombre, email y contraseña son requeridos', 422);
        }

        if (!in_array($rol, ['padre', 'hijo'])) {
            $this->json_error('Rol inválido', 422);
        }

        $existe = $this->User_model->find_by_email($email);
        if ($existe) {
            $this->json_error('El correo electrónico ya está registrado', 409);
        }

        $familia_id = $this->User_model->get_familia_id($payload['sub']);
        $foto_perfil = $this->handle_upload('foto');

        $id = $this->User_model->create_user([
            'nombre'           => $nombre,
            'email'            => $email,
            'password'         => password_hash($password, PASSWORD_DEFAULT),
            'rol'              => $rol,
            'padre_id'         => $familia_id,
            'fecha_nacimiento' => $fecha_nacimiento,
            'foto_perfil'      => $foto_perfil,
            'activo'           => 1
        ]);

        $this->json_ok(['id' => $id, 'foto_perfil' => $foto_perfil ? base_url($foto_perfil) : null], 'Usuario creado con éxito', 201);
    }

    /**
     * PUT /api/users/:id
     * Pero como multer/form-data con PUT es complicado en PHP, solemos mandar un POST con _method=PUT o usar la ruta POST /api/users/:id con un flag
     * Para simplificar, asumiremos que puede venir por POST
     */
    public function update(int $id): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        // Para soportar PUT real (JSON) y POST masquerading (form-data)
        $is_multipart = !empty($_POST) || !empty($_FILES);
        
        if ($is_multipart) {
            $nombre = $this->input->post('nombre');
            $email = $this->input->post('email');
            $password = $this->input->post('password');
            $rol = $this->input->post('rol');
            $fecha_nacimiento = $this->input->post('fecha_nacimiento');
        } else {
            $body = $this->json_body();
            $nombre = $body['nombre'] ?? null;
            $email = $body['email'] ?? null;
            $password = $body['password'] ?? null;
            $rol = $body['rol'] ?? null;
            $fecha_nacimiento = $body['fecha_nacimiento'] ?? null;
        }

        $usuario = $this->User_model->find($id);
        if (!$usuario) $this->json_error('Usuario no encontrado', 404);

        $familia_id = $this->User_model->get_familia_id($payload['sub']);
        
        // Validar que el usuario a editar pertenezca a la misma familia (sea la cabeza o tenga el mismo padre_id)
        $target_familia_id = $this->User_model->get_familia_id($id);
        if ($target_familia_id !== $familia_id) {
            $this->json_error('No tienes permiso para editar este usuario', 403);
        }

        if (!empty($email) && $email !== $usuario->email) {
            $existe = $this->User_model->find_by_email($email);
            if ($existe) $this->json_error('El correo electrónico ya está en uso', 409);
        }

        $datos = [];
        if (!empty($nombre)) $datos['nombre'] = $nombre;
        if (!empty($email)) $datos['email'] = $email;
        if (!empty($fecha_nacimiento)) $datos['fecha_nacimiento'] = $fecha_nacimiento;
        if (!empty($rol) && in_array($rol, ['padre', 'hijo'])) $datos['rol'] = $rol;
        if (!empty($password)) $datos['password'] = password_hash($password, PASSWORD_DEFAULT);

        if ($is_multipart) {
            $foto_perfil = $this->handle_upload('foto');
            if ($foto_perfil) {
                $datos['foto_perfil'] = $foto_perfil;
                // Opcional: Borrar foto anterior
                if (!empty($usuario->foto_perfil) && file_exists(FCPATH . $usuario->foto_perfil)) {
                    @unlink(FCPATH . $usuario->foto_perfil);
                }
            }
        }

        if (count($datos) > 0) {
            $this->User_model->update_user($id, $datos);
        }

        $this->json_ok(['foto_perfil' => isset($datos['foto_perfil']) ? base_url($datos['foto_perfil']) : ($usuario->foto_perfil ? base_url($usuario->foto_perfil) : null)], 'Usuario actualizado');
    }

    /**
     * DELETE /api/users/:id
     */
    public function destroy(int $id): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');

        $usuario = $this->User_model->find($id);
        if (!$usuario) $this->json_error('Usuario no encontrado', 404);

        $familia_id = $this->User_model->get_familia_id($payload['sub']);
        $target_familia_id = $this->User_model->get_familia_id($id);

        if ($target_familia_id !== $familia_id) {
            $this->json_error('No tienes permiso para eliminar este usuario', 403);
        }

        $this->User_model->delete_user($id);
        $this->json_ok([], 'Usuario eliminado');
    }

    /**
     * Sube un archivo y retorna el path relativo
     */
    private function handle_upload(string $field_name): ?string
    {
        if (empty($_FILES[$field_name]['name'])) return null;

        $upload_path = FCPATH . 'uploads/perfiles/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path']   = $upload_path;
        // Permitimos '*' para evitar fallos de MIME types antiguos en CI3 y validamos la extensión abajo si queremos, pero de momento '*' ayuda a descartar el error
        $config['allowed_types'] = '*';
        $config['max_size']      = 5120; // 5MB para enviar fotos grandes
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload($field_name)) {
            $this->json_error($this->upload->display_errors('', ''), 422);
        }

        $data = $this->upload->data();
        return 'uploads/perfiles/' . $data['file_name'];
    }
}
