<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\ApacheDirectAccessDenyRuleGenerator;
use Period\WpKit\WordPress\Access\DirectAccessProtectionStrategy;
use Period\WpKit\WordPress\Access\NginxDirectAccessDenyRuleGenerator;

final class DirectAccessProtectionTest extends TestCase
{
    public function testApacheDenyRuleGeneration(): void
    {
        $generator = new ApacheDirectAccessDenyRuleGenerator('protected-uploads');

        $expected = implode("\n", [
            '# Direct access denied for protected prefix: protected-uploads',
            '<IfModule mod_authz_core.c>',
            '    Require all denied',
            '</IfModule>',
            '<IfModule !mod_authz_core.c>',
            '    Deny from all',
            '</IfModule>',
        ]);

        $this->assertSame($expected, $generator->generate());
    }

    public function testApacheDenyRuleDoesNotUseDirectoryBlock(): void
    {
        $generator = new ApacheDirectAccessDenyRuleGenerator('protected-uploads');

        $this->assertStringNotContainsString('<Directory', $generator->generate());
    }

    public function testApacheDenyRuleIncludesProtectedPrefixInComment(): void
    {
        $generator = new ApacheDirectAccessDenyRuleGenerator('/protected-uploads/');

        $this->assertStringContainsString('protected-uploads', $generator->generate());
    }

    public function testNginxDenyRuleGeneration(): void
    {
        $generator = new NginxDirectAccessDenyRuleGenerator('protected-uploads');

        $expected = implode("\n", [
            'location ^~ /protected-uploads/ {',
            '    deny all;',
            '}',
        ]);

        $this->assertSame($expected, $generator->generate());
    }

    public function testNginxPrefixNormalization(): void
    {
        $canonical = new NginxDirectAccessDenyRuleGenerator('protected-uploads');
        $leading = new NginxDirectAccessDenyRuleGenerator('/protected-uploads');
        $trailing = new NginxDirectAccessDenyRuleGenerator('protected-uploads/');
        $both = new NginxDirectAccessDenyRuleGenerator('/protected-uploads/');

        $this->assertSame($canonical->generate(), $leading->generate());
        $this->assertSame($canonical->generate(), $trailing->generate());
        $this->assertSame($canonical->generate(), $both->generate());
    }

    public function testNginxDenyRuleContainsNoDuplicateSlash(): void
    {
        $generator = new NginxDirectAccessDenyRuleGenerator('/protected-uploads/');

        $this->assertStringNotContainsString('//', $generator->generate());
    }

    public function testDeterministicOutput(): void
    {
        $apache = new ApacheDirectAccessDenyRuleGenerator('protected-uploads');
        $nginx = new NginxDirectAccessDenyRuleGenerator('protected-uploads');

        $this->assertSame($apache->generate(), $apache->generate());
        $this->assertSame($nginx->generate(), $nginx->generate());
    }

    public function testDirectAccessProtectionStrategyHelpers(): void
    {
        $rewrite = DirectAccessProtectionStrategy::rewrite();
        $deny = DirectAccessProtectionStrategy::deny();
        $outsideWebroot = DirectAccessProtectionStrategy::outsideWebroot();

        $this->assertSame(DirectAccessProtectionStrategy::MODE_REWRITE, $rewrite->mode());
        $this->assertTrue($rewrite->isRewrite());
        $this->assertFalse($rewrite->isDeny());
        $this->assertSame(DirectAccessProtectionStrategy::MODE_DENY, $deny->mode());
        $this->assertTrue($deny->isDeny());
        $this->assertSame(DirectAccessProtectionStrategy::MODE_OUTSIDE_WEBROOT, $outsideWebroot->mode());
        $this->assertTrue($outsideWebroot->isOutsideWebroot());
    }

    public function testGenerateHasNoFileWriteBehavior(): void
    {
        $before = scandir(sys_get_temp_dir());

        (new ApacheDirectAccessDenyRuleGenerator('protected-uploads'))->generate();
        (new NginxDirectAccessDenyRuleGenerator('protected-uploads'))->generate();

        $after = scandir(sys_get_temp_dir());

        $this->assertSame($before, $after);
        $this->assertFileDoesNotExist(getcwd() . '/.htaccess');
    }
}
