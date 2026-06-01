<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetUploadUrlRewriteProcessor;
use Period\WpKit\WordPress\Access\AssetUrlRewriteStrategyInterface;
use Period\WpKit\WordPress\Access\ProxyAssetUrlRewriteStrategy;

final class AssetUploadUrlRewriteTest extends TestCase
{
    private function makeProxy(string $base = '/asset-access'): ProxyAssetUrlRewriteStrategy
    {
        return new ProxyAssetUrlRewriteStrategy($base);
    }

    // -----------------------------------------------------------------------
    // ProxyAssetUrlRewriteStrategy
    // -----------------------------------------------------------------------

    public function testProxyRewriteProducesExpectedUrl(): void
    {
        $strategy = $this->makeProxy('/asset-access');

        $result = $strategy->rewrite(
            'https://example.com/uploads/file.pdf',
            '/protected-uploads/file.pdf',
        );

        $this->assertSame('/asset-access?asset=protected-uploads%2Ffile.pdf', $result);
    }

    public function testProxyRewriteStripsLeadingSlashFromPath(): void
    {
        $strategy = $this->makeProxy('/asset-access');

        $result = $strategy->rewrite('', '/protected-uploads/photo.jpg');

        $this->assertStringNotContainsString('%2F', substr($result, 0, strlen('/asset-access?asset=')));
        $this->assertStringStartsWith('protected-uploads', rawurldecode(explode('=', $result, 2)[1]));
    }

    public function testProxyRewriteEncodesSubdirectorySlashes(): void
    {
        $strategy = $this->makeProxy('/asset-access');

        $result = $strategy->rewrite('', '/protected-uploads/2026/05/report.pdf');

        $this->assertSame(
            '/asset-access?asset=protected-uploads%2F2026%2F05%2Freport.pdf',
            $result,
        );
    }

    public function testProxyRewriteIsDeterministic(): void
    {
        $strategy = $this->makeProxy('/asset-access');

        $a = $strategy->rewrite('https://example.com/uploads/x.pdf', '/protected-uploads/x.pdf');
        $b = $strategy->rewrite('https://example.com/uploads/x.pdf', '/protected-uploads/x.pdf');

        $this->assertSame($a, $b);
    }

    public function testProxyRewriteIgnoresOriginalUrl(): void
    {
        $strategy = $this->makeProxy('/asset-access');

        $r1 = $strategy->rewrite('https://example.com/uploads/x.pdf', '/protected-uploads/x.pdf');
        $r2 = $strategy->rewrite('https://other.com/different/path.pdf', '/protected-uploads/x.pdf');

        $this->assertSame($r1, $r2);
    }

    public function testProxyRewriteUsesCustomBaseUrl(): void
    {
        $strategy = $this->makeProxy('https://cdn.example.com/protected');

        $result = $strategy->rewrite('', '/protected-uploads/image.png');

        $this->assertStringStartsWith('https://cdn.example.com/protected?asset=', $result);
    }

    public function testProxyImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetUrlRewriteStrategyInterface::class, $this->makeProxy());
    }

    // -----------------------------------------------------------------------
    // AssetUploadUrlRewriteProcessor
    // -----------------------------------------------------------------------

    public function testProcessRewritesUrl(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy('/asset-access'));

        $upload = [
            'file' => '/protected-uploads/photo.jpg',
            'url'  => 'https://example.com/uploads/photo.jpg',
            'type' => 'image/jpeg',
        ];

        $result = $processor->process($upload);

        $this->assertSame('/asset-access?asset=protected-uploads%2Fphoto.jpg', $result['url']);
    }

    public function testProcessSetsRewrittenFlag(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $result = $processor->process([
            'file' => '/protected-uploads/doc.pdf',
            'url'  => 'https://example.com/uploads/doc.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertTrue($result['asset_url_rewritten']);
    }

    public function testProcessLeavesFilepathUnchanged(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $upload = [
            'file' => '/protected-uploads/photo.jpg',
            'url'  => 'https://example.com/uploads/photo.jpg',
            'type' => 'image/jpeg',
        ];

        $result = $processor->process($upload);

        $this->assertSame('/protected-uploads/photo.jpg', $result['file']);
    }

    public function testProcessLeavesMimeTypeUnchanged(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $result = $processor->process([
            'file' => '/protected-uploads/doc.pdf',
            'url'  => 'https://example.com/uploads/doc.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('application/pdf', $result['type']);
    }

    public function testProcessMissingUrlKeyReturnsUploadUnchanged(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $upload = ['file' => '/protected-uploads/doc.pdf', 'type' => 'application/pdf'];

        $result = $processor->process($upload);

        $this->assertSame($upload, $result);
    }

    public function testProcessMissingUrlKeyDoesNotSetRewrittenFlag(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $result = $processor->process(['file' => '/protected-uploads/doc.pdf', 'type' => 'application/pdf']);

        $this->assertArrayNotHasKey('asset_url_rewritten', $result);
    }

    public function testProcessIsDeterministic(): void
    {
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy('/asset-access'));

        $upload = ['file' => '/protected-uploads/x.pdf', 'url' => 'https://example.com/uploads/x.pdf', 'type' => 'application/pdf'];

        $r1 = $processor->process($upload);
        $r2 = $processor->process($upload);

        $this->assertSame($r1['url'], $r2['url']);
    }

    public function testProcessDoesNotTouchFilesystem(): void
    {
        // Completes without any filesystem interaction — pure string manipulation.
        $processor = new AssetUploadUrlRewriteProcessor($this->makeProxy());

        $result = $processor->process([
            'file' => '/protected-uploads/nonexistent-xyz.pdf',
            'url'  => 'https://example.com/uploads/nonexistent-xyz.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertIsString($result['url']);
    }

    public function testProcessWithCustomStrategy(): void
    {
        $strategy = new class implements AssetUrlRewriteStrategyInterface {
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                return 'https://delivery.example.com/serve/' . ltrim($protectedPath, '/');
            }
        };

        $processor = new AssetUploadUrlRewriteProcessor($strategy);

        $result = $processor->process([
            'file' => '/protected-uploads/img.png',
            'url'  => 'https://example.com/uploads/img.png',
            'type' => 'image/png',
        ]);

        $this->assertSame('https://delivery.example.com/serve/protected-uploads/img.png', $result['url']);
    }
}
