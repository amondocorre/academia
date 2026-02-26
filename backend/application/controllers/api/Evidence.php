<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * Evidence Controller
 * GET /api/evidence → Lista de fotos (solo padre)
 * ?hijo_id=2&page=1&limit=12
 */
class Evidence extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('DailyLog_model');
    }

    public function index(): void
    {
        $payload = $this->require_auth();
        $this->require_rol('padre');
        $padre_id = $payload['sub'];

        $hijo_id      = $this->input->get('hijo_id') ?: null;
        $materia_id   = $this->input->get('materia_id') ?: null;
        $fecha_inicio = $this->input->get('fecha_inicio') ?: null;
        $fecha_fin    = $this->input->get('fecha_fin') ?: null;

        $page    = max(1, (int) ($this->input->get('page') ?? 1));
        $limit   = min(50, (int) ($this->input->get('limit') ?? 12));
        $offset  = ($page - 1) * $limit;

        $lista = $this->DailyLog_model->get_evidencias($padre_id, $hijo_id, $materia_id, $fecha_inicio, $fecha_fin, $limit, $offset);
        $this->json_ok($lista);
    }
}
