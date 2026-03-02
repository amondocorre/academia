<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * Auth Controller
 * POST /api/auth/login  → Devuelve JWT
 * GET  /api/auth/me     → Datos del usuario autenticado
 */
class Auth extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper('url');
    }

    /**
     * Inicia sesión y devuelve un JWT.
     * Valida email y password, luego genera el token.
     */
    public function login(): void
    {
        $body = $this->json_body();

        // Validar presencia de campos
        $email    = trim($body['email'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            $this->json_error('Email y contraseña son requeridos', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json_error('Email inválido', 422);
        }

        // Buscar usuario por email
        $usuario = $this->User_model->find_by_email($email);
        if (!$usuario || !password_verify($password, $usuario->password)) {
            $this->json_error('Credenciales incorrectas', 401);
        }

        if (!$usuario->activo) {
            $this->json_error('Tu cuenta está desactivada', 403);
        }

        // Generar JWT con datos mínimos necesarios
        $payload = [
            'sub'    => $usuario->id,
            'nombre' => $usuario->nombre,
            'email'  => $usuario->email,
            'rol'    => $usuario->rol,
        ];
        $token = jwt_encode($payload, self::JWT_SECRET, 1440); // 24 horas

        $this->output->set_status_header(200)->set_output(json_encode([
            'status'  => true,
            'message' => 'Login exitoso',
            'token'   => $token,
            'user'    => [
                'id'              => (int) $usuario->id,
                'nombre'          => $usuario->nombre,
                'email'           => $usuario->email,
                'rol'             => $usuario->rol,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'foto_perfil'     => $usuario->foto_perfil ? base_url($usuario->foto_perfil) : null,
            ],
        ]));
    }

    /**
     * Devuelve los datos del usuario autenticado vía JWT.
     */
    public function me(): void
    {
        $payload = $this->require_auth();
        $usuario = $this->User_model->find($payload['sub']);
        if (!$usuario) $this->json_error('Usuario no encontrado', 404);

        $this->json_ok([
            'id'          => (int) $usuario->id,
            'nombre'      => $usuario->nombre,
            'email'       => $usuario->email,
            'rol'         => $usuario->rol,
            'foto_perfil' => $usuario->foto_perfil ? base_url($usuario->foto_perfil) : null,
        ]);
    }

    /** Logout (el cliente simplemente borra el token) */
    public function logout(): void
    {
        $this->json_ok([], 'Sesión cerrada');
    }

    /** Actualizar foto de perfil del usuario logueado */
    public function update_photo(): void
    {
        $payload = $this->require_auth();
        $user_id = $payload['sub'];

        if (empty($_FILES['foto']['name'])) {
            $this->json_error('No se envió ninguna foto', 422);
        }

        $upload_path = FCPATH . 'uploads/perfiles/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('foto')) {
            $this->json_error($this->upload->display_errors('', ''), 422);
        }

        $data = $this->upload->data();
        $ruta_foto = 'uploads/perfiles/' . $data['file_name'];

        // Obtener usuario actual para borrar foto anterior
        $usuario = $this->User_model->find($user_id);
        if ($usuario && !empty($usuario->foto_perfil) && file_exists(FCPATH . $usuario->foto_perfil)) {
            @unlink(FCPATH . $usuario->foto_perfil);
        }

        $this->User_model->update_user($user_id, ['foto_perfil' => $ruta_foto]);

        $this->json_ok(['foto_perfil' => base_url($ruta_foto)], 'Foto actualizada');
    }
}
