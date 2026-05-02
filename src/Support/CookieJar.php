<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class CookieJar
{
    private array $cookies = [];

    public function set(string $name, string $value): self
    {
        $this->cookies[$name] = $value;

        return $this;
    }

    public function get(string $name): string
    {
        return $this->cookies[$name] ?? '';
    }

    public function all(): array
    {
        return $this->cookies;
    }

    public function fromHeader(string|array $header): self
    {
        $lines = is_array($header)
            ? $header
            : preg_split('/\r\n|\n|\r/', $header);

        if (!is_array($lines)) {
            return $this;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (stripos($line, 'set-cookie:') === 0) {
                $line = trim(substr($line, strlen('set-cookie:')));
            }

            $parts = explode(';', $line);
            $cookie = trim($parts[0]);

            if ($cookie === '') {
                continue;
            }

            $pair = explode('=', $cookie, 2);

            if (count($pair) !== 2) {
                continue;
            }

            $name = trim($pair[0]);
            $value = trim($pair[1]);

            if ($name === '') {
                continue;
            }

            $this->set($name, $value);
        }

        return $this;
    }

    public function toHeader(): string
    {
        $parts = [];

        foreach ($this->cookies as $name => $value) {
            $parts[] = sprintf('%s=%s', $name, $value);
        }

        return implode('; ', $parts);
    }
}
