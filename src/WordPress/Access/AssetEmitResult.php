<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetEmitResult
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly bool $emitted,
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly ?string $body,
        private readonly ?string $redirectUrl,
    ) {}

    public function emitted(): bool
    {
        return $this->emitted;
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
