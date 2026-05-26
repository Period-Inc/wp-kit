<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetUrlRewriteStrategyInterface;
use Period\WpFramework\WordPress\Access\SignedAssetUrlGenerator;
use Period\WpFramework\WordPress\Access\SignedAssetUrlRewriteStrategy;

final class SignedAssetUrlRewriteStrategyTest extends TestCase
{
    private function makeGenerator(string $secret = 'test-secret', string $base = '/asset-access'): SignedAssetUrlGenerator
    {
        return new SignedAssetUrlGenerator($secret, $base);
    }

    private function makeStrategy(
        string            $secret    = 'test-secret',
        string            $base      = '/asset-access',
        ?DateTimeImmutable $expiresAt = null,
    ): SignedAssetUrlRewriteStrategy {
        return new SignedAssetUrlRewriteStrategy(
            $this->makeGenerator($secret, $base),
            $expiresAt ?? new DateTimeImmutable('2030-01-01T00:00:00Z'),
        );
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    public function testImplementsAssetUrlRewriteStrategyInterface(): void
    {
        $this->assertInstanceOf(AssetUrlRewriteStrategyInterface::class, $this->makeStrategy());
    }

    // -----------------------------------------------------------------------
    // Signed URL is generated from protected path
    // -----------------------------------------------------------------------

    public function testRewriteReturnsNonEmptyString(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('https://example.com/uploads/file.pdf', '/protected-uploads/file.pdf');

        $this->assertNotEmpty($result);
    }

    public function testRewriteUrlContainsAssetParameter(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringContainsString('asset=', $result);
    }

    public function testRewriteUrlContainsEncodedProtectedPath(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringContainsString(rawurlencode('/protected-uploads/file.pdf'), $result);
    }

    public function testRewriteUrlContainsExpiresParameter(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringContainsString('expires=', $result);
    }

    public function testRewriteUrlContainsSignatureParameter(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringContainsString('signature=', $result);
    }

    public function testRewriteUrlStartsWithBaseUrl(): void
    {
        $strategy = $this->makeStrategy(base: '/secure-assets');

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringStartsWith('/secure-assets', $result);
    }

    // -----------------------------------------------------------------------
    // Original URL is ignored
    // -----------------------------------------------------------------------

    public function testOriginalUrlIsIgnored(): void
    {
        $strategy = $this->makeStrategy();

        $r1 = $strategy->rewrite('https://example.com/uploads/file.pdf', '/protected-uploads/file.pdf');
        $r2 = $strategy->rewrite('https://other-origin.com/different/path.pdf', '/protected-uploads/file.pdf');

        $this->assertSame($r1, $r2);
    }

    public function testEmptyOriginalUrlProducesSameResult(): void
    {
        $strategy = $this->makeStrategy();

        $withUrl    = $strategy->rewrite('https://example.com/x.pdf', '/protected-uploads/x.pdf');
        $withoutUrl = $strategy->rewrite('', '/protected-uploads/x.pdf');

        $this->assertSame($withUrl, $withoutUrl);
    }

    // -----------------------------------------------------------------------
    // Encoded protected path is preserved
    // -----------------------------------------------------------------------

    public function testSubdirectorySlashesAreEncoded(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/2026/05/report.pdf');

        $this->assertStringContainsString(rawurlencode('/protected-uploads/2026/05/report.pdf'), $result);
    }

    public function testSpecialCharsInPathAreEncoded(): void
    {
        $strategy = $this->makeStrategy();

        $result = $strategy->rewrite('', '/protected-uploads/my file (1).pdf');

        $this->assertStringContainsString(rawurlencode('/protected-uploads/my file (1).pdf'), $result);
    }

    // -----------------------------------------------------------------------
    // Deterministic output
    // -----------------------------------------------------------------------

    public function testSameInputProducesSameOutput(): void
    {
        $expiresAt = new DateTimeImmutable('2030-06-15T12:00:00Z');
        $strategy  = $this->makeStrategy(expiresAt: $expiresAt);

        $r1 = $strategy->rewrite('', '/protected-uploads/file.pdf');
        $r2 = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertSame($r1, $r2);
    }

    public function testDifferentPathsProduceDifferentUrls(): void
    {
        $strategy = $this->makeStrategy();

        $r1 = $strategy->rewrite('', '/protected-uploads/a.pdf');
        $r2 = $strategy->rewrite('', '/protected-uploads/b.pdf');

        $this->assertNotSame($r1, $r2);
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $expiresAt = new DateTimeImmutable('2030-01-01T00:00:00Z');
        $s1 = new SignedAssetUrlRewriteStrategy(
            new SignedAssetUrlGenerator('secret-a', '/asset-access'),
            $expiresAt,
        );
        $s2 = new SignedAssetUrlRewriteStrategy(
            new SignedAssetUrlGenerator('secret-b', '/asset-access'),
            $expiresAt,
        );

        $r1 = $s1->rewrite('', '/protected-uploads/file.pdf');
        $r2 = $s2->rewrite('', '/protected-uploads/file.pdf');

        $this->assertNotSame($r1, $r2);
    }

    public function testExpiresTimestampAppearsInUrl(): void
    {
        $expiresAt = new DateTimeImmutable('2030-01-01T00:00:00Z');
        $strategy  = $this->makeStrategy(expiresAt: $expiresAt);

        $result = $strategy->rewrite('', '/protected-uploads/file.pdf');

        $this->assertStringContainsString((string) $expiresAt->getTimestamp(), $result);
    }

    // -----------------------------------------------------------------------
    // Output matches generator directly
    // -----------------------------------------------------------------------

    public function testOutputMatchesDirectGeneratorCall(): void
    {
        $expiresAt = new DateTimeImmutable('2030-03-15T08:00:00Z');
        $generator = $this->makeGenerator();
        $strategy  = new SignedAssetUrlRewriteStrategy($generator, $expiresAt);

        $expected = $generator->generate('/protected-uploads/file.pdf', $expiresAt);
        $actual   = $strategy->rewrite('https://example.com/uploads/file.pdf', '/protected-uploads/file.pdf');

        $this->assertSame($expected, $actual);
    }
}
