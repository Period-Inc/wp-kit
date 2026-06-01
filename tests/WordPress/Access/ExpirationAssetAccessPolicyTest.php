<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\ExpirationAssetAccessPolicy;

final class ExpirationAssetAccessPolicyTest extends TestCase
{
    private function makeContext(string $requestTime): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: [],
            requestTime: new DateTimeImmutable($requestTime),
        );
    }

    public function testAllowsWhenRequestIsBeforeExpiry(): void
    {
        $policy = new ExpirationAssetAccessPolicy(new DateTimeImmutable('2026-12-31 23:59:59'));

        $result = $policy->evaluate($this->makeContext('2026-06-01 00:00:00'));

        $this->assertTrue($result->allowed());
    }

    public function testAllowsWhenRequestTimeEqualsExpiry(): void
    {
        $expiry = new DateTimeImmutable('2026-06-01 12:00:00');
        $policy = new ExpirationAssetAccessPolicy($expiry);

        $result = $policy->evaluate($this->makeContext('2026-06-01 12:00:00'));

        $this->assertTrue($result->allowed());
    }

    public function testDeniesWhenRequestIsAfterExpiry(): void
    {
        $policy = new ExpirationAssetAccessPolicy(new DateTimeImmutable('2026-01-01 00:00:00'));

        $result = $policy->evaluate($this->makeContext('2026-06-01 00:00:00'));

        $this->assertFalse($result->allowed());
    }

    public function testDenyReasonIsExpired(): void
    {
        $policy = new ExpirationAssetAccessPolicy(new DateTimeImmutable('2026-01-01 00:00:00'));

        $result = $policy->evaluate($this->makeContext('2026-06-01 00:00:00'));

        $this->assertSame('Expired', $result->reason());
    }

    public function testDeniesOneSecondAfterExpiry(): void
    {
        $policy = new ExpirationAssetAccessPolicy(new DateTimeImmutable('2026-06-01 12:00:00'));

        $result = $policy->evaluate($this->makeContext('2026-06-01 12:00:01'));

        $this->assertFalse($result->allowed());
    }

    public function testImplementsInterface(): void
    {
        $policy = new ExpirationAssetAccessPolicy(new DateTimeImmutable('+1 day'));

        $this->assertInstanceOf(AssetAccessPolicyInterface::class, $policy);
    }
}
