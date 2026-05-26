<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class NginxDirectAccessDenyRuleGenerator
{
    private readonly string $protectedPrefix;

    public function __construct(string $protectedPrefix)
    {
        $this->protectedPrefix = '/' . trim($protectedPrefix, '/') . '/';
    }

    public function generate(): string
    {
        return implode("\n", [
            'location ^~ ' . $this->protectedPrefix . ' {',
            '    deny all;',
            '}',
        ]);
    }
}
