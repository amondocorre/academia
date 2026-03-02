<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controlador base para todos los endpoints de la API.
 * Provee: parsing JWT, respuestas JSON estandarizadas y CORS.
 */
class Api_base extends CI_Controller
{
    protected $user_data = null;  // Payload del JWT autenticado

    // Clave secreta (en producción usar config o variable de entorno)
    const JWT_SECRET = 'academia_familiar_super_secret_2026';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['jwt', 'url']);

        // Habilitar CORS dinámico basado en el origen de la petición
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
        $this->output
            ->set_header('Access-Control-Allow-Origin: ' . $origin)
            ->set_header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS')
            ->set_header('Access-Control-Allow-Headers: Content-Type, Authorization')
            ->set_header('Access-Control-Allow-Credentials: true')
            ->set_header('Content-Type: application/json; charset=utf-8');

        // Preflight OPTIONS → responder vacío
        if ($this->input->server('REQUEST_METHOD') === 'OPTIONS') {
            $this->output->set_status_header(200)->_display();
            exit;
        }
    }

    /**
     * Extrae y valida el JWT del header Authorization.
     * Si el token es inválido lanza una respuesta 401.
     *
     * @return array Payload del token
     */
    protected function require_auth(): array
    {
        // Obtener headers de forma robusta
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        }

        // Buscar Authorization ignorando mayúsculas/minúsculas
        $header = '';
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }

        // Respaldo por si Apache lo redirigió (común en PHP-FPM)
        if (empty($header)) {
            $header = $this->input->server('HTTP_AUTHORIZATION') ?? 
                      $this->input->server('REDIRECT_HTTP_AUTHORIZATION') ?? '';
        }

        if (empty($header) || strpos($header, 'Bearer ') !== 0) {
            $this->json_error('Token no proporcionado o formato inválido (Header: ' . (empty($header) ? 'vacío' : 'presente') . ')', 401);
        }

        $token   = trim(substr($header, 7));
        $partes  = explode('.', $token);
        if (count($partes) !== 3) {
            $this->json_error('Token malformado: no tiene 3 partes', 401);
        }

        $payload = jwt_decode($token, self::JWT_SECRET);

        if (!$payload) {
            // Re-decodificar sin verificar para ver si expiró o la firma falló
            $temp_partes = explode('.', $token);
            $temp_payload = json_decode(base64url_decode($temp_partes[1]), true);
            $msg = 'Token inválido';
            if ($temp_payload && isset($temp_payload['exp']) && $temp_payload['exp'] < time()) {
                $msg = 'Token expirado';
            } else {
                $msg = 'Firma de token inválida';
            }
            $this->json_error($msg, 401);
        }

        $this->user_data = $payload;
        return $payload;
    }

    /**
     * Verifica que el usuario autenticado tenga el rol esperado.
     *
     * @param string $rol 'padre' o 'hijo'
     */
    protected function require_rol(string $rol): void
    {
        if ($this->user_data['rol'] !== $rol) {
            $this->json_error('Acceso denegado para tu rol', 403);
        }
    }

    // -------------------------------------------------------
    // Helpers de respuesta JSON

    /** Éxito estándar */
    protected function json_ok(array $data = [], string $message = 'OK', int $status = 200): void
    {
        $this->output->set_status_header($status)
                     ->set_content_type('application/json', 'utf-8')
                     ->set_output(json_encode([
                         'status'  => true,
                         'message' => $message,
                         'data'    => $data,
                     ]));
        $this->output->_display();
        exit;
    }

    /** Error estándar - termina la ejecución */
    protected function json_error(string $message = 'Error', int $status = 400): void
    {
        $this->output->set_status_header($status)
                     ->set_content_type('application/json', 'utf-8')
                     ->set_output(json_encode([
                         'status'  => false,
                         'message' => $message,
                         'data'    => null,
                     ]));
        $this->output->_display();
        exit;
    }

    /** Obtiene y decodifica el body JSON de la petición */
    protected function json_body(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
}
