<?php

require_once __DIR__ . '/ApiException.php';

class ExceptionHandler
{
    public static function register(string $traceId): void
    {
        set_exception_handler(
            function (Throwable $exception) use ($traceId) {
                self::handle($exception, $traceId);
            }
        );
    }

    public static function handle(
        Throwable $exception,
        string $traceId
    ): never {
        $language = self::resolveLanguage($exception);

        if ($exception instanceof ApiException) {
            $status = $exception->status;
            $code = $exception->errorCode;
            $details = $exception->details;

            $message = self::message($code, $language);
        } else {
            $status = 500;
            $code = 'INTERNAL_ERROR';
            $details = null;

            $message = self::message(
                'INTERNAL_ERROR',
                $language
            );
        }

        self::writeLog(
            $exception,
            $traceId,
            $code
        );

        http_response_code($status);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'trace_id' => $traceId
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    private static function resolveLanguage(
        Throwable $exception
    ): string {
        if (
            $exception instanceof ApiException &&
            $exception->language !== null
        ) {
            return $exception->language;
        }

        $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es';

        return str_starts_with(
            strtolower($language),
            'de'
        ) ? 'de' : 'es';
    }

    private static function message(
        string $code,
        string $language
    ): string {
        $messages = [
            'es' => [
                'BAD_REQUEST' => 'La solicitud es inválida.',
                'AUTH_INVALID_CREDENTIALS' => 'Correo o contraseña incorrectos.',
                'AUTH_TOKEN_MISSING' => 'No se envió el token.',
                'AUTH_TOKEN_INVALID' => 'El token no es válido.',
                'AUTH_TOKEN_EXPIRED' => 'El token ha vencido.',
                'AUTH_TOKEN_REVOKED' => 'El token fue revocado.',
                'FORBIDDEN' => 'No tienes permiso para acceder a este recurso.',
                'NOT_FOUND' => 'El recurso solicitado no existe.',
                'USER_ALREADY_EXISTS' => 'El correo ya está registrado.',
                'VALIDATION_ERROR' => 'Hay campos con errores.',
                'TOO_MANY_ATTEMPTS' => 'Demasiados intentos de inicio de sesión.',
                'EXTERNAL_API_ERROR' => 'La API externa respondió con un error.',
                'EXTERNAL_API_TIMEOUT' => 'La API externa no respondió a tiempo.',
                'INTERNAL_ERROR' => 'Ocurrió un error interno.'
            ],

            'de' => [
                'BAD_REQUEST' => 'Die Anfrage ist ungültig.',
                'AUTH_INVALID_CREDENTIALS' => 'E-Mail oder Passwort sind falsch.',
                'AUTH_TOKEN_MISSING' => 'Es wurde kein Token gesendet.',
                'AUTH_TOKEN_INVALID' => 'Das Token ist ungültig.',
                'AUTH_TOKEN_EXPIRED' => 'Das Token ist abgelaufen.',
                'AUTH_TOKEN_REVOKED' => 'Das Token wurde widerrufen.',
                'FORBIDDEN' => 'Du hast keine Berechtigung für diese Ressource.',
                'NOT_FOUND' => 'Die angeforderte Ressource wurde nicht gefunden.',
                'USER_ALREADY_EXISTS' => 'Die E-Mail-Adresse ist bereits registriert.',
                'VALIDATION_ERROR' => 'Es gibt Fehler in den Feldern.',
                'TOO_MANY_ATTEMPTS' => 'Zu viele Anmeldeversuche.',
                'EXTERNAL_API_ERROR' => 'Die externe API hat einen Fehler zurückgegeben.',
                'EXTERNAL_API_TIMEOUT' => 'Die externe API hat nicht rechtzeitig geantwortet.',
                'INTERNAL_ERROR' => 'Ein interner Fehler ist aufgetreten.'
            ]
        ];

        return $messages[$language][$code]
            ?? $messages['es']['INTERNAL_ERROR'];
    }

    private static function writeLog(
        Throwable $exception,
        string $traceId,
        string $code
    ): void {
        $directory = __DIR__ . '/../storage/logs';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $logFile = $directory . '/app.log';

        $message = sprintf(
            "[%s] trace_id=%s code=%s exception=%s message=%s file=%s line=%s\n",
            date('Y-m-d H:i:s'),
            $traceId,
            $code,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        file_put_contents(
            $logFile,
            $message,
            FILE_APPEND | LOCK_EX
        );
    }
}