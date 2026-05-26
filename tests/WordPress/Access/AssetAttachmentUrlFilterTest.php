<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAttachmentUrlFilter;
use Period\WpFramework\WordPress\Access\AssetUrlRewriteStrategyInterface;
use Period\WpFramework\WordPress\Access\ProxyAssetUrlRewriteStrategy;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentUrlFilterHookRegistrar;

final class AssetAttachmentUrlFilterTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a fake getMeta callable backed by a simple key→value map.
     *
     * @param array<string,mixed> $meta
     */
    private function fakeMeta(array $meta): callable
    {
        return function (int $id, string $key, bool $single) use ($meta): mixed {
            return $meta[$key] ?? '';
        };
    }

    private function makeFilter(
        bool   $protected      = false,
        string $protectedPath  = '',
        string $deliveryBase   = '/asset-access',
    ): AssetAttachmentUrlFilter {
        return new AssetAttachmentUrlFilter(
            $this->fakeMeta([
                '_period_asset_protected'      => $protected ? '1' : '',
                '_period_asset_protected_path' => $protectedPath,
            ]),
            new ProxyAssetUrlRewriteStrategy($deliveryBase),
        );
    }

    // -----------------------------------------------------------------------
    // Public attachment — returns original URL
    // -----------------------------------------------------------------------

    public function testPublicAttachmentReturnsOriginalUrl(): void
    {
        $filter = $this->makeFilter(protected: false);

        $result = $filter->filter('https://example.com/uploads/photo.jpg', 42);

        $this->assertSame('https://example.com/uploads/photo.jpg', $result);
    }

    public function testFalsyProtectedMetaReturnsOriginalUrl(): void
    {
        $urlFilter = new AssetAttachmentUrlFilter(
            $this->fakeMeta(['_period_asset_protected' => '', '_period_asset_protected_path' => '/protected-uploads/x.pdf']),
            new ProxyAssetUrlRewriteStrategy('/asset-access'),
        );

        $result = $urlFilter->filter('https://example.com/uploads/x.pdf', 1);

        $this->assertSame('https://example.com/uploads/x.pdf', $result);
    }

    public function testZeroStringProtectedMetaReturnsOriginalUrl(): void
    {
        $urlFilter = new AssetAttachmentUrlFilter(
            $this->fakeMeta(['_period_asset_protected' => '0', '_period_asset_protected_path' => '/protected-uploads/x.pdf']),
            new ProxyAssetUrlRewriteStrategy('/asset-access'),
        );

        $result = $urlFilter->filter('https://example.com/uploads/x.pdf', 1);

        $this->assertSame('https://example.com/uploads/x.pdf', $result);
    }

    // -----------------------------------------------------------------------
    // Protected attachment — rewrites URL
    // -----------------------------------------------------------------------

    public function testProtectedAttachmentRewritesUrl(): void
    {
        $filter = $this->makeFilter(
            protected: true,
            protectedPath: '/protected-uploads/secret.pdf',
        );

        $result = $filter->filter('https://example.com/uploads/secret.pdf', 7);

        $this->assertSame('/asset-access?asset=protected-uploads%2Fsecret.pdf', $result);
    }

    public function testProtectedAttachmentWithSubdirectoryRewritesUrl(): void
    {
        $filter = $this->makeFilter(
            protected: true,
            protectedPath: '/protected-uploads/2026/05/report.pdf',
        );

        $result = $filter->filter('https://example.com/uploads/2026/05/report.pdf', 3);

        $this->assertSame('/asset-access?asset=protected-uploads%2F2026%2F05%2Freport.pdf', $result);
    }

    // -----------------------------------------------------------------------
    // Missing protected path — falls back to original URL
    // -----------------------------------------------------------------------

    public function testMissingProtectedPathReturnsOriginalUrl(): void
    {
        $filter = $this->makeFilter(protected: true, protectedPath: '');

        $result = $filter->filter('https://example.com/uploads/orphan.pdf', 5);

        $this->assertSame('https://example.com/uploads/orphan.pdf', $result);
    }

    // -----------------------------------------------------------------------
    // Strategy receives original URL and protected path
    // -----------------------------------------------------------------------

    public function testStrategyReceivesOriginalUrl(): void
    {
        $capturedOriginalUrl = null;

        $strategy = new class($capturedOriginalUrl) implements AssetUrlRewriteStrategyInterface {
            public function __construct(private mixed &$capture) {}

            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                $this->capture = $originalUrl;
                return '/rewritten';
            }
        };

        $urlFilter = new AssetAttachmentUrlFilter(
            $this->fakeMeta([
                '_period_asset_protected'      => '1',
                '_period_asset_protected_path' => '/protected-uploads/file.pdf',
            ]),
            $strategy,
        );

        $urlFilter->filter('https://example.com/uploads/file.pdf', 1);

        $this->assertSame('https://example.com/uploads/file.pdf', $capturedOriginalUrl);
    }

    public function testStrategyReceivesProtectedPath(): void
    {
        $capturedPath = null;

        $strategy = new class($capturedPath) implements AssetUrlRewriteStrategyInterface {
            public function __construct(private mixed &$capture) {}

            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                $this->capture = $protectedPath;
                return '/rewritten';
            }
        };

        $urlFilter = new AssetAttachmentUrlFilter(
            $this->fakeMeta([
                '_period_asset_protected'      => '1',
                '_period_asset_protected_path' => '/protected-uploads/target.pdf',
            ]),
            $strategy,
        );

        $urlFilter->filter('https://example.com/uploads/target.pdf', 1);

        $this->assertSame('/protected-uploads/target.pdf', $capturedPath);
    }

    public function testFilterReturnsStrategyResult(): void
    {
        $strategy = new class implements AssetUrlRewriteStrategyInterface {
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                return 'https://cdn.example.com/serve/' . ltrim($protectedPath, '/');
            }
        };

        $urlFilter = new AssetAttachmentUrlFilter(
            $this->fakeMeta([
                '_period_asset_protected'      => '1',
                '_period_asset_protected_path' => '/protected-uploads/img.png',
            ]),
            $strategy,
        );

        $result = $urlFilter->filter('https://example.com/uploads/img.png', 1);

        $this->assertSame('https://cdn.example.com/serve/protected-uploads/img.png', $result);
    }

    // -----------------------------------------------------------------------
    // getMeta is called with correct arguments
    // -----------------------------------------------------------------------

    public function testGetMetaCalledWithAttachmentIdForProtectedCheck(): void
    {
        $capturedId = null;

        $getMeta = function (int $id, string $key, bool $single) use (&$capturedId): mixed {
            if ($key === '_period_asset_protected') {
                $capturedId = $id;
            }
            return '';
        };

        $urlFilter = new AssetAttachmentUrlFilter(
            $getMeta,
            new ProxyAssetUrlRewriteStrategy('/asset-access'),
        );

        $urlFilter->filter('https://example.com/uploads/x.pdf', 77);

        $this->assertSame(77, $capturedId);
    }

    public function testGetMetaNotCalledForProtectedPathWhenNotProtected(): void
    {
        $pathQueryCount = 0;

        $getMeta = function (int $id, string $key, bool $single) use (&$pathQueryCount): mixed {
            if ($key === '_period_asset_protected_path') {
                $pathQueryCount++;
            }
            return '';
        };

        $urlFilter = new AssetAttachmentUrlFilter(
            $getMeta,
            new ProxyAssetUrlRewriteStrategy('/asset-access'),
        );

        $urlFilter->filter('https://example.com/uploads/x.pdf', 1);

        $this->assertSame(0, $pathQueryCount);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentUrlFilterHookRegistrar
    // -----------------------------------------------------------------------

    private function makeRegistrarFilter(): AssetAttachmentUrlFilter
    {
        return $this->makeFilter();
    }

    public function testRegistrarCallsAddFilterOnce(): void
    {
        $calls = [];

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$calls): void {
                $calls[] = [$hook, $cb, $priority, $acceptedArgs];
            },
        );

        $registrar->register();

        $this->assertCount(1, $calls);
    }

    public function testRegistrarUsesDefaultHook(): void
    {
        $captured = null;

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register();

        $this->assertSame('wp_get_attachment_url', $captured);
    }

    public function testRegistrarUsesDefaultPriority(): void
    {
        $captured = null;

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register();

        $this->assertSame(10, $captured);
    }

    public function testRegistrarPassesAcceptedArgsTwoToAddFilter(): void
    {
        $captured = null;

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$captured): void {
                $captured = $acceptedArgs;
            },
        );

        $registrar->register();

        $this->assertSame(2, $captured);
    }

    public function testRegistrarPassesFilterMethodAsCallback(): void
    {
        $capturedCb = null;
        $filter     = $this->makeRegistrarFilter();

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $filter,
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$capturedCb): void {
                $capturedCb = $cb;
            },
        );

        $registrar->register();

        $this->assertSame([$filter, 'filter'], $capturedCb);
    }

    public function testRegistrarSupportsCustomHook(): void
    {
        $captured = null;

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register('wp_get_attachment_link');

        $this->assertSame('wp_get_attachment_link', $captured);
    }

    public function testRegistrarSupportsCustomPriority(): void
    {
        $captured = null;

        $registrar = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            $this->makeRegistrarFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register('wp_get_attachment_url', 20);

        $this->assertSame(20, $captured);
    }
}
