<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class HttpClient
{
    private CookieJar $cookies;
    private array $defaultHeaders;

    public function __construct(?CookieJar $cookies = null, array $defaultHeaders = [])
    {
        $this->cookies = $cookies ?? new CookieJar();
        $this->defaultHeaders = $defaultHeaders;
    }

    public static function create(): self
    {
        return new self();
    }

    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        if (!empty($query)) {
            $separator = strpos($url, '?') === false ? '?' : '&';
            $url .= $separator . http_build_query($query);
        }

        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, array $data = [], array $headers = []): HttpResponse
    {
        $body = http_build_query($data);

        if (!array_key_exists('Content-Type', $headers) && !array_key_exists('content-type', $headers)) {
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        return $this->request('POST', $url, $body, $headers);
    }

    public function cookies(): CookieJar
    {
        return $this->cookies;
    }

    private function request(string $method, string $url, ?string $body, array $headers): HttpResponse
    {
        try {
            $requestHeaders = array_merge($this->defaultHeaders, $headers);
            $cookieHeader = $this->cookies->toHeader();

            if ($cookieHeader !== '') {
                $requestHeaders['Cookie'] = $cookieHeader;
            }

            return $this->requestWithFallback($method, $url, $body, $requestHeaders);
        } catch (\Throwable $exception) {
            return new HttpResponse();
        }
    }

    private function requestWithFallback(string $method, string $url, ?string $body, array $headers): HttpResponse
    {
        $contextOptions = [
            'http' => [
                'method' => $method,
                'header' => $this->buildHeaderString($headers),
                'ignore_errors' => true,
            ],
        ];

        if ($method === 'POST') {
            $contextOptions['http']['content'] = $body ?? '';
        }

        $context = stream_context_create($contextOptions);
        $stream = @fopen($url, 'r', false, $context);

        if ($stream === false) {
            return new HttpResponse();
        }

        $body = stream_get_contents($stream);
        $meta = stream_get_meta_data($stream);
        fclose($stream);

        $responseHeaders = [];

        if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            $responseHeaders = $this->parseRawHeaders($meta['wrapper_data']);
        }

        $status = isset($responseHeaders['Status']) ? (int) $responseHeaders['Status'] : 0;
        $this->importSetCookie($responseHeaders);

        return new HttpResponse($status, $responseHeaders, $body === false ? '' : (string) $body);
    }

    private function buildHeaderString(array $headers): string
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }

        return implode("\r\n", $lines);
    }

    private function parseRawHeaders(array $rawHeaders): array
    {
        $headers = [];

        foreach ($rawHeaders as $line) {
            if (stripos($line, 'HTTP/') === 0) {
                if (preg_match('/HTTP\/[^\s]+\s+(\d{3})/', $line, $matches)) {
                    $headers['Status'] = (int) $matches[1];
                }

                continue;
            }

            $parts = explode(':', $line, 2);

            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if (array_key_exists($name, $headers)) {
                if (is_array($headers[$name])) {
                    $headers[$name][] = $value;
                } else {
                    $headers[$name] = [$headers[$name], $value];
                }
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private function normalizeHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $value) {
            if (is_int($name)) {
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private function importSetCookie(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Set-Cookie') !== 0) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $line) {
                    $this->cookies->fromHeader($line);
                }

                continue;
            }

            $this->cookies->fromHeader((string) $value);
        }
    }
}
