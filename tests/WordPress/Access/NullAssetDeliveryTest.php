<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetDeliveryInterface;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\NullAssetDelivery;

final class NullAssetDeliveryTest extends TestCase
{
    private AssetRequestContext $context;

    protected function setUp(): void
    {
        $this->context = new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetDeliveryInterface::class, new NullAssetDelivery());
    }

    public function testReturns501(): void
    {
        $result = (new NullAssetDelivery())->deliver($this->context);

        $this->assertSame(501, $result->statusCode());
    }

    public function testIsNotSuccess(): void
    {
        $result = (new NullAssetDelivery())->deliver($this->context);

        $this->assertFalse($result->success());
    }

    public function testBodyDescribesNotImplemented(): void
    {
        $result = (new NullAssetDelivery())->deliver($this->context);

        $this->assertSame('Asset delivery not implemented', $result->body());
    }
}
