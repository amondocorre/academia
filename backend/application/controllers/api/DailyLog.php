<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/api/Api_base.php';

/**
 * DailyLog Controller - Registro Diario del Alumno
 *
 * POST /api/dailylog → Crear registro (avance en clase) + subir fotos
 * GET  /api/dailylog → Listar registros del hijo autenticado
 * GET  /api/dailylog/:id → Ver detalle de registro y sus fotos
 * PUT  /api/dailylog/:id → Actualizar texto de un registro existente
 * POST /api/dailylog/:id/tarea → Subir fotos de la tarea terminada y marcar completado
 */
class DailyLog extends Api_base
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('DailyLog_model');
        $this->load->library('upload');
    }

    /**
     * GET /api/dailylog
     * ?fecha=2026-02-23 → filtra por fecha
     */
    public function index(): void
    {
        $payload = $this->require_auth();
        $fecha   = $this->input->get('fecha');

        $registros = $this->DailyLog_model->get_registros(
            $payload['sub'],
            $fecha ?: null
        );
        $this->json_ok($registros);
    }

    /**
     * GET /api/dailylog/retrasos
     * Retorna registros de días anteriores que no están completados
     */
    public function retrasos(): void
    {
        $payload = $this->require_auth();
        $retrasos = $this->DailyLog_model->get_retrasos($payload['sub']);
        $this->json_ok($retrasos);
    }

    /**
     * POST /api/dailylog
     * Recibe multipart/form-data con campos de texto + fotos opcionales.
     * Regla de negocio: no_hubo_tarea=1 || profesor_falto=1 → completado=1
     */
    public function create(): void
    {
        $payload = $this->require_auth();

        // Validar campos requeridos
        $materia_id = $this->input->post('materia_id');
        if (!$materia_id) {
            $this->json_error('El campo materia_id es requerido', 422);
        }

        $no_hubo_tarea  = (bool) $this->input->post('no_hubo_tarea');
        $profesor_falto = (bool) $this->input->post('profesor_falto');
        $avance         = strip_tags($this->input->post('avance_texto') ?? '');
        $tarea          = strip_tags($this->input->post('tarea_descripcion') ?? '');

        // Validar avance solo si no hay excepción marcada
        if (!$no_hubo_tarea && !$profesor_falto && empty(trim($avance))) {
            $this->json_error('Escribe algo sobre tu avance de hoy', 422);
        }

        // Aplicar regla de negocio: auto-completado
        $completado = $no_hubo_tarea || $profesor_falto ? 1 : 0;

        $fecha = date('Y-m-d');

        // Verificar si ya existe un registro para esta materia y fecha
        $existe = $this->DailyLog_model->existe($payload['sub'], $materia_id, $fecha);

        if ($existe) {
            // Actualizar si ya existe
            $this->DailyLog_model->actualizar($existe->id, [
                'avance_texto'      => $avance,
                'tarea_descripcion' => $tarea,
                'no_hubo_tarea'     => $no_hubo_tarea ? 1 : 0,
                'profesor_falto'    => $profesor_falto ? 1 : 0,
                'completado'        => $completado,
            ]);
            $registro_id = $existe->id;
        } else {
            // Crear nuevo
            $registro_id = $this->DailyLog_model->crear([
                'usuario_id'        => $payload['sub'],
                'materia_id'        => (int) $materia_id,
                'fecha'             => $fecha,
                'avance_texto'      => $avance,
                'tarea_descripcion' => $tarea,
                'no_hubo_tarea'     => $no_hubo_tarea ? 1 : 0,
                'profesor_falto'    => $profesor_falto ? 1 : 0,
                'completado'        => $completado,
            ]);
        }

        // Procesar subida de fotos (avance)
        $fotos_guardadas = [];
        if (!empty($_FILES['fotos'])) {
            $fotos_guardadas = $this->_procesar_fotos($registro_id, $_FILES['fotos'], 'avance');
        }

        $this->json_ok([
            'registro_id' => $registro_id,
            'completado'  => (bool) $completado,
            'fotos'       => $fotos_guardadas,
        ], 'Registro guardado correctamente', 201);
    }

    /**
     * PUT /api/dailylog/:id
     * Actualiza texto de un registro existente del hijo.
     */
    public function update(int $id): void
    {
        $payload  = $this->require_auth();
        $registro = $this->DailyLog_model->find($id);

        if (!$registro || (int) $registro->usuario_id !== (int) $payload['sub']) {
            $this->json_error('Registro no encontrado', 404);
        }

        $body = $this->json_body();
        $no_hubo   = isset($body['no_hubo_tarea'])  ? (bool) $body['no_hubo_tarea']  : (bool) $registro->no_hubo_tarea;
        $prof_falt = isset($body['profesor_falto']) ? (bool) $body['profesor_falto'] : (bool) $registro->profesor_falto;

        $this->DailyLog_model->actualizar($id, [
            'avance_texto'      => strip_tags($body['avance_texto'] ?? $registro->avance_texto),
            'tarea_descripcion' => strip_tags($body['tarea_descripcion'] ?? $registro->tarea_descripcion),
            'no_hubo_tarea'     => $no_hubo ? 1 : 0,
            'profesor_falto'    => $prof_falt ? 1 : 0,
            'completado'        => ($no_hubo || $prof_falt) ? 1 : (int) ($body['completado'] ?? $registro->completado),
        ]);

        $this->json_ok([], 'Registro actualizado');
    }

    /**
     * GET /api/dailylog/:id
     * Ver detalles completos del registro (incluye fotos de avance y tarea).
     */
    public function show(int $id): void
    {
        $payload  = $this->require_auth();
        $registro = $this->DailyLog_model->find($id);

        if (!$registro) {
            $this->json_error('Registro no encontrado', 404);
        }

        // Verify permission (child owns it, or parent owns child)
        $tiene_permiso = false;
        if ($payload['rol'] === 'hijo' && (int)$registro->usuario_id === (int)$payload['sub']) {
            $tiene_permiso = true;
        } else if ($payload['rol'] === 'padre') {
            $this->load->model('User_model');
            $hijos = $this->User_model->get_hijos($payload['sub']);
            foreach ($hijos as $h) {
                if ((int)$h['id'] === (int)$registro->usuario_id) {
                    $tiene_permiso = true;
                    break;
                }
            }
        }

        if (!$tiene_permiso) {
            $this->json_error('No tienes permisos para ver este registro', 403);
        }

        // Obtener fotos y separarlas
        $evidencias = $this->DailyLog_model->get_evidencias_por_registro($id);
        $fotos_avance = [];
        $fotos_tarea  = [];
        $fecha_tarea_subida = null;

        foreach ($evidencias as $ev) {
            $url = base_url('uploads/evidencias/' . $ev['url_foto']);
            if ($ev['tipo'] === 'avance') {
                $fotos_avance[] = ['url' => $url];
            } else {
                $fotos_tarea[] = ['url' => $url];
                if (!$fecha_tarea_subida) {
                    $fecha_tarea_subida = $ev['created_at'];
                }
            }
        }

        $this->json_ok([
            'registro'           => $registro,
            'fecha_clase_subida' => $registro->created_at,
            'fecha_tarea_subida' => $fecha_tarea_subida,
            'fotos_avance'       => $fotos_avance,
            'fotos_tarea'        => $fotos_tarea
        ]);
    }

    /**
     * POST /api/dailylog/:id/tarea
     * Sube fotos de la tarea terminada y marca el registro como completado.
     */
    public function upload_homework(int $id): void
    {
        $payload  = $this->require_auth();
        $registro = $this->DailyLog_model->find($id);

        if (!$registro || (int) $registro->usuario_id !== (int) $payload['sub']) {
            $this->json_error('Registro no encontrado', 404);
        }

        if (empty($_FILES['fotos'])) {
            $this->json_error('Debes seleccionar al menos una foto', 422);
        }

        // Procesar fotos y guardar como 'tarea'
        $fotos_guardadas = $this->_procesar_fotos($id, $_FILES['fotos'], 'tarea');

        // Marcar como completado
        $this->DailyLog_model->actualizar($id, ['completado' => 1]);

        $this->json_ok([
            'fotos' => $fotos_guardadas
        ], 'Tarea subida correctamente');
    }

    // -------------------------------------------------------

    /**
     * Procesa y guarda múltiples archivos de imagen.
     * Retorna array de rutas guardadas.
     */
    private function _procesar_fotos(int $registro_id, array $files, string $tipo = 'tarea'): array
    {
        $rutas       = [];
        $directorio  = FCPATH . 'uploads/evidencias/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        // $files asume la estructura reestructurada de PHP para múltiples archivos:
        // $_FILES['fotos']['name'][0], $_FILES['fotos']['type'][0]...
        $nombres = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tipos   = is_array($files['type']) ? $files['type'] : [$files['type']];
        $tmps    = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errores = is_array($files['error']) ? $files['error'] : [$files['error']];

        foreach ($nombres as $i => $nombre) {
            if ($errores[$i] !== UPLOAD_ERR_OK) continue;

            $ext      = pathinfo($nombre, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

            $nuevo    = "ev_{$registro_id}_{$i}_" . time() . '.' . strtolower($ext);
            $destino  = $directorio . $nuevo;

            if (move_uploaded_file($tmps[$i], $destino)) {
                $this->DailyLog_model->guardar_evidencia($registro_id, $nuevo, $tipo);
                $rutas[] = base_url('uploads/evidencias/' . $nuevo);
            }
        }

        return $rutas;
    }
}
