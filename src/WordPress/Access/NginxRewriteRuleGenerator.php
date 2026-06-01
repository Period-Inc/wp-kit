<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NginxRewriteRuleGenerator
{
    private readonly string $prefix;
    private readonly string $endpoint;

    public function __construct(string $protectedPrefix, string $deliveryEndpoint)
    {
        $this->prefix   = trim($protectedPrefix, '/');
        $this->endpoint = '/' . trim($deliveryEndpoint, '/');
    }

    public function generate(): string
    {
        $rewrite = sprintf(
            '    rewrite ^/%s/(.*)$ %s?asset=%s/$1 last;',
            $this->prefix,
            $this->endpoint,
            $this->prefix,
        );

        return implode("\n", [
            'location ^~ /' . $this->prefix . '/ {',
            $rewrite,
            '}',
        ]);
    }
}
