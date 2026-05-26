<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class HtaccessRewriteRuleGenerator
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
        $rule = sprintf(
            'RewriteRule ^%s/(.*)$ %s?asset=%s/$1 [L,QSA]',
            $this->prefix,
            $this->endpoint,
            $this->prefix,
        );

        return implode("\n", [
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            $rule,
            '</IfModule>',
        ]);
    }
}
