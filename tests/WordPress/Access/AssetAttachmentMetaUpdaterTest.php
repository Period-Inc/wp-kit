<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaUpdater;
use Period\WpKit\WordPress\Access\AssetFileMoveResult;
use Period\WpKit\WordPress\Access\WordPressAssetAttachmentMetaHookRegistrar;

final class AssetAttachmentMetaUpdaterTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** @return array<array{int,string,mixed}> */
    private function captureUpdater(): array
    {
        return [];
    }

    private function makeUpdater(array &$calls): AssetAttachmentMetaUpdater
    {
        return new AssetAttachmentMetaUpdater(
            function (int $id, string $key, mixed $value) use (&$calls): void {
                $calls[] = [$id, $key, $value];
            },
        );
    }

    private function protectedUpload(string $file = '/protected-uploads/file.pdf', string $url = '/asset-access?asset=protected-uploads%2Ffile.pdf'): array
    {
        return [
            'file'             => $file,
            'url'              => $url,
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success('/uploads/file.pdf', $file),
            'asset_url_rewritten' => true,
        ];
    }

    private function publicUpload(string $file = '/uploads/file.pdf'): array
    {
        return [
            'file' => $file,
            'url'  => 'https://example.com/uploads/file.pdf',
            'type' => 'application/pdf',
        ];
    }

    // -----------------------------------------------------------------------
    // Protected upload updates all meta
    // -----------------------------------------------------------------------

    public function testProtectedUploadUpdatesProtectedFlag(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(42, $this->protectedUpload());

        $keys = array_column($calls, 1);
        $this->assertContains('_period_asset_protected', $keys);
    }

    public function testProtectedUploadSetsProtectedFlagToOne(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(42, $this->protectedUpload());

        $flagCall = array_values(array_filter($calls, fn($c) => $c[1] === '_period_asset_protected'));
        $this->assertSame('1', $flagCall[0][2]);
    }

    public function testProtectedUploadUpdatesProtectedPath(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(42, $this->protectedUpload('/protected-uploads/secret.pdf'));

        $pathCall = array_values(array_filter($calls, fn($c) => $c[1] === '_period_asset_protected_path'));
        $this->assertSame('/protected-uploads/secret.pdf', $pathCall[0][2]);
    }

    public function testProtectedUploadUpdatesDeliveryUrl(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(42, $this->protectedUpload('/protected-uploads/file.pdf', '/asset-access?asset=x'));

        $urlCall = array_values(array_filter($calls, fn($c) => $c[1] === '_period_asset_delivery_url'));
        $this->assertSame('/asset-access?asset=x', $urlCall[0][2]);
    }

    public function testProtectedUploadPassesAttachmentIdToAllCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(99, $this->protectedUpload());

        foreach ($calls as [$id]) {
            $this->assertSame(99, $id);
        }
    }

    public function testProtectedUploadMakesThreeMetaCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(1, $this->protectedUpload());

        $this->assertCount(3, $calls);
    }

    // -----------------------------------------------------------------------
    // Public upload does not update meta
    // -----------------------------------------------------------------------

    public function testPublicUploadMakesNoMetaCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $updater->update(1, $this->publicUpload());

        $this->assertCount(0, $calls);
    }

    public function testUploadWithFailedMoveResultMakesNoMetaCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = [
            'file'             => '/protected-uploads/file.pdf',
            'url'              => '',
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::failure('/uploads/file.pdf', '/protected-uploads/file.pdf', 'disk full'),
            'error'            => 'disk full',
        ];

        $updater->update(1, $upload);

        $this->assertCount(0, $calls);
    }

    public function testUploadWithoutMoveResultMakesNoMetaCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = ['file' => '/uploads/x.pdf', 'url' => 'https://example.com/x.pdf', 'type' => 'application/pdf'];

        $updater->update(1, $upload);

        $this->assertCount(0, $calls);
    }

    // -----------------------------------------------------------------------
    // Missing delivery url still stores protected flag / path
    // -----------------------------------------------------------------------

    public function testMissingUrlKeyStoresProtectedFlag(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = [
            'file'             => '/protected-uploads/doc.pdf',
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success('/uploads/doc.pdf', '/protected-uploads/doc.pdf'),
        ];

        $updater->update(5, $upload);

        $keys = array_column($calls, 1);
        $this->assertContains('_period_asset_protected', $keys);
    }

    public function testMissingUrlKeyStoresProtectedPath(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = [
            'file'             => '/protected-uploads/doc.pdf',
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success('/uploads/doc.pdf', '/protected-uploads/doc.pdf'),
        ];

        $updater->update(5, $upload);

        $keys = array_column($calls, 1);
        $this->assertContains('_period_asset_protected_path', $keys);
    }

    public function testMissingUrlKeyDoesNotStoreDeliveryUrl(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = [
            'file'             => '/protected-uploads/doc.pdf',
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success('/uploads/doc.pdf', '/protected-uploads/doc.pdf'),
        ];

        $updater->update(5, $upload);

        $keys = array_column($calls, 1);
        $this->assertNotContains('_period_asset_delivery_url', $keys);
    }

    public function testMissingUrlKeyMakesTwoMetaCalls(): void
    {
        $calls   = [];
        $updater = $this->makeUpdater($calls);

        $upload = [
            'file'             => '/protected-uploads/doc.pdf',
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success('/uploads/doc.pdf', '/protected-uploads/doc.pdf'),
        ];

        $updater->update(5, $upload);

        $this->assertCount(2, $calls);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentMetaHookRegistrar
    // -----------------------------------------------------------------------

    public function testRegistrarCallsAddActionOnce(): void
    {
        $calls   = [];
        $dummy   = [];
        $updater = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$calls): void {
                $calls[] = [$hook, $cb, $priority];
            },
        );

        $registrar->register();

        $this->assertCount(1, $calls);
    }

    public function testRegistrarUsesDefaultHook(): void
    {
        $captured = null;
        $dummy    = [];
        $updater  = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register();

        $this->assertSame('add_attachment', $captured);
    }

    public function testRegistrarUsesDefaultPriority(): void
    {
        $captured = null;
        $dummy    = [];
        $updater  = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register();

        $this->assertSame(10, $captured);
    }

    public function testRegistrarPassesUpdaterUpdateAsCallback(): void
    {
        $capturedCallback = null;
        $dummy            = [];
        $updater          = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$capturedCallback): void {
                $capturedCallback = $cb;
            },
        );

        $registrar->register();

        $this->assertSame([$updater, 'update'], $capturedCallback);
    }

    public function testRegistrarSupportsCustomHook(): void
    {
        $captured = null;
        $dummy    = [];
        $updater  = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register('wp_async_task_after_call');

        $this->assertSame('wp_async_task_after_call', $captured);
    }

    public function testRegistrarSupportsCustomPriority(): void
    {
        $captured = null;
        $dummy    = [];
        $updater  = $this->makeUpdater($dummy);

        $registrar = new WordPressAssetAttachmentMetaHookRegistrar(
            $updater,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register('add_attachment', 20);

        $this->assertSame(20, $captured);
    }
}
