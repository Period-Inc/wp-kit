<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\HtaccessRewriteRuleGenerator;

final class HtaccessRewriteRuleGeneratorTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Default style generation
    // -----------------------------------------------------------------------

    public function testGenerateReturnsNonEmptyString(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertNotEmpty($gen->generate());
    }

    public function testGenerateContainsIfModuleOpen(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('<IfModule mod_rewrite.c>', $gen->generate());
    }

    public function testGenerateContainsIfModuleClose(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('</IfModule>', $gen->generate());
    }

    public function testGenerateContainsRewriteEngineOn(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('RewriteEngine On', $gen->generate());
    }

    public function testGenerateContainsRewriteRule(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('RewriteRule', $gen->generate());
    }

    public function testGenerateContainsPrefixInPattern(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('^protected-uploads/', $gen->generate());
    }

    public function testGenerateContainsEndpointInSubstitution(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
    }

    public function testGenerateContainsPrefixInAssetParameter(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('asset=protected-uploads/', $gen->generate());
    }

    public function testGenerateContainsRewriteFlags(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('[L,QSA]', $gen->generate());
    }

    public function testGenerateContainsCaptureGroup(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertStringContainsString('(.*)', $gen->generate());
        $this->assertStringContainsString('$1', $gen->generate());
    }

    public function testDefaultStyleOutput(): void
    {
        $gen      = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $expected = implode("\n", [
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteRule ^protected-uploads/(.*)$ /asset-access?asset=protected-uploads/$1 [L,QSA]',
            '</IfModule>',
        ]);

        $this->assertSame($expected, $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Prefix normalization
    // -----------------------------------------------------------------------

    public function testLeadingSlashInPrefixIsStripped(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('/protected-uploads', '/asset-access');

        $this->assertStringContainsString('^protected-uploads/', $gen->generate());
        $this->assertStringNotContainsString('^/protected-uploads/', $gen->generate());
    }

    public function testTrailingSlashInPrefixIsStripped(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads/', '/asset-access');

        $this->assertStringContainsString('^protected-uploads/', $gen->generate());
        $this->assertStringNotContainsString('^protected-uploads//', $gen->generate());
    }

    public function testBothSlashesInPrefixAreStripped(): void
    {
        $gen      = new HtaccessRewriteRuleGenerator('/protected-uploads/', '/asset-access');
        $expected = implode("\n", [
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteRule ^protected-uploads/(.*)$ /asset-access?asset=protected-uploads/$1 [L,QSA]',
            '</IfModule>',
        ]);

        $this->assertSame($expected, $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Endpoint normalization
    // -----------------------------------------------------------------------

    public function testEndpointWithoutLeadingSlashGetsOne(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', 'asset-access');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
    }

    public function testTrailingSlashInEndpointIsStripped(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access/');

        $this->assertStringContainsString('/asset-access?asset=', $gen->generate());
        $this->assertStringNotContainsString('/asset-access/?asset=', $gen->generate());
    }

    public function testEndpointNormalizationProducesSameResultAsCanonical(): void
    {
        $canonical  = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $withSlash  = new HtaccessRewriteRuleGenerator('protected-uploads', 'asset-access/');
        $bothSlash  = new HtaccessRewriteRuleGenerator('/protected-uploads/', '/asset-access/');
        $noSlash    = new HtaccessRewriteRuleGenerator('protected-uploads', 'asset-access');

        $this->assertSame($canonical->generate(), $withSlash->generate());
        $this->assertSame($canonical->generate(), $bothSlash->generate());
        $this->assertSame($canonical->generate(), $noSlash->generate());
    }

    // -----------------------------------------------------------------------
    // No duplicate slashes
    // -----------------------------------------------------------------------

    public function testNoDuplicateSlashInPattern(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('/protected-uploads/', '/asset-access/');

        $this->assertStringNotContainsString('//', $gen->generate());
    }

    public function testNoDuplicateSlashInSubstitution(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '//asset-access//');

        $this->assertStringNotContainsString('//', $gen->generate());
    }

    // -----------------------------------------------------------------------
    // Deterministic output
    // -----------------------------------------------------------------------

    public function testSameInputProducesSameOutput(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');

        $this->assertSame($gen->generate(), $gen->generate());
    }

    public function testDifferentPrefixProducesDifferentOutput(): void
    {
        $g1 = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $g2 = new HtaccessRewriteRuleGenerator('private-files', '/asset-access');

        $this->assertNotSame($g1->generate(), $g2->generate());
    }

    public function testDifferentEndpointProducesDifferentOutput(): void
    {
        $g1 = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $g2 = new HtaccessRewriteRuleGenerator('protected-uploads', '/secure-delivery');

        $this->assertNotSame($g1->generate(), $g2->generate());
    }

    // -----------------------------------------------------------------------
    // Does not write files
    // -----------------------------------------------------------------------

    public function testGenerateDoesNotCreateHtaccessFile(): void
    {
        $gen = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $gen->generate();

        $this->assertFileDoesNotExist(getcwd() . '/.htaccess');
    }

    public function testGenerateDoesNotCreateAnyFile(): void
    {
        $before = scandir(sys_get_temp_dir());
        $gen    = new HtaccessRewriteRuleGenerator('protected-uploads', '/asset-access');
        $gen->generate();
        $after = scandir(sys_get_temp_dir());

        $this->assertSame($before, $after);
    }
}
