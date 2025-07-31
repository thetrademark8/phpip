<?php

namespace App\DataTransferObjects\Renewal;

class ActionResultDTO
{
    public function __construct(
        public bool $success,
        public int $count = 0,
        public ?string $message = null,
        public ?string $error = null,
        public array $data = [],
    ) {}

    public static function success(int $count, ?string $message = null, array $data = []): self
    {
        return new self(
            success: true,
            count: $count,
            message: $message,
            data: $data,
        );
    }

    public static function error(string $error, array $data = []): self
    {
        return new self(
            success: false,
            count: 0,
            message: null,
            error: $error,
            data: $data,
        );
    }

    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'count' => $this->count,
        ];

        if ($this->message) {
            $result['message'] = $this->message;
        }

        if ($this->error) {
            $result['error'] = $this->error;
        }

        if (!empty($this->data)) {
            $result['data'] = $this->data;
        }

        return $result;
    }

    public function toResponse(): array
    {
        if ($this->success) {
            return ['success' => $this->message ?? "Operation completed for {$this->count} items"];
        }

        return ['error' => $this->error ?? 'Operation failed'];
    }
}