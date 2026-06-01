<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class MemoryHttpEmitter implements HttpEmitterInterface
{
    private int $statusCode        = 200;
    /** @var array<string, string> */
    private array $headers         = [];
    private ?string $body          = null;
    private ?string $redirectUrl   = null;
    private ?int $redirectStatus   = null;

    public function status(int $code): void
    {
        $this->statusCode = $code;
    }

    public function header(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function body(?string $body): void
    {
        $this->body = $body;
    }

    public function redirect(string $url, int $statusCode): void
    {
        $this->redirectUrl    = $url;
        $this->redirectStatus = $statusCode;
    }

    public function emittedStatus(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function emittedHeaders(): array
    {
        return $this->headers;
    }

    public function emittedBody(): ?string
    {
        return $this->body;
    }

    public function emittedRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function emittedRedirectStatus(): ?int
    {
        return $this->redirectStatus;
    }
}
