<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\NginxRewriteRuleGenerator;

final class NginxRewriteRuleGeneratorTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Default style generation
    // -----------------------------------------------------------------------

    public function testGenerateReturnsNonEmptyString(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertNotEmpty($gen->generate());
    }

    public function testGenerateContainsLocationBlock(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('location', $gen->generate());
    }

    public function testGenerateContainsPrefixInLocationDirective(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('/protected-uploads/', $gen->generate());
    }

    public function testGenerateContainsPriorityModifier(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('^~', $gen->generate());
    }

    public function testGenerateContainsRewriteDirective(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('rewrite', $gen->generate());
    }

    public function testGenerateContainsPrefixInRewritePattern(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('^/protected-uploads/', $gen->generate());
    }

    public function testGenerateContainsEndpointInRewriteReplacement(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
    }

    public function testGenerateContainsPrefixInAssetParameter(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('asset=protected-uploads/', $gen->generate());
    }

    public function testGenerateContainsLastFlag(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('last', $gen->generate());
    }

    public function testGenerateContainsCaptureGroupAndBackreference(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('(.*)', $gen->generate());
        $this->assertStringContainsString('$1', $gen->generate());
    }

    public function testGenerateContainsOpenAndCloseBrace(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('{', $gen->generate());
        $this->assertStringContainsString('}', $gen->generate());
    }

    public function testDefaultStyleOutput(): void
    {
        $gen      = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $expected = implode("\n", [
            'location ^~ /protected-uploads/ {',
            '    rewrite ^/protected-uploads/(.*)$ /asset-access?asset=protected-uploads/$1 last;',
            '}',
        ]);

        $this->assertSame($expected, $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Prefix normalization
    // -----------------------------------------------------------------------

    public function testLeadingSlashInPrefixIsStripped(): void
    {
        $gen = new NginxRewriteRuleGenerator('/protected-uploads', '/asset-access');

        $this->assertStringContainsString('/protected-uploads/', $gen->generate());
        $this->assertStringNotContainsString('//protected-uploads/', $gen->generate());
    }

    public function testTrailingSlashInPrefixIsStripped(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads/', '/asset-access');

        $this->assertStringContainsString('location ^~ /protected-uploads/ {', $gen->generate());
    }

    public function testBothSlashesInPrefixAreStripped(): void
    {
        $gen      = new NginxRewriteRuleGenerator('/protected-uploads/', '/asset-access');
        $expected = implode("\n", [
            'location ^~ /protected-uploads/ {',
            '    rewrite ^/protected-uploads/(.*)$ /asset-access?asset=protected-uploads/$1 last;',
            '}',
        ]);

        $this->assertSame($expected, $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Endpoint normalization
    // -----------------------------------------------------------------------

    public function testEndpointWithoutLeadingSlashGetsOne(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', 'asset-access');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
    }

    public function testTrailingSlashInEndpointIsStripped(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access/');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
        $this->assertStringNotContainsString('/asset-access/?asset=', $gen->generate());
    }

    public function testEndpointNormalizationProducesSameResultAsCanonical(): void
    {
        $canonical = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $noSlash   = new NginxRewriteRuleGenerator('protected-uploads', 'asset-access');
        $trailing  = new NginxRewriteRuleGenerator('protected-uploads', 'asset-access/');
        $both      = new NginxRewriteRuleGenerator('/protected-uploads/', '/asset-access/');

        $this->assertSame($canonical->generate(), $noSlash->generate());
        $this->assertSame($canonical->generate(), $trailing->generate());
        $this->assertSame($canonical->generate(), $both->generate());
    }

    // -----------------------------------------------------------------------
    // No duplicate slashes
    // -----------------------------------------------------------------------

    public function testNoDuplicateSlashInLocationBlock(): void
    {
        $gen = new NginxRewriteRuleGenerator('/protected-uploads/', '/asset-access/');

        $this->assertStringNotContainsString('//', $gen->generate());
    }

    public function testNoDuplicateSlashWithDoubleSlashEndpoint(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '//asset-access//');

        $this->assertStringNotContainsString('//', $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Deterministic output
    // -----------------------------------------------------------------------

    public function testSameInputProducesSameOutput(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertSame($gen->generate(), $gen->generate());
    }

    public function testDifferentPrefixProducesDifferentOutput(): void
    {
        $g1 = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $g2 = new NginxRewriteRuleGenerator('private-files', '/asset-access');

        $this->assertNotSame($g1->generate(), $g2->generate());
    }

    public function testDifferentEndpointProducesDifferentOutput(): void
    {
        $g1 = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $g2 = new NginxRewriteRuleGenerator('protected-uploads', '/secure-delivery');

        $this->assertNotSame($g1->generate(), $g2->generate());
    }

    // -----------------------------------------------------------------------
    // Does not write files
    // -----------------------------------------------------------------------

    public function testGenerateDoesNotCreateNginxFile(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $gen->generate();

        $this->assertFileDoesNotExist(getcwd() . '/nginx.conf');
        $this->assertFileDoesNotExist(getcwd() . '/period-asset-access.conf');
    }

    public function testGenerateDoesNotCreateAnyTempFile(): void
    {
        $before = scandir(sys_get_temp_dir());
        $gen    = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $gen->generate();
        $after = scandir(sys_get_temp_dir());

        $this->assertSame($before, $after);
    }

    public function testGenerateDoesNotTouchHtaccess(): void
    {
        $gen = new NginxRewriteRuleGenerator('protected-uploads', '/asset-access');
        $gen->generate();

        $this->assertFileDoesNotExist(getcwd() . '/.htaccess');
    }
}
