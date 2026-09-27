<?php


require_once __DIR__ . '/../../vendor/autoload.php';


use Firebase\JWT\JWT;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\Dir;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\Serializer\CompactSerializer;

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function register(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(file_get_contents('php://input'), true);

        $nombre = trim($data['nombre'] ?? '');
        $correo = trim($data['correo'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirmation = $data['password_confirmation'] ?? '';

        if (
            $nombre === '' ||
            $correo === '' ||
            $password === '' ||
            $passwordConfirmation === ''
        ) {
            http_response_code(400);

            echo json_encode([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Todos los campos son obligatorios.'
            ]);

            return;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);

            echo json_encode([
                'code' => 'INVALID_EMAIL',
                'message' => 'El correo no tiene un formato válido.'
            ]);

            return;
        }

        if ($password !== $passwordConfirmation) {
            http_response_code(400);

            echo json_encode([
                'code' => 'PASSWORD_MISMATCH',
                'message' => 'Las contraseñas no coinciden.'
            ]);

            return;
        }

        if (strlen($password) < 8) {
            http_response_code(400);

            echo json_encode([
                'code' => 'INVALID_PASSWORD',
                'message' => 'La contraseña debe tener mínimo 8 caracteres.'
            ]);

            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            http_response_code(400);

            echo json_encode([
                'code' => 'INVALID_PASSWORD',
                'message' => 'La contraseña debe contener una mayúscula.'
            ]);

            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            http_response_code(400);

            echo json_encode([
                'code' => 'INVALID_PASSWORD',
                'message' => 'La contraseña debe contener una minúscula.'
            ]);

            return;
        }

        if (!preg_match('/[0-9]/', $password)) {
            http_response_code(400);

            echo json_encode([
                'code' => 'INVALID_PASSWORD',
                'message' => 'La contraseña debe contener un número.'
            ]);

            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");

        $stmt->execute([
            ':correo' => $correo
        ]);

        if ($stmt->fetch()) {
            http_response_code(409);

            echo json_encode([
                'code' => 'USER_ALREADY_EXISTS',
                'message' => 'El correo ya está registrado.'
            ]);

            return;
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios
                (
                    nombre,
                    correo,
                    password_hash,
                    idioma,
                    created_at,
                    updated_at
                )
            VALUES
                (
                    :nombre,
                    :correo,
                    :password_hash,
                    :idioma,
                    NOW(),
                    NOW()
                )
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':password_hash' => $passwordHash,
            ':idioma' => 'es'
        ]);

        http_response_code(201);

        echo json_encode([
            'message' => 'Usuario registrado correctamente.'
        ]);
    }

    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $correo = trim($data['correo'] ?? '');
        $password = $data['password'] ?? '';

        if ($correo === '' || $password === '') {
            http_response_code(401);

            echo json_encode([
                'message' => 'Correo o contraseña inválidos'
            ]);

            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT id, nombre, correo, password_hash, idioma
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");

        $stmt->execute([
            ':correo' => $correo
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$usuario ||
            !password_verify($password, $usuario['password_hash'])
        ) {
            http_response_code(401);

            echo json_encode([
                'message' => 'Correo o contraseña inválidos'
            ]);

            return;
        }

        /*
         * 6.2
         * Derivamos dos claves diferentes
         * a partir del mismo secreto.
         */

        $secreto = base64_decode(
            env('APP_TOKEN_SECRET'),
            true
        );

        if ($secreto === false) {
            http_response_code(500);

            echo json_encode([
                'message' => 'Error de configuración del servidor.'
            ]);

            return;
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

        /*
         * 6.4
         * Creamos el contenido del JWT.
         */

        $ahora = time();
        $expira = $ahora + 900;

        $payload = [
            'sub' => (int) $usuario['id'],
            'iat' => $ahora,
            'exp' => $expira,
            'jti' => bin2hex(random_bytes(16)),
            'idioma' => $usuario['idioma']
        ];

        /*
         * 6.5
         * Firmamos el JWT utilizando HS256.
         */

        $jwt = JWT::encode(
            $payload,
            $claveFirma,
            'HS256'
        );

        /*
 * 6.6
 * Ciframos el JWT firmado utilizando JWE
 * con dir + A256GCM.
 */

$keyEncryptionAlgorithmManager = new AlgorithmManager([
    new Dir()
]);

$contentEncryptionAlgorithmManager = new AlgorithmManager([
    new A256GCM()
]);

$jweBuilder = new JWEBuilder(
    $keyEncryptionAlgorithmManager,
    $contentEncryptionAlgorithmManager,
    new CompressionMethodManager([])
);

$jwk = new \Jose\Component\Core\JWK([
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

$jwe = $jweBuilder
    ->create()
    ->withPayload($jwt)
    ->withSharedProtectedHeader([
        'alg' => 'dir',
        'enc' => 'A256GCM'
    ])
    ->addRecipient($jwk)
    ->build();

$serializer = new CompactSerializer();

$accessToken = $serializer->serialize($jwe);

$refreshToken = bin2hex(random_bytes(32));

http_response_code(200);

echo json_encode([
    'success' => true,
    'data' => [
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in' => 900
    ]
]);
    }
}