<?php
namespace BaseballAnalytics\Api;

class ApiResponse {
    private $statusCode;
    private $data;
    private $message;
    private $errors;

    public function __construct(int $statusCode = 200, $data = null, string $message = '', array $errors = []) {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
        $this->errors = $errors;
    }

    public function send(): void {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => $this->getStatusText(),
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'timestamp' => date('c')
        ]);
        exit();
    }

    private function getStatusText(): string {
        return match ($this->statusCode) {
            200 => 'success',
            201 => 'created',
            204 => 'no_content',
            400 => 'bad_request',
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'not_found',
            422 => 'validation_error',
            429 => 'too_many_requests',
            500 => 'server_error',
            default => 'unknown'
        };
    }

    public static function success($data = null, string $message = ''): self {
        return new self(200, $data, $message);
    }

    public static function created($data = null, string $message = ''): self {
        return new self(201, $data, $message);
    }

    public static function noContent(string $message = ''): self {
        return new self(204, null, $message);
    }

    public static function badRequest(string $message = '', array $errors = []): self {
        return new self(400, null, $message, $errors);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self {
        return new self(401, null, $message);
    }

    public static function forbidden(string $message = 'Forbidden'): self {
        return new self(403, null, $message);
    }

    public static function notFound(string $message = 'Resource not found'): self {
        return new self(404, null, $message);
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): self {
        return new self(422, null, $message, $errors);
    }

    public static function tooManyRequests(string $message = 'Too many requests'): self {
        return new self(429, null, $message);
    }

    public static function serverError(string $message = 'Internal server error'): self {
        return new self(500, null, $message);
    }

    public function withHeaders(array $headers): self {
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        return $this;
    }

    public function withPagination(array $pagination): self {
        $this->data = [
            'items' => $this->data,
            'pagination' => $pagination
        ];
        return $this;
    }

    public function withMeta(array $meta): self {
        if (!is_array($this->data)) {
            $this->data = ['data' => $this->data];
        }
        $this->data['meta'] = $meta;
        return $this;
    }
} 