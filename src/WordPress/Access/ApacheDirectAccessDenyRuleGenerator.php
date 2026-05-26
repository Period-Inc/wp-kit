<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class ApacheDirectAccessDenyRuleGenerator
{
    private readonly string $protectedPrefix;

    public function __construct(string $protectedPrefix)
    {
        $this->protectedPrefix = trim($protectedPrefix, '/');
    }

    public function generate(): string
    {
        return implode("\n", [
            '# Direct access denied for protected prefix: ' . $this->protectedPrefix,
            '<IfModule mod_authz_core.c>',
            '    Require all denied',
            '</IfModule>',
            '<IfModule !mod_authz_core.c>',
            '    Deny from all',
            '</IfModule>',
        ]);
    }
}
