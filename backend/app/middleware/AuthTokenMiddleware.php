<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\KeyEncryption\Dir;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer;

class AuthTokenMiddleware
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(): ?array
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if ($authorization === '') {
            $this->unauthorized('AUTH_TOKEN_MISSING');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $this->unauthorized('AUTH_TOKEN_MISSING');
        }

        $token = trim($matches[1]);

        if ($token === '') {
            $this->unauthorized('AUTH_TOKEN_MISSING');
        }

        try {
            $secreto = base64_decode(
                env('APP_TOKEN_SECRET'),
                true
            );

            if ($secreto === false) {
                throw new RuntimeException('Invalid secret');
            }

            $claveFirma = hash_hkdf(
                'sha256',
                $secreto,
                32,
                'jwt-firma'
            );

            $claveCifrado = hash_hkdf(
                'sha256',
                $secreto,
                32,
                'jwt-cifrado'
            );

            $keyEncryptionAlgorithmManager = new AlgorithmManager([
                new Dir()
            ]);

            $contentEncryptionAlgorithmManager = new AlgorithmManager([
                new A256GCM()
            ]);

            $jweDecrypter = new JWEDecrypter(
                $keyEncryptionAlgorithmManager,
                $contentEncryptionAlgorithmManager,
                new CompressionMethodManager([])
            );

            $jwk = new JWK([
                'kty' => 'oct',
                'k' => rtrim(
                    strtr(
                        base64_encode($claveCifrado),
                        '+/',
                        '-_'
                    ),
                    '='
                )
            ]);

            $serializer = new CompactSerializer();

            $jwe = $serializer->unserialize($token);

            $protectedHeader = $jwe->getSharedProtectedHeader();

            if (
                ($protectedHeader['alg'] ?? null) !== 'dir' ||
                ($protectedHeader['enc'] ?? null) !== 'A256GCM'
            ) {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            $jweDecrypter->decryptUsingKey(
                $jwe,
                $jwk,
                0
            );

            $jwt = $jwe->getPayload();

            if (!is_string($jwt) || $jwt === '') {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            $decoded = JWT::decode(
                $jwt,
                new Key($claveFirma, 'HS256')
            );

            $payload = (array) $decoded;

            if (
                !isset($payload['exp']) ||
                !is_numeric($payload['exp'])
            ) {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            if ((int) $payload['exp'] < time()) {
                $this->unauthorized('AUTH_TOKEN_EXPIRED');
            }

            if (
                !isset($payload['jti']) ||
                !is_string($payload['jti'])
            ) {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            $stmt = $this->pdo->prepare("
                SELECT 1
                FROM tokens_revocados
                WHERE jti = :jti
                LIMIT 1
            ");

            $stmt->execute([
                ':jti' => $payload['jti']
            ]);

            if ($stmt->fetchColumn()) {
                $this->unauthorized('AUTH_TOKEN_REVOKED');
            }

            if (
                !isset($payload['sub']) ||
                !is_numeric($payload['sub'])
            ) {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            $stmt = $this->pdo->prepare("
                SELECT id, nombre, correo, idioma
                FROM usuarios
                WHERE id = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => (int) $payload['sub']
            ]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->unauthorized('AUTH_TOKEN_INVALID');
            }

            return [
    'usuario' => $usuario,
    'jti' => $payload['jti']
];

        } catch (\Firebase\JWT\ExpiredException $e) {

            $this->unauthorized('AUTH_TOKEN_EXPIRED');

        } catch (\Throwable $e) {


            $this->unauthorized('AUTH_TOKEN_INVALID');
        }
    }

   private function unauthorized(string $code): never
{
    throw new ApiException(401, $code);
}
}