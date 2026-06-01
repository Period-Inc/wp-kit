<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentImageSrcFilter;
use Period\WpKit\WordPress\Access\AssetAttachmentJsPrepareFilter;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaReader;
use Period\WpKit\WordPress\Access\AssetUrlRewriteStrategyInterface;
use Period\WpKit\WordPress\Access\WordPressAssetAttachmentDerivedFilterHookRegistrar;

final class AssetAttachmentDerivedFiltersTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeReader(
        bool    $protected     = true,
        ?string $protectedPath = '/protected-uploads/file.jpg',
    ): AssetAttachmentMetaReader {
        return new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use ($protected, $protectedPath): mixed {
                return match ($key) {
                    '_period_asset_protected'      => $protected ? '1' : '',
                    '_period_asset_protected_path' => $protectedPath ?? '',
                    default                        => '',
                };
            },
        );
    }

    private function makeStrategy(string $prefix = '/asset-access'): AssetUrlRewriteStrategyInterface
    {
        return new class ($prefix) implements AssetUrlRewriteStrategyInterface {
            public function __construct(private readonly string $prefix) {}
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                return $this->prefix . '?asset=' . ltrim($protectedPath, '/');
            }
        };
    }

    private function makeImageSrcFilter(
        bool    $protected     = true,
        ?string $protectedPath = '/protected-uploads/file.jpg',
        string  $strategyPrefix = '/asset-access',
    ): AssetAttachmentImageSrcFilter {
        return new AssetAttachmentImageSrcFilter(
            $this->makeReader($protected, $protectedPath),
            $this->makeStrategy($strategyPrefix),
        );
    }

    private function makeJsPrepareFilter(
        bool    $protected     = true,
        ?string $protectedPath = '/protected-uploads/file.jpg',
        string  $strategyPrefix = '/asset-access',
    ): AssetAttachmentJsPrepareFilter {
        return new AssetAttachmentJsPrepareFilter(
            $this->makeReader($protected, $protectedPath),
            $this->makeStrategy($strategyPrefix),
        );
    }

    private function makeAttachment(int $id): object
    {
        return new class ($id) {
            public int $ID;
            public function __construct(int $id) { $this->ID = $id; }
        };
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentImageSrcFilter — false passthrough
    // -----------------------------------------------------------------------

    public function testFalseIsReturnedUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter();

        $this->assertFalse($filter->filter(false, 1));
    }

    public function testFalseIsReturnedEvenForProtectedAttachment(): void
    {
        $filter = $this->makeImageSrcFilter(protected: true);

        $this->assertFalse($filter->filter(false, 1));
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentImageSrcFilter — public image unchanged
    // -----------------------------------------------------------------------

    public function testPublicImageIsReturnedUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter(protected: false);
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame($image, $result);
    }

    public function testPublicImageUrlIsNotRewritten(): void
    {
        $filter = $this->makeImageSrcFilter(protected: false);
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame('https://example.com/uploads/photo.jpg', $result[0]);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentImageSrcFilter — empty protected path
    // -----------------------------------------------------------------------

    public function testEmptyProtectedPathReturnsImageUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter(protected: true, protectedPath: '');
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame($image, $result);
    }

    public function testNullProtectedPathReturnsImageUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter(protected: true, protectedPath: null);
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame($image, $result);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentImageSrcFilter — protected image rewritten
    // -----------------------------------------------------------------------

    public function testProtectedImageUrlIsRewritten(): void
    {
        $filter = $this->makeImageSrcFilter(protected: true, protectedPath: '/protected-uploads/photo.jpg');
        $image  = ['https://example.com/protected-uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame('/asset-access?asset=protected-uploads/photo.jpg', $result[0]);
    }

    public function testProtectedImageWidthIsUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter();
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame(800, $result[1]);
    }

    public function testProtectedImageHeightIsUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter();
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertSame(600, $result[2]);
    }

    public function testProtectedImageIntermediateFlagIsUnchanged(): void
    {
        $filter = $this->makeImageSrcFilter();
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, true];

        $result = $filter->filter($image, 1);

        $this->assertTrue($result[3]);
    }

    public function testProtectedImageResultIsStillArray(): void
    {
        $filter = $this->makeImageSrcFilter();
        $image  = ['https://example.com/uploads/photo.jpg', 800, 600, false];

        $result = $filter->filter($image, 1);

        $this->assertIsArray($result);
    }

    public function testProtectedImageStrategyReceivesProtectedPath(): void
    {
        $receivedPath = null;
        $strategy     = new class ($receivedPath) implements AssetUrlRewriteStrategyInterface {
            public function __construct(private ?string &$path) {}
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                $this->path = $protectedPath;
                return '/rewritten';
            }
        };
        $filter = new AssetAttachmentImageSrcFilter(
            $this->makeReader(true, '/protected-uploads/file.jpg'),
            $strategy,
        );

        $filter->filter(['https://example.com/photo.jpg', 800, 600, false], 1);

        $this->assertSame('/protected-uploads/file.jpg', $receivedPath);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentJsPrepareFilter — public attachment unchanged
    // -----------------------------------------------------------------------

    public function testPublicAttachmentResponseIsUnchanged(): void
    {
        $filter     = $this->makeJsPrepareFilter(protected: false);
        $response   = ['url' => 'https://example.com/uploads/file.jpg', 'icon' => 'page.png'];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame($response, $result);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentJsPrepareFilter — empty protected path
    // -----------------------------------------------------------------------

    public function testEmptyProtectedPathLeavesResponseUnchanged(): void
    {
        $filter     = $this->makeJsPrepareFilter(protected: true, protectedPath: null);
        $response   = ['url' => 'https://example.com/uploads/file.jpg', 'icon' => 'page.png'];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame($response, $result);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentJsPrepareFilter — url rewritten
    // -----------------------------------------------------------------------

    public function testProtectedResponseUrlIsRewritten(): void
    {
        $filter     = $this->makeJsPrepareFilter(protected: true, protectedPath: '/protected-uploads/file.jpg');
        $response   = ['url' => 'https://example.com/protected-uploads/file.jpg', 'icon' => 'page.png'];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame('/asset-access?asset=protected-uploads/file.jpg', $result['url']);
    }

    public function testAttachmentIdIsReadFromObjectProperty(): void
    {
        $seenId  = null;
        $reader  = new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use (&$seenId): mixed {
                $seenId = $id;
                return match ($key) {
                    '_period_asset_protected'      => '1',
                    '_period_asset_protected_path' => '/protected-uploads/file.jpg',
                    default                        => '',
                };
            },
        );
        $filter     = new AssetAttachmentJsPrepareFilter($reader, $this->makeStrategy());
        $attachment = $this->makeAttachment(77);

        $filter->filter(['url' => 'https://example.com/file.jpg'], $attachment, []);

        $this->assertSame(77, $seenId);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentJsPrepareFilter — icon unchanged
    // -----------------------------------------------------------------------

    public function testIconIsNotRewritten(): void
    {
        $filter     = $this->makeJsPrepareFilter(protected: true);
        $response   = [
            'url'  => 'https://example.com/protected-uploads/file.jpg',
            'icon' => 'https://example.com/wp-includes/images/media/document.png',
        ];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame('https://example.com/wp-includes/images/media/document.png', $result['icon']);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentJsPrepareFilter — sizes urls rewritten
    // -----------------------------------------------------------------------

    public function testSizesUrlsAreRewritten(): void
    {
        $filter   = $this->makeJsPrepareFilter(protected: true, protectedPath: '/protected-uploads/photo.jpg');
        $response = [
            'url'  => 'https://example.com/protected-uploads/photo.jpg',
            'sizes' => [
                'thumbnail' => ['url' => 'https://example.com/protected-uploads/photo-150x150.jpg', 'width' => 150, 'height' => 150],
                'medium'    => ['url' => 'https://example.com/protected-uploads/photo-300x200.jpg', 'width' => 300, 'height' => 200],
            ],
        ];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame('/asset-access?asset=protected-uploads/photo.jpg', $result['sizes']['thumbnail']['url']);
        $this->assertSame('/asset-access?asset=protected-uploads/photo.jpg', $result['sizes']['medium']['url']);
    }

    public function testSizeDimensionsAreUnchangedAfterRewrite(): void
    {
        $filter   = $this->makeJsPrepareFilter(protected: true);
        $response = [
            'url'  => 'https://example.com/protected-uploads/photo.jpg',
            'sizes' => [
                'thumbnail' => ['url' => 'https://example.com/protected-uploads/photo-150x150.jpg', 'width' => 150, 'height' => 150],
            ],
        ];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertSame(150, $result['sizes']['thumbnail']['width']);
        $this->assertSame(150, $result['sizes']['thumbnail']['height']);
    }

    public function testMissingSizesKeyIsHandledGracefully(): void
    {
        $filter     = $this->makeJsPrepareFilter(protected: true);
        $response   = ['url' => 'https://example.com/protected-uploads/file.jpg'];
        $attachment = $this->makeAttachment(1);

        $result = $filter->filter($response, $attachment, []);

        $this->assertArrayNotHasKey('sizes', $result);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentDerivedFilterHookRegistrar — hook registration
    // -----------------------------------------------------------------------

    private function makeRegistrar(array &$filterCalls): WordPressAssetAttachmentDerivedFilterHookRegistrar
    {
        return new WordPressAssetAttachmentDerivedFilterHookRegistrar(
            $this->makeImageSrcFilter(),
            $this->makeJsPrepareFilter(),
            function () use (&$filterCalls): void { $filterCalls[] = func_get_args(); },
        );
    }

    public function testRegisterCallsAddFilterTwice(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $this->assertCount(2, $calls);
    }

    public function testRegisterHooksWpGetAttachmentImageSrc(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $hooks = array_column($calls, 0);
        $this->assertContains('wp_get_attachment_image_src', $hooks);
    }

    public function testRegisterHooksWpPrepareAttachmentForJs(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $hooks = array_column($calls, 0);
        $this->assertContains('wp_prepare_attachment_for_js', $hooks);
    }

    public function testImageSrcFilterAcceptsFourArgs(): void
    {
        $capturedArgs = [];
        $registrar    = new WordPressAssetAttachmentDerivedFilterHookRegistrar(
            $this->makeImageSrcFilter(),
            $this->makeJsPrepareFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs = 1) use (&$capturedArgs): void {
                $capturedArgs[$hook] = $acceptedArgs;
            },
        );

        $registrar->register();

        $this->assertSame(4, $capturedArgs['wp_get_attachment_image_src']);
    }

    public function testJsPrepareFilterAcceptsThreeArgs(): void
    {
        $capturedArgs = [];
        $registrar    = new WordPressAssetAttachmentDerivedFilterHookRegistrar(
            $this->makeImageSrcFilter(),
            $this->makeJsPrepareFilter(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs = 1) use (&$capturedArgs): void {
                $capturedArgs[$hook] = $acceptedArgs;
            },
        );

        $registrar->register();

        $this->assertSame(3, $capturedArgs['wp_prepare_attachment_for_js']);
    }
}
