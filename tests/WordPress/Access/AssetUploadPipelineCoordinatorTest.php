<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetFileMoveResult;
use Period\WpKit\WordPress\Access\AssetFileMoverInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\AssetUploadInterceptor;
use Period\WpKit\WordPress\Access\AssetUploadMoveProcessor;
use Period\WpKit\WordPress\Access\AssetUploadPathResolver;
use Period\WpKit\WordPress\Access\AssetUploadPipelineCoordinator;
use Period\WpKit\WordPress\Access\AssetUploadUrlRewriteProcessor;
use Period\WpKit\WordPress\Access\DefaultProtectedAssetPathStrategy;
use Period\WpKit\WordPress\Access\ProxyAssetUrlRewriteStrategy;
use Period\WpKit\WordPress\Access\RoleBasedAssetUploadPolicy;

final class AssetUploadPipelineCoordinatorTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function contextFactory(array $upload): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: (string) ($upload['file'] ?? ''),
            assetUrl: '',
            currentUserId: 1,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );
    }

    private function makePublicInterceptor(): AssetUploadInterceptor
    {
        return new AssetUploadInterceptor(
            new RoleBasedAssetUploadPolicy([]),
            new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy()),
            fn(array $u) => $this->contextFactory($u),
        );
    }

    private function makeProtectedInterceptor(): AssetUploadInterceptor
    {
        return new AssetUploadInterceptor(
            new RoleBasedAssetUploadPolicy(['editor']),
            new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy()),
            fn(array $u) => $this->contextFactory($u),
        );
    }

    private function makeMoveProcessor(bool $succeeds, string $error = 'move failed'): AssetUploadMoveProcessor
    {
        $mover = new class($succeeds, $error) implements AssetFileMoverInterface {
            public function __construct(
                private readonly bool $succeeds,
                private readonly string $error,
            ) {}

            public function move(string $from, string $to): AssetFileMoveResult
            {
                return $this->succeeds
                    ? AssetFileMoveResult::success($from, $to)
                    : AssetFileMoveResult::failure($from, $to, $this->error);
            }
        };

        return new AssetUploadMoveProcessor($mover);
    }

    private function makeUrlRewriteProcessor(): AssetUploadUrlRewriteProcessor
    {
        return new AssetUploadUrlRewriteProcessor(
            new ProxyAssetUrlRewriteStrategy('/asset-access'),
        );
    }

    private function makeCoordinator(
        bool $protected,
        bool $moveSucceeds = true,
        string $moveError = 'move failed',
    ): AssetUploadPipelineCoordinator {
        return new AssetUploadPipelineCoordinator(
            $protected ? $this->makeProtectedInterceptor() : $this->makePublicInterceptor(),
            $this->makeMoveProcessor($moveSucceeds, $moveError),
            $this->makeUrlRewriteProcessor(),
        );
    }

    // -----------------------------------------------------------------------
    // Protected upload: intercept → move → url rewrite
    // -----------------------------------------------------------------------

    public function testProtectedUploadRewritesFilePath(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $result = $coordinator->process([
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('/protected-uploads/secret.pdf', $result['file']);
    }

    public function testProtectedUploadRewritesUrl(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $result = $coordinator->process([
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('/asset-access?asset=protected-uploads%2Fsecret.pdf', $result['url']);
    }

    public function testProtectedUploadSetsMoveResult(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $result = $coordinator->process([
            'file' => '/uploads/doc.pdf',
            'url'  => 'https://example.com/uploads/doc.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertInstanceOf(AssetFileMoveResult::class, $result['asset_move_result']);
        $this->assertTrue($result['asset_move_result']->isSuccess());
    }

    public function testProtectedUploadSetsUrlRewrittenFlag(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $result = $coordinator->process([
            'file' => '/uploads/img.jpg',
            'url'  => 'https://example.com/uploads/img.jpg',
            'type' => 'image/jpeg',
        ]);

        $this->assertTrue($result['asset_url_rewritten']);
    }

    // -----------------------------------------------------------------------
    // Public upload: intercept only
    // -----------------------------------------------------------------------

    public function testPublicUploadLeavesFilePathUnchanged(): void
    {
        $coordinator = $this->makeCoordinator(protected: false);

        $upload = ['file' => '/uploads/public.pdf', 'url' => 'https://example.com/uploads/public.pdf', 'type' => 'application/pdf'];
        $result = $coordinator->process($upload);

        $this->assertSame('/uploads/public.pdf', $result['file']);
    }

    public function testPublicUploadLeavesUrlUnchanged(): void
    {
        $coordinator = $this->makeCoordinator(protected: false);

        $upload = ['file' => '/uploads/public.pdf', 'url' => 'https://example.com/uploads/public.pdf', 'type' => 'application/pdf'];
        $result = $coordinator->process($upload);

        $this->assertSame('https://example.com/uploads/public.pdf', $result['url']);
    }

    public function testPublicUploadDoesNotSetMoveResult(): void
    {
        $coordinator = $this->makeCoordinator(protected: false);

        $result = $coordinator->process(['file' => '/uploads/public.pdf', 'url' => '', 'type' => 'application/pdf']);

        $this->assertArrayNotHasKey('asset_move_result', $result);
    }

    public function testPublicUploadDoesNotSetUrlRewrittenFlag(): void
    {
        $coordinator = $this->makeCoordinator(protected: false);

        $result = $coordinator->process(['file' => '/uploads/public.pdf', 'url' => '', 'type' => 'application/pdf']);

        $this->assertArrayNotHasKey('asset_url_rewritten', $result);
    }

    // -----------------------------------------------------------------------
    // Move failure stops url rewrite
    // -----------------------------------------------------------------------

    public function testMoveFailureSetsErrorKey(): void
    {
        $coordinator = $this->makeCoordinator(protected: true, moveSucceeds: false, moveError: 'disk full');

        $result = $coordinator->process([
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('disk full', $result['error']);
    }

    public function testMoveFailureDoesNotRewriteUrl(): void
    {
        $coordinator = $this->makeCoordinator(protected: true, moveSucceeds: false);

        $result = $coordinator->process([
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ]);

        $this->assertArrayNotHasKey('asset_url_rewritten', $result);
        $this->assertSame('https://example.com/uploads/secret.pdf', $result['url']);
    }

    public function testMoveFailureStoresMoveResult(): void
    {
        $coordinator = $this->makeCoordinator(protected: true, moveSucceeds: false, moveError: 'oops');

        $result = $coordinator->process([
            'file' => '/uploads/secret.pdf',
            'url'  => '',
            'type' => 'application/pdf',
        ]);

        $this->assertInstanceOf(AssetFileMoveResult::class, $result['asset_move_result']);
        $this->assertFalse($result['asset_move_result']->isSuccess());
    }

    // -----------------------------------------------------------------------
    // Existing upload error skips all processing
    // -----------------------------------------------------------------------

    public function testPreExistingErrorSkipsIntercept(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $upload = [
            'file'  => '/uploads/file.pdf',
            'url'   => 'https://example.com/uploads/file.pdf',
            'type'  => 'application/pdf',
            'error' => 'upload already failed',
        ];

        $result = $coordinator->process($upload);

        $this->assertSame('/uploads/file.pdf', $result['file']);
        $this->assertSame('https://example.com/uploads/file.pdf', $result['url']);
        $this->assertArrayNotHasKey('asset_move_result', $result);
        $this->assertArrayNotHasKey('asset_url_rewritten', $result);
    }

    public function testPreExistingErrorPreservesOriginalErrorMessage(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $upload = ['file' => '/uploads/file.pdf', 'url' => '', 'type' => 'application/pdf', 'error' => 'prior hook error'];
        $result = $coordinator->process($upload);

        $this->assertSame('prior hook error', $result['error']);
    }

    // -----------------------------------------------------------------------
    // Missing url does not fail
    // -----------------------------------------------------------------------

    public function testProtectedUploadWithoutUrlKeyDoesNotThrow(): void
    {
        $coordinator = $this->makeCoordinator(protected: true);

        $result = $coordinator->process(['file' => '/uploads/doc.pdf', 'type' => 'application/pdf']);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('error', $result);
    }

    public function testPublicUploadWithoutUrlKeyDoesNotThrow(): void
    {
        $coordinator = $this->makeCoordinator(protected: false);

        $result = $coordinator->process(['file' => '/uploads/doc.pdf', 'type' => 'application/pdf']);

        $this->assertIsArray($result);
    }

    // -----------------------------------------------------------------------
    // originalPath is preserved for move processor
    // -----------------------------------------------------------------------

    public function testMoveProcessorReceivesOriginalPath(): void
    {
        $coordinator = $this->makeCoordinator(protected: true, moveSucceeds: true);

        $result = $coordinator->process([
            'file' => '/uploads/original.pdf',
            'url'  => '',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('/uploads/original.pdf', $result['asset_move_result']->from());
    }

    public function testMoveProcessorReceivesProtectedPathAsDestination(): void
    {
        $coordinator = $this->makeCoordinator(protected: true, moveSucceeds: true);

        $result = $coordinator->process([
            'file' => '/uploads/original.pdf',
            'url'  => '',
            'type' => 'application/pdf',
        ]);

        $this->assertSame('/protected-uploads/original.pdf', $result['asset_move_result']->to());
    }
}
