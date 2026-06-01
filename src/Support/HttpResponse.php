<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class HttpResponse
{
    private int $statusCode;
    private array $headers;
    private string $body;

    public function __construct(int $statusCode = 0, array $headers = [], string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function header(string $name): string
    {
        $name = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                if (is_array($value)) {
                    return $value[0] ?? '';
                }

                return (string) $value;
            }
        }

        return '';
    }

    public function isOk(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
