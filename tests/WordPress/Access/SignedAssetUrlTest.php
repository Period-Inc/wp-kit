<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\SignedAssetToken;
use Period\WpKit\WordPress\Access\SignedAssetUrlGenerator;
use Period\WpKit\WordPress\Access\SignedAssetUrlValidator;

final class SignedAssetUrlTest extends TestCase
{
    private const SECRET  = 'test-secret-key';
    private const BASE    = 'https://example.com/asset-proxy';

    private function generator(): SignedAssetUrlGenerator
    {
        return new SignedAssetUrlGenerator(self::SECRET, self::BASE);
    }

    private function validator(): SignedAssetUrlValidator
    {
        return new SignedAssetUrlValidator(self::SECRET);
    }

    // --- SignedAssetToken ---

    public function testTokenStoresProperties(): void
    {
        $expiresAt = new DateTimeImmutable('2026-12-31 23:59:59');
        $token = new SignedAssetToken('/uploads/file.pdf', $expiresAt, 'abc123');

        $this->assertSame('/uploads/file.pdf', $token->assetPath());
        $this->assertSame($expiresAt, $token->expiresAt());
        $this->assertSame('abc123', $token->signature());
    }

    // --- SignedAssetUrlGenerator ---

    public function testGenerateDeterministic(): void
    {
        $expiresAt = new DateTimeImmutable('2026-12-31 23:59:59');

        $url1 = $this->generator()->generate('/uploads/file.pdf', $expiresAt);
        $url2 = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $this->assertSame($url1, $url2);
    }

    public function testGeneratedUrlContainsAssetParam(): void
    {
        $url = $this->generator()->generate('/uploads/file.pdf', new DateTimeImmutable('+1 hour'));

        $this->assertStringContainsString('asset=', $url);
    }

    public function testGeneratedUrlContainsExpiresParam(): void
    {
        $url = $this->generator()->generate('/uploads/file.pdf', new DateTimeImmutable('+1 hour'));

        $this->assertStringContainsString('expires=', $url);
    }

    public function testGeneratedUrlContainsSignatureParam(): void
    {
        $url = $this->generator()->generate('/uploads/file.pdf', new DateTimeImmutable('+1 hour'));

        $this->assertStringContainsString('signature=', $url);
    }

    public function testGeneratedUrlStartsWithBaseUrl(): void
    {
        $url = $this->generator()->generate('/uploads/file.pdf', new DateTimeImmutable('+1 hour'));

        $this->assertStringStartsWith(self::BASE, $url);
    }

    public function testEncodedPathInUrl(): void
    {
        $url = $this->generator()->generate('/uploads/my file.pdf', new DateTimeImmutable('+1 hour'));

        $this->assertStringContainsString('my%20file.pdf', $url);
    }

    public function testDifferentPathsProduceDifferentSignatures(): void
    {
        $expiresAt = new DateTimeImmutable('2026-12-31 23:59:59');

        $url1 = $this->generator()->generate('/uploads/a.pdf', $expiresAt);
        $url2 = $this->generator()->generate('/uploads/b.pdf', $expiresAt);

        parse_str(parse_url($url1, PHP_URL_QUERY), $p1);
        parse_str(parse_url($url2, PHP_URL_QUERY), $p2);

        $this->assertNotSame($p1['signature'], $p2['signature']);
    }

    // --- SignedAssetUrlValidator ---

    public function testValidSignatureAllows(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $result = $this->validator()->validate($url, new DateTimeImmutable());

        $this->assertTrue($result->allowed());
    }

    public function testExpiredTokenDenies(): void
    {
        $expiresAt = new DateTimeImmutable('-1 second');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $result = $this->validator()->validate($url, new DateTimeImmutable());

        $this->assertFalse($result->allowed());
        $this->assertSame('Expired', $result->reason());
    }

    public function testInvalidSignatureDenies(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);
        $tampered = preg_replace('/signature=[^&]+/', 'signature=invalidsignature', $url);

        $result = $this->validator()->validate($tampered, new DateTimeImmutable());

        $this->assertFalse($result->allowed());
        $this->assertSame('Invalid signature', $result->reason());
    }

    public function testMissingParamsDeny(): void
    {
        $result = $this->validator()->validate(self::BASE . '?asset=/file.pdf', new DateTimeImmutable());

        $this->assertFalse($result->allowed());
        $this->assertSame('Invalid signature', $result->reason());
    }

    public function testWrongSecretDenies(): void
    {
        $expiresAt = new DateTimeImmutable('+1 hour');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $wrongValidator = new SignedAssetUrlValidator('wrong-secret');
        $result = $wrongValidator->validate($url, new DateTimeImmutable());

        $this->assertFalse($result->allowed());
        $this->assertSame('Invalid signature', $result->reason());
    }

    public function testEncodedPathRoundtrip(): void
    {
        $path = '/uploads/my file & doc.pdf';
        $expiresAt = new DateTimeImmutable('+1 hour');
        $url = $this->generator()->generate($path, $expiresAt);

        $result = $this->validator()->validate($url, new DateTimeImmutable());

        $this->assertTrue($result->allowed());
    }

    public function testExactExpiryBoundaryAllows(): void
    {
        $expiresAt = new DateTimeImmutable('2026-06-01 12:00:00');
        $now       = new DateTimeImmutable('2026-06-01 12:00:00');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $result = $this->validator()->validate($url, $now);

        $this->assertTrue($result->allowed());
    }

    public function testOneSecondPastExpiryDenies(): void
    {
        $expiresAt = new DateTimeImmutable('2026-06-01 12:00:00');
        $now       = new DateTimeImmutable('2026-06-01 12:00:01');
        $url = $this->generator()->generate('/uploads/file.pdf', $expiresAt);

        $result = $this->validator()->validate($url, $now);

        $this->assertFalse($result->allowed());
        $this->assertSame('Expired', $result->reason());
    }
}
