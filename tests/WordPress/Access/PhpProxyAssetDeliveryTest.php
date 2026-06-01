<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessManager;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetAccessResult;
use Period\WpKit\WordPress\Access\AssetDeliveryInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\AssetStorageItem;
use Period\WpKit\WordPress\Access\InMemoryAssetStorage;
use Period\WpKit\WordPress\Access\PhpProxyAssetDelivery;
use Period\WpKit\WordPress\Access\PublicAssetAccessPolicy;

final class PhpProxyAssetDeliveryTest extends TestCase
{
    private function makeContext(string $path = '/uploads/file.pdf'): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: $path,
            assetUrl: 'https://example.com' . $path,
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            requestTime: new DateTimeImmutable(),
        );
    }

    private function allowManager(): AssetAccessManager
    {
        return new AssetAccessManager(new PublicAssetAccessPolicy());
    }

    private function denyManager(): AssetAccessManager
    {
        $policy = new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $ctx): AssetAccessResult
            {
                return AssetAccessResult::deny('Not permitted');
            }
        };

        return new AssetAccessManager($policy);
    }

    public function testImplementsInterface(): void
    {
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), new InMemoryAssetStorage([]));

        $this->assertInstanceOf(AssetDeliveryInterface::class, $delivery);
    }

    public function testMissingAssetReturns404(): void
    {
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), new InMemoryAssetStorage([]));

        $result = $delivery->deliver($this->makeContext('/uploads/missing.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(404, $result->statusCode());
    }

    public function testDeniedAssetReturns403(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, null, null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->denyManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(403, $result->statusCode());
    }

    public function testAllowedAssetReturns200(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, null, null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertTrue($result->success());
        $this->assertSame(200, $result->statusCode());
    }

    public function testAllowedResultContainsXAssetPathHeader(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, null, null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertSame('/uploads/file.pdf', $result->headers()['X-Asset-Path']);
    }

    public function testMimeTypePassedInHeader(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, 'application/pdf', null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertSame('application/pdf', $result->headers()['X-Asset-Mime']);
    }

    public function testNoMimeHeaderWhenMimeIsNull(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, null, null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertArrayNotHasKey('X-Asset-Mime', $result->headers());
    }

    public function testBodyIsNullNoFileIo(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/file.pdf', null, 'application/pdf', null, null),
        ]);
        $delivery = new PhpProxyAssetDelivery($this->allowManager(), $storage);

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertNull($result->body());
    }
}
