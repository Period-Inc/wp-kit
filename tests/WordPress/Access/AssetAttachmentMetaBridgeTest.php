<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaBridge;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaUpdater;
use Period\WpFramework\WordPress\Access\AssetFileMoveResult;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentMetaBridgeHookRegistrar;

final class AssetAttachmentMetaBridgeTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeUpdater(array &$metaCalls): AssetAttachmentMetaUpdater
    {
        return new AssetAttachmentMetaUpdater(
            function (int $id, string $key, mixed $value) use (&$metaCalls): void {
                $metaCalls[] = [$id, $key, $value];
            },
        );
    }

    /** @return array<string,mixed> */
    private function protectedUpload(string $file = '/protected-uploads/file.pdf'): array
    {
        return [
            'file'             => $file,
            'type'             => 'application/pdf',
            'asset_move_result' => AssetFileMoveResult::success(
                '/uploads/' . basename($file),
                $file,
            ),
        ];
    }

    /** @return array<string,mixed> */
    private function publicUpload(string $file = '/uploads/file.pdf'): array
    {
        return ['file' => $file, 'url' => '', 'type' => 'application/pdf'];
    }

    private function makeBridge(array &$metaCalls): AssetAttachmentMetaBridge
    {
        return new AssetAttachmentMetaBridge($this->makeUpdater($metaCalls));
    }

    // -----------------------------------------------------------------------
    // rememberUpload
    // -----------------------------------------------------------------------

    public function testRememberUploadReturnsOriginalUpload(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);
        $upload    = $this->protectedUpload();

        $result = $bridge->rememberUpload($upload);

        $this->assertSame($upload, $result);
    }

    public function testRememberUploadReturnsPublicUploadUnchanged(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);
        $upload    = $this->publicUpload();

        $result = $bridge->rememberUpload($upload);

        $this->assertSame($upload, $result);
    }

    // -----------------------------------------------------------------------
    // updateAttachment — upload remembered
    // -----------------------------------------------------------------------

    public function testUpdateAttachmentCallsUpdaterWhenUploadRemembered(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(42);

        $this->assertNotEmpty($metaCalls);
    }

    public function testUpdateAttachmentPassesCorrectAttachmentId(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(99);

        foreach ($metaCalls as [$id]) {
            $this->assertSame(99, $id);
        }
    }

    public function testUpdateAttachmentPassesRememberedUploadToUpdater(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload('/protected-uploads/secret.pdf'));
        $bridge->updateAttachment(5);

        $pathCall = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertSame('/protected-uploads/secret.pdf', $pathCall[0][2]);
    }

    // -----------------------------------------------------------------------
    // updateAttachment — no upload remembered
    // -----------------------------------------------------------------------

    public function testUpdateAttachmentDoesNothingWhenNoUploadRemembered(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->updateAttachment(1);

        $this->assertCount(0, $metaCalls);
    }

    // -----------------------------------------------------------------------
    // upload is cleared after update
    // -----------------------------------------------------------------------

    public function testUploadIsClearedAfterUpdate(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(10);

        $callsAfterFirst = count($metaCalls);

        $bridge->updateAttachment(10);

        $this->assertSame($callsAfterFirst, count($metaCalls));
    }

    public function testSecondUpdateAttachmentWithoutRememberDoesNothing(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(1);
        $bridge->updateAttachment(2);

        $ids = array_unique(array_column($metaCalls, 0));
        $this->assertNotContains(2, $ids);
    }

    // -----------------------------------------------------------------------
    // second upload replaces first
    // -----------------------------------------------------------------------

    public function testSecondRememberReplacesFirstUpload(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->rememberUpload($this->protectedUpload('/protected-uploads/first.pdf'));
        $bridge->rememberUpload($this->protectedUpload('/protected-uploads/second.pdf'));
        $bridge->updateAttachment(7);

        $pathCall = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertSame('/protected-uploads/second.pdf', $pathCall[0][2]);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentMetaBridgeHookRegistrar
    // -----------------------------------------------------------------------

    private function makeBridgeForRegistrar(): AssetAttachmentMetaBridge
    {
        $dummy = [];

        return new AssetAttachmentMetaBridge($this->makeUpdater($dummy));
    }

    public function testRegistrarCallsAddFilter(): void
    {
        $filterCalls = [];

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $this->makeBridgeForRegistrar(),
            function (string $hook, callable $cb, int $priority) use (&$filterCalls): void {
                $filterCalls[] = [$hook, $cb, $priority];
            },
            fn() => null,
        );

        $registrar->register();

        $this->assertCount(1, $filterCalls);
    }

    public function testRegistrarCallsAddAction(): void
    {
        $actionCalls = [];

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $this->makeBridgeForRegistrar(),
            fn() => null,
            function (string $hook, callable $cb, int $priority) use (&$actionCalls): void {
                $actionCalls[] = [$hook, $cb, $priority];
            },
        );

        $registrar->register();

        $this->assertCount(1, $actionCalls);
    }

    public function testRegistrarUsesDefaultUploadHook(): void
    {
        $captured = null;
        $bridge   = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
            fn() => null,
        );

        $registrar->register();

        $this->assertSame('wp_handle_upload', $captured);
    }

    public function testRegistrarUsesDefaultAttachmentHook(): void
    {
        $captured = null;
        $bridge   = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            fn() => null,
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register();

        $this->assertSame('add_attachment', $captured);
    }

    public function testRegistrarUsesDefaultPriority(): void
    {
        $filterPriority = null;
        $actionPriority = null;
        $bridge         = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            function (string $hook, callable $cb, int $priority) use (&$filterPriority): void {
                $filterPriority = $priority;
            },
            function (string $hook, callable $cb, int $priority) use (&$actionPriority): void {
                $actionPriority = $priority;
            },
        );

        $registrar->register();

        $this->assertSame(10, $filterPriority);
        $this->assertSame(10, $actionPriority);
    }

    public function testRegistrarPassesBridgeRememberUploadAsFilterCallback(): void
    {
        $capturedCb = null;
        $bridge     = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            function (string $hook, callable $cb, int $priority) use (&$capturedCb): void {
                $capturedCb = $cb;
            },
            fn() => null,
        );

        $registrar->register();

        $this->assertSame([$bridge, 'rememberUpload'], $capturedCb);
    }

    public function testRegistrarPassesBridgeUpdateAttachmentAsActionCallback(): void
    {
        $capturedCb = null;
        $bridge     = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            fn() => null,
            function (string $hook, callable $cb, int $priority) use (&$capturedCb): void {
                $capturedCb = $cb;
            },
        );

        $registrar->register();

        $this->assertSame([$bridge, 'updateAttachment'], $capturedCb);
    }

    public function testRegistrarSupportsCustomHooksAndPriority(): void
    {
        $filterHook  = null;
        $actionHook  = null;
        $filterPri   = null;
        $actionPri   = null;
        $bridge      = $this->makeBridgeForRegistrar();

        $registrar = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            $bridge,
            function (string $hook, callable $cb, int $priority) use (&$filterHook, &$filterPri): void {
                $filterHook = $hook;
                $filterPri  = $priority;
            },
            function (string $hook, callable $cb, int $priority) use (&$actionHook, &$actionPri): void {
                $actionHook = $hook;
                $actionPri  = $priority;
            },
        );

        $registrar->register('wp_handle_sideload', 'wp_insert_attachment', 5);

        $this->assertSame('wp_handle_sideload', $filterHook);
        $this->assertSame('wp_insert_attachment', $actionHook);
        $this->assertSame(5, $filterPri);
        $this->assertSame(5, $actionPri);
    }
}
