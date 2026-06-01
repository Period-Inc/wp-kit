<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\LoggedInAssetAccessPolicy;
use Period\WpKit\WordPress\Access\PrivateAssetAccessPolicy;
use Period\WpKit\WordPress\Access\PublicAssetAccessPolicy;

final class DefaultPoliciesTest extends TestCase
{
    private function makeContext(int $userId): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: $userId,
            currentUserRoles: [],
            requestTime: new DateTimeImmutable(),
        );
    }

    // --- LoggedInAssetAccessPolicy ---

    public function testLoggedInAllowsAuthenticatedUser(): void
    {
        $result = (new LoggedInAssetAccessPolicy())->evaluate($this->makeContext(1));

        $this->assertTrue($result->allowed());
    }

    public function testLoggedInAllowsHighUserId(): void
    {
        $result = (new LoggedInAssetAccessPolicy())->evaluate($this->makeContext(9999));

        $this->assertTrue($result->allowed());
    }

    public function testLoggedInDeniesGuest(): void
    {
        $result = (new LoggedInAssetAccessPolicy())->evaluate($this->makeContext(0));

        $this->assertFalse($result->allowed());
    }

    public function testLoggedInDenyReasonIsLoginRequired(): void
    {
        $result = (new LoggedInAssetAccessPolicy())->evaluate($this->makeContext(0));

        $this->assertSame('Login required', $result->reason());
    }

    public function testLoggedInImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetAccessPolicyInterface::class, new LoggedInAssetAccessPolicy());
    }

    // --- PublicAssetAccessPolicy ---

    public function testPublicAlwaysAllowsGuest(): void
    {
        $result = (new PublicAssetAccessPolicy())->evaluate($this->makeContext(0));

        $this->assertTrue($result->allowed());
    }

    public function testPublicAlwaysAllowsAuthenticatedUser(): void
    {
        $result = (new PublicAssetAccessPolicy())->evaluate($this->makeContext(42));

        $this->assertTrue($result->allowed());
    }

    public function testPublicImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetAccessPolicyInterface::class, new PublicAssetAccessPolicy());
    }

    // --- PrivateAssetAccessPolicy ---

    public function testPrivateAlwaysDeniesGuest(): void
    {
        $result = (new PrivateAssetAccessPolicy())->evaluate($this->makeContext(0));

        $this->assertFalse($result->allowed());
    }

    public function testPrivateAlwaysDeniesAuthenticatedUser(): void
    {
        $result = (new PrivateAssetAccessPolicy())->evaluate($this->makeContext(1));

        $this->assertFalse($result->allowed());
    }

    public function testPrivateDenyReasonIsPrivateAsset(): void
    {
        $result = (new PrivateAssetAccessPolicy())->evaluate($this->makeContext(0));

        $this->assertSame('Private asset', $result->reason());
    }

    public function testPrivateImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetAccessPolicyInterface::class, new PrivateAssetAccessPolicy());
    }
}
