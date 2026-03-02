<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * Events Controller - Calendario de exámenes y exposiciones
 */
class Events extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper(['url', 'file']);
    }

    /** GET /api/events → próximos 5 eventos del usuario autenticado (pendientes) */
    public function index(): void
    {
        $payload = $this->require_auth();
        $hoy     = date('Y-m-d');

        $eventos = $this->db
            ->where('usuario_id', $payload['sub'])
            ->where('fecha >=', $hoy)
            ->where('estado', 'pendiente')  // Ocultar los concluidos de la pantalla inicial
            ->order_by('fecha', 'ASC')
            ->limit(5)
            ->get('calendario_eventos')
            ->result_array();

        foreach ($eventos as &$evento) {
            if (!empty($evento['imagen'])) {
                $evento['imagen'] = base_url($evento['imagen']);
            }
        }

        $this->json_ok($eventos);
    }

    /** GET /api/events/history → todos los eventos del usuario autenticado (histórico para el hijo) */
    public function history(): void
    {
        $payload = $this->require_auth();
        $eventos = $this->db
            ->where('usuario_id', $payload['sub'])
            ->order_by('fecha', 'DESC')
            ->get('calendario_eventos')
            ->result_array();
            
        foreach ($eventos as &$evento) {
            if (!empty($evento['imagen'])) {
                $evento['imagen'] = base_url($evento['imagen']);
            }
        }

        $this->json_ok($eventos);
    }
    /** POST /api/events → crear evento (padre o hijo) */
    public function create(): void
    {
        $payload = $this->require_auth();

        // Leemos de $_POST dado que puede ser form-data, o del payload JSON
        $is_multipart = !empty($_POST) || !empty($_FILES);

        if ($is_multipart) {
            $titulo = strip_tags(trim($this->input->post('titulo') ?? ''));
            $fecha  = $this->input->post('fecha') ?? '';
            $tipo   = $this->input->post('tipo') ?? '';
            $descripcion = strip_tags($this->input->post('descripcion') ?? '');
        } else {
            $body = $this->json_body();
            $titulo = strip_tags(trim($body['titulo'] ?? ''));
            $fecha  = $body['fecha'] ?? '';
            $tipo   = $body['tipo'] ?? '';
            $descripcion = strip_tags($body['descripcion'] ?? '');
        }

        if (empty($titulo) || empty($fecha) || !in_array($tipo, ['examen', 'exposicion'])) {
            $this->json_error('Faltan campos requeridos: titulo, fecha, tipo', 422);
        }

        $imagen = $this->handle_upload('imagen');

        $this->db->insert('calendario_eventos', [
            'usuario_id'  => $payload['sub'],
            'titulo'      => $titulo,
            'descripcion' => $descripcion,
            'fecha'       => $fecha,
            'tipo'        => $tipo,
            'imagen'      => $imagen
        ]);

        $this->json_ok([
            'id' => $this->db->insert_id(),
            'imagen' => $imagen ? base_url($imagen) : null
        ], 'Evento creado', 201);
    }

    /** DELETE /api/events/:id */
    public function destroy(int $id): void
    {
        $payload = $this->require_auth();

        $evento = $this->db
            ->where('id', $id)
            ->where('usuario_id', $payload['sub'])
            ->get('calendario_eventos')
            ->row();

        if (!$evento) $this->json_error('Evento no encontrado', 404);

        if (!empty($evento->imagen) && file_exists(FCPATH . $evento->imagen)) {
            @unlink(FCPATH . $evento->imagen);
        }

        $this->db->where('id', $id)->delete('calendario_eventos');
        $this->json_ok([], 'Evento eliminado');
    }

    /**
     * Sube archivo y devuelve path relativo
     */
    private function handle_upload(string $field_name): ?string
    {
        if (empty($_FILES[$field_name]['name'])) return null;

        $upload_path = FCPATH . 'uploads/eventos/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = '*';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload($field_name)) {
            $this->json_error($this->upload->display_errors('', ''), 422);
        }

        $data = $this->upload->data();
        return 'uploads/eventos/' . $data['file_name'];
    }

    /** GET /api/events/padre → Eventos de los hijos del padre autenticado (filtrable) */
    public function padre(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $padre_id = $payload['sub'];

        $hijo_id      = $this->input->get('hijo_id') ?: null;
        $fecha_inicio = $this->input->get('fecha_inicio') ?: null;
        $fecha_fin    = $this->input->get('fecha_fin') ?: null;

        $this->load->model('User_model');
        $hijos = $this->User_model->get_hijos($padre_id);
        $hijos_ids = array_column($hijos, 'id');

        if (empty($hijos_ids)) {
            $this->json_ok([]);
            return;
        }

        $this->db->select('ce.*, u.nombre as hijo_nombre')
                 ->from('calendario_eventos ce')
                 ->join('usuarios u', 'u.id = ce.usuario_id');

        if ($hijo_id) {
            if (in_array($hijo_id, $hijos_ids)) {
                $this->db->where('ce.usuario_id', $hijo_id);
            } else {
                $this->json_ok([]); // Intenta acceder al hijo de otro
                return;
            }
        } else {
            $this->db->where_in('ce.usuario_id', $hijos_ids);
        }

        if ($fecha_inicio) $this->db->where('ce.fecha >=', $fecha_inicio);
        if ($fecha_fin)    $this->db->where('ce.fecha <=', $fecha_fin);

        $eventos = $this->db->order_by('ce.fecha', 'DESC')->get()->result_array();

        foreach ($eventos as &$evento) {
            if (!empty($evento['imagen'])) {
                $evento['imagen'] = base_url($evento['imagen']);
            }
        }

        $this->json_ok($eventos);
    }

    /** PUT /api/events/estado/:id → Cambiar estado de un evento a concluido/pendiente */
    public function cambiar_estado(int $id): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $padre_id = $payload['sub'];

        $json = json_decode(file_get_contents('php://input'), true);
        $nuevo_estado = $json['estado'] ?? '';

        if (!in_array($nuevo_estado, ['pendiente', 'concluido'])) {
            $this->json_error('Estado inválido', 400);
        }

        // Verificar que el evento pertenece a uno de los hijos del padre
        $this->load->model('User_model');
        $hijos = $this->User_model->get_hijos($padre_id);
        $hijos_ids = array_column($hijos, 'id');

        $evento = $this->db->where('id', $id)->get('calendario_eventos')->row();
        if (!$evento) $this->json_error('Evento no encontrado', 404);
        if (!in_array($evento->usuario_id, $hijos_ids)) $this->json_error('No autorizado', 403);

        $this->db->where('id', $id)->update('calendario_eventos', ['estado' => $nuevo_estado]);
        $this->json_ok([], 'Estado actualizado');
    }
}
