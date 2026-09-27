<?php


require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../exceptions/ApiException.php';


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

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ApiException(
            400,
            'BAD_REQUEST'
        );
    }

    $nombre = trim($data['nombre'] ?? '');
    $correo = trim($data['correo'] ?? '');
    $password = $data['password'] ?? '';
    $passwordConfirmation = $data['password_confirmation'] ?? '';

    $details = [];

    if ($nombre === '') {
        $details[] = [
            'field' => 'nombre',
            'message' => 'El nombre es obligatorio.'
        ];
    }

    if ($correo === '') {
        $details[] = [
            'field' => 'correo',
            'message' => 'El correo es obligatorio.'
        ];
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $details[] = [
            'field' => 'correo',
            'message' => 'El correo no es válido.'
        ];
    }

    if ($password === '') {
        $details[] = [
            'field' => 'password',
            'message' => 'La contraseña es obligatoria.'
        ];
    }

    if ($password !== '' && strlen($password) < 8) {
        $details[] = [
            'field' => 'password',
            'message' => 'La contraseña debe tener al menos 8 caracteres.'
        ];
    }

    if ($password !== '' && !preg_match('/[A-Z]/', $password)) {
        $details[] = [
            'field' => 'password',
            'message' => 'La contraseña debe contener una mayúscula.'
        ];
    }

    if ($password !== '' && !preg_match('/[a-z]/', $password)) {
        $details[] = [
            'field' => 'password',
            'message' => 'La contraseña debe contener una minúscula.'
        ];
    }

    if ($password !== '' && !preg_match('/[0-9]/', $password)) {
        $details[] = [
            'field' => 'password',
            'message' => 'La contraseña debe contener un número.'
        ];
    }

    if ($password !== $passwordConfirmation) {
        $details[] = [
            'field' => 'password_confirmation',
            'message' => 'Las contraseñas no coinciden.'
        ];
    }

    if (!empty($details)) {
        throw new ApiException(
            422,
            'VALIDATION_ERROR',
            null,
            $details
        );
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
        throw new ApiException(
            409,
            'USER_ALREADY_EXISTS'
        );
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
        'success' => true,
        'message' => 'Usuario registrado correctamente.'
    ]);
}
   

//Todo codigo viviendo en LOGIN------------------------------------------------------

    public function login(): void
    {
    header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new ApiException(
            400,
            'BAD_REQUEST'
        );
    }

        $correo = trim($data['correo'] ?? '');
        $password = $data['password'] ?? '';

      

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
    throw new ApiException(
        401,
        'AUTH_INVALID_CREDENTIALS'
    );
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
    throw new ApiException(
        500,
        'INTERNAL_ERROR'
    );
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

$refreshTokenHash = hash('sha256', $refreshToken);

$refreshExpiresAt = date(
    'Y-m-d H:i:sP',
    time() + (60 * 60 * 24 * 30)
);

$stmt = $this->pdo->prepare("
    INSERT INTO refresh_tokens (
        usuario_id,
        token_hash,
        expires_at
    )
    VALUES (
        :usuario_id,
        :token_hash,
        :expires_at
    )
");

$stmt->execute([
    ':usuario_id' => (int) $usuario['id'],
    ':token_hash' => $refreshTokenHash,
    ':expires_at' => $refreshExpiresAt
]);

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

public function me(?array $auth): void
{
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => true,
        'data' => [
            'usuario' => $auth['usuario']
        ]
    ]);
}

//Funcion de logout y arrojo de excepciones-------------------------------------------------------

public function logout(?array $auth): void
{
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );      //Arroja mensaje con exito

    if (json_last_error() !== JSON_ERROR_NONE) {
    throw new ApiException(
        400,
        'BAD_REQUEST'
    );  //si no error badRequest
}

    $refreshToken = $input['refresh_token'] ?? null;

if (!$refreshToken) {
    throw new ApiException(
        422,
        'VALIDATION_ERROR',
        null,
        [
            [
                'field' => 'refresh_token',
                'message' => 'El refresh token es obligatorio.'
            ]
        ]
    );
}

    $jti = $auth['jti'];

    // Revocar access token
    $stmt = $this->pdo->prepare("
        INSERT INTO tokens_revocados (jti)
        VALUES (:jti)
        ON CONFLICT (jti) DO NOTHING
    ");

    $stmt->execute([
        ':jti' => $jti
    ]);

    // Revocar refresh token
    $refreshTokenHash = hash(
        'sha256',
        $refreshToken
    );

    $stmt = $this->pdo->prepare("
        UPDATE refresh_tokens
        SET revoked_at = CURRENT_TIMESTAMP
        WHERE token_hash = :token_hash
          AND revoked_at IS NULL
    ");

    $stmt->execute([
        ':token_hash' => $refreshTokenHash
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
}

}