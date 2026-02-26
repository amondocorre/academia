<?php
/**
 * JWT Helper para CodeIgniter 3
 * Implementación HS256 sin dependencias externas
 * --------------------------------------------------
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Codifica un payload en un token JWT firmado con HS256
 *
 * @param array  $payload    Datos a incluir en el token
 * @param string $secret     Clave secreta
 * @param int    $expMinutes Minutos de expiración (default: 1440 = 24h)
 * @return string Token JWT
 */
function jwt_encode(array $payload, string $secret, int $expMinutes = 1440): string
{
    // Cabecera estándar HS256
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];

    // Agregar claims estándar
    $payload['iat'] = time();
    $payload['exp'] = time() + ($expMinutes * 60);

    $headerEncoded  = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));

    $firma = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $firmaEncoded = base64url_encode($firma);

    return "$headerEncoded.$payloadEncoded.$firmaEncoded";
}

/**
 * Decodifica y verifica un token JWT
 *
 * @param string $token  Token JWT
 * @param string $secret Clave secreta
 * @return array|null    Payload o null si inválido/expirado
 */
function jwt_decode(string $token, string $secret): ?array
{
    $partes = explode('.', $token);
    if (count($partes) !== 3) return null;

    [$headerEncoded, $payloadEncoded, $firmaEncoded] = $partes;

    // Verificar firma
    $firmaEsperada = base64url_encode(
        hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true)
    );

    // Comparación segura contra timing attacks
    if (!hash_equals($firmaEsperada, $firmaEncoded)) return null;

    $payload = json_decode(base64url_decode($payloadEncoded), true);
    if (!$payload) return null;

    // Verificar expiración
    if (isset($payload['exp']) && $payload['exp'] < time()) return null;

    return $payload;
}

/** Codificación URL-safe de Base64 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** Decodificación URL-safe de Base64 */
function base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}
