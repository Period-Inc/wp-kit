<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessManager;
use Period\WpFramework\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpFramework\WordPress\Access\AssetAccessResult;
use Period\WpFramework\WordPress\Access\AssetDeliveryInterface;
use Period\WpFramework\WordPress\Access\AssetDeliveryResult;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\AssetRequestMatcher;
use Period\WpFramework\WordPress\Access\AssetStorageItem;
use Period\WpFramework\WordPress\Access\InMemoryAssetStorage;
use Period\WpFramework\WordPress\Access\NullAssetDelivery;
use Period\WpFramework\WordPress\Access\PublicAssetAccessPolicy;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessKernel;

final class WordPressAssetAccessKernelTest extends TestCase
{
    private function makeContext(
        string $assetPath = '/var/www/wp-content/uploads/file.pdf',
        string $assetUrl  = 'https://example.com/wp-content/uploads/file.pdf',
    ): AssetRequestContext {
        return new AssetRequestContext(
            assetPath: $assetPath,
            assetUrl: $assetUrl,
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
        return new AssetAccessManager(new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $ctx): AssetAccessResult
            {
                return AssetAccessResult::deny('Forbidden');
            }
        });
    }

    private function stubDelivery(AssetDeliveryResult $result): AssetDeliveryInterface
    {
        return new class($result) implements AssetDeliveryInterface {
            public function __construct(private readonly AssetDeliveryResult $result) {}

            public function deliver(AssetRequestContext $context): AssetDeliveryResult
            {
                return $this->result;
            }
        };
    }

    private function storageWith(string $path): InMemoryAssetStorage
    {
        return new InMemoryAssetStorage([
            new AssetStorageItem($path, null, null, null, null),
        ]);
    }

    // --- non-matching request ---

    public function testNonMatchingUrlReturnsNull(): void
    {
        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(['/wp-content/uploads/']),
            $this->allowManager(),
            new NullAssetDelivery(),
            new InMemoryAssetStorage([]),
        );

        $context = $this->makeContext(
            assetUrl: 'https://example.com/wp-content/themes/style.css',
        );

        $this->assertNull($kernel->handle($context));
    }

    // --- missing asset ---

    public function testMissingAssetReturns404(): void
    {
        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(),
            $this->allowManager(),
            new NullAssetDelivery(),
            new InMemoryAssetStorage([]),
        );

        $result = $kernel->handle($this->makeContext());

        $this->assertNotNull($result);
        $this->assertSame(404, $result->statusCode());
        $this->assertFalse($result->success());
    }

    // --- denied asset ---

    public function testDeniedAssetReturns403(): void
    {
        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(),
            $this->denyManager(),
            new NullAssetDelivery(),
            $this->storageWith('/var/www/wp-content/uploads/file.pdf'),
        );

        $result = $kernel->handle($this->makeContext());

        $this->assertNotNull($result);
        $this->assertSame(403, $result->statusCode());
        $this->assertFalse($result->success());
    }

    // --- allowed asset ---

    public function testAllowedAssetReturnsDeliveryResult(): void
    {
        $deliveryResult = AssetDeliveryResult::ok(200, [], 'content');

        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(),
            $this->allowManager(),
            $this->stubDelivery($deliveryResult),
            $this->storageWith('/var/www/wp-content/uploads/file.pdf'),
        );

        $result = $kernel->handle($this->makeContext());

        $this->assertSame($deliveryResult, $result);
    }

    // --- query string in URL ---

    public function testUrlWithQueryStringStillMatches(): void
    {
        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(),
            $this->allowManager(),
            new NullAssetDelivery(),
            $this->storageWith('/var/www/wp-content/uploads/file.pdf'),
        );

        $context = $this->makeContext(
            assetUrl: 'https://example.com/wp-content/uploads/file.pdf?v=42',
        );

        $result = $kernel->handle($context);

        // Storage returns null (path mismatch is fine), but match succeeded → returns 404 not null
        $this->assertNotNull($result);
    }

    // --- delivery is invoked only after auth passes ---

    public function testDeliveryNotCalledWhenDenied(): void
    {
        $called = false;
        $delivery = new class($called) implements AssetDeliveryInterface {
            public function __construct(private bool &$called) {}

            public function deliver(AssetRequestContext $context): AssetDeliveryResult
            {
                $this->called = true;
                return AssetDeliveryResult::ok();
            }
        };

        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(),
            $this->denyManager(),
            $delivery,
            $this->storageWith('/var/www/wp-content/uploads/file.pdf'),
        );

        $kernel->handle($this->makeContext());

        $this->assertFalse($called);
    }
}
