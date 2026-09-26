<?php

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
}