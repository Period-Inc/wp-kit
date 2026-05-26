<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetDeliveryResult
{
    /** @param array<string, string> $headers */
    private function __construct(
        private readonly bool $success,
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly ?string $body,
        private readonly ?string $redirectUrl,
    ) {}

    /** @param array<string, string> $headers */
    public static function ok(int $statusCode = 200, array $headers = [], ?string $body = null): self
    {
        return new self(true, $statusCode, $headers, $body, null);
    }

    /** @param array<string, string> $headers */
    public static function deny(int $statusCode = 403, ?string $body = null, array $headers = []): self
    {
        return new self(false, $statusCode, $headers, $body, null);
    }

    /** @param array<string, string> $headers */
    public static function redirect(string $redirectUrl, int $statusCode = 302, array $headers = []): self
    {
        return new self(true, $statusCode, $headers, null, $redirectUrl);
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }
}
