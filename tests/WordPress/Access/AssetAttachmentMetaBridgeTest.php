<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaBridge;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaUpdater;
use Period\WpKit\WordPress\Access\AssetFileMoveResult;
use Period\WpKit\WordPress\Access\AssetUploadFingerprintGenerator;
use Period\WpKit\WordPress\Access\AttachmentFingerprintResolverInterface;
use Period\WpKit\WordPress\Access\CallableAttachmentFingerprintResolver;
use Period\WpKit\WordPress\Access\WordPressAssetAttachmentMetaBridgeHookRegistrar;

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
            'file'              => $file,
            'url'               => 'https://example.com' . $file,
            'type'              => 'application/pdf',
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

    private function makeGenerator(): AssetUploadFingerprintGenerator
    {
        return new AssetUploadFingerprintGenerator();
    }

    /**
     * Build a bridge where a captured-fingerprint variable drives the resolver.
     * Callers assign $capturedFingerprint after calling rememberUpload().
     */
    private function makeBridge(array &$metaCalls, ?string &$capturedFingerprint = null): AssetAttachmentMetaBridge
    {
        $capturedFingerprint = null;

        return new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(
                function (int $id) use (&$capturedFingerprint): ?string {
                    return $capturedFingerprint;
                },
            ),
            $this->makeGenerator(),
        );
    }

    // -----------------------------------------------------------------------
    // rememberUpload — fingerprint is embedded
    // -----------------------------------------------------------------------

    public function testRememberUploadReturnsFingerprintKey(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);
        $upload    = $this->protectedUpload();

        $result = $bridge->rememberUpload($upload);

        $this->assertArrayHasKey('_period_upload_fingerprint', $result);
    }

    public function testRememberUploadFingerprintIsNonEmpty(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $result = $bridge->rememberUpload($this->protectedUpload());

        $this->assertNotEmpty($result['_period_upload_fingerprint']);
    }

    public function testRememberUploadPreservesOriginalKeys(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);
        $upload    = $this->protectedUpload();

        $result = $bridge->rememberUpload($upload);

        foreach ($upload as $key => $value) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    public function testRememberPublicUploadAlsoEmbeddsFingerprint(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $result = $bridge->rememberUpload($this->publicUpload());

        $this->assertArrayHasKey('_period_upload_fingerprint', $result);
    }

    // -----------------------------------------------------------------------
    // updateAttachment — matched fingerprint triggers updater
    // -----------------------------------------------------------------------

    public function testUpdateAttachmentCallsUpdaterWhenUploadRemembered(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $result = $bridge->rememberUpload($this->protectedUpload());
        $fp     = $result['_period_upload_fingerprint'];

        $bridge->updateAttachment(42);

        $this->assertNotEmpty($metaCalls);
    }

    public function testUpdateAttachmentPassesCorrectAttachmentId(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $result = $bridge->rememberUpload($this->protectedUpload());
        $fp     = $result['_period_upload_fingerprint'];

        $bridge->updateAttachment(99);

        foreach ($metaCalls as [$id]) {
            $this->assertSame(99, $id);
        }
    }

    public function testUpdateAttachmentPassesRememberedUploadToUpdater(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $result = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/secret.pdf'));
        $fp     = $result['_period_upload_fingerprint'];

        $bridge->updateAttachment(5);

        $pathCall = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertSame('/protected-uploads/secret.pdf', $pathCall[0][2]);
    }

    // -----------------------------------------------------------------------
    // updateAttachment — no upload or unmatched fingerprint
    // -----------------------------------------------------------------------

    public function testUpdateAttachmentDoesNothingWhenNoUploadRemembered(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $bridge->updateAttachment(1);

        $this->assertCount(0, $metaCalls);
    }

    public function testUpdateAttachmentDoesNothingWhenFingerprintResolverReturnsNull(): void
    {
        $metaCalls = [];
        $bridge    = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(fn(int $id): ?string => null),
            $this->makeGenerator(),
        );

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(1);

        $this->assertEmpty($metaCalls);
    }

    public function testUnmatchedFingerprintDoesNothing(): void
    {
        $metaCalls = [];
        $bridge    = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(fn(int $id): ?string => 'nonexistent-fingerprint'),
            $this->makeGenerator(),
        );

        $bridge->rememberUpload($this->protectedUpload());
        $bridge->updateAttachment(1);

        $this->assertEmpty($metaCalls);
    }

    // -----------------------------------------------------------------------
    // upload cleared after use
    // -----------------------------------------------------------------------

    public function testUploadIsClearedAfterUpdate(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $result = $bridge->rememberUpload($this->protectedUpload());
        $fp     = $result['_period_upload_fingerprint'];

        $bridge->updateAttachment(10);
        $countAfterFirst = count($metaCalls);

        $bridge->updateAttachment(10);

        $this->assertSame($countAfterFirst, count($metaCalls));
    }

    public function testSecondUpdateAttachmentWithSameFingerprintDoesNothing(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $result = $bridge->rememberUpload($this->protectedUpload());
        $fp     = $result['_period_upload_fingerprint'];

        $bridge->updateAttachment(1);
        $bridge->updateAttachment(2);

        $ids = array_unique(array_column($metaCalls, 0));
        $this->assertNotContains(2, $ids);
    }

    // -----------------------------------------------------------------------
    // multiple uploads coexist
    // -----------------------------------------------------------------------

    public function testMultipleUploadsCoexistInMap(): void
    {
        $metaCalls   = [];
        $fpMap       = [];
        $bridge      = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(function (int $id) use (&$fpMap): ?string { return $fpMap[$id] ?? null; }),
            $this->makeGenerator(),
        );

        $r1 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/file-a.pdf'));
        $r2 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/file-b.pdf'));

        $fpMap[10] = $r1['_period_upload_fingerprint'];
        $fpMap[20] = $r2['_period_upload_fingerprint'];

        $bridge->updateAttachment(10);
        $bridge->updateAttachment(20);

        $pathCalls = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertCount(2, $pathCalls);
        $paths = array_column($pathCalls, 2);
        $this->assertContains('/protected-uploads/file-a.pdf', $paths);
        $this->assertContains('/protected-uploads/file-b.pdf', $paths);
    }

    public function testCorrectUploadMatchedToAttachment(): void
    {
        $metaCalls   = [];
        $fpMap       = [];
        $bridge      = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(function (int $id) use (&$fpMap): ?string { return $fpMap[$id] ?? null; }),
            $this->makeGenerator(),
        );

        $r1 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/alpha.pdf'));
        $r2 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/beta.pdf'));

        $fpMap[100] = $r2['_period_upload_fingerprint']; // only beta → attachment 100
        // alpha is NOT mapped to any attachment

        $bridge->updateAttachment(100);

        $pathCalls = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertCount(1, $pathCalls);
        $this->assertSame('/protected-uploads/beta.pdf', $pathCalls[0][2]);
        $this->assertSame(100, $pathCalls[0][0]);
    }

    public function testAlphaUploadNotCalledForOtherAttachment(): void
    {
        $metaCalls   = [];
        $fpMap       = [];
        $bridge      = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(function (int $id) use (&$fpMap): ?string { return $fpMap[$id] ?? null; }),
            $this->makeGenerator(),
        );

        $r1         = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/alpha.pdf'));
        $fpMap[50]  = $r1['_period_upload_fingerprint'];

        // Also upload beta but don't map it to any attachment
        $bridge->rememberUpload($this->protectedUpload('/protected-uploads/beta.pdf'));

        $bridge->updateAttachment(50);

        $ids = array_unique(array_column($metaCalls, 0));
        $this->assertSame([50], array_values($ids));
    }

    // -----------------------------------------------------------------------
    // duplicate upload overwrites same fingerprint only
    // -----------------------------------------------------------------------

    public function testDuplicateUploadOverwritesSameFingerprint(): void
    {
        $metaCalls = [];
        $fp        = null;
        $bridge    = $this->makeBridge($metaCalls, $fp);

        $upload = $this->protectedUpload('/protected-uploads/file.pdf');

        $r1 = $bridge->rememberUpload($upload);
        $r2 = $bridge->rememberUpload($upload); // same content → same fingerprint

        $this->assertSame($r1['_period_upload_fingerprint'], $r2['_period_upload_fingerprint']);

        $fp = $r2['_period_upload_fingerprint'];
        $bridge->updateAttachment(1);

        // Only one updater call per fingerprint (not two)
        $protectedCalls = array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected');
        $this->assertCount(1, $protectedCalls);
    }

    public function testDifferentUploadsProduceDifferentFingerprints(): void
    {
        $metaCalls = [];
        $bridge    = $this->makeBridge($metaCalls);

        $r1 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/a.pdf'));
        $r2 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/b.pdf'));

        $this->assertNotSame(
            $r1['_period_upload_fingerprint'],
            $r2['_period_upload_fingerprint'],
        );
    }

    // -----------------------------------------------------------------------
    // old single-upload behavior no longer exists
    // -----------------------------------------------------------------------

    public function testTwoUploadsBothRemainAvailableForSeparateAttachments(): void
    {
        $metaCalls = [];
        $fpMap     = [];
        $bridge    = new AssetAttachmentMetaBridge(
            $this->makeUpdater($metaCalls),
            new CallableAttachmentFingerprintResolver(function (int $id) use (&$fpMap): ?string { return $fpMap[$id] ?? null; }),
            $this->makeGenerator(),
        );

        $r1 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/first.pdf'));
        $r2 = $bridge->rememberUpload($this->protectedUpload('/protected-uploads/second.pdf'));

        // Both fingerprints are distinct and coexist — the second does NOT erase the first
        $fpMap[7]  = $r1['_period_upload_fingerprint'];
        $fpMap[8]  = $r2['_period_upload_fingerprint'];

        $bridge->updateAttachment(8); // second first
        $bridge->updateAttachment(7); // first second

        $pathCalls = array_values(
            array_filter($metaCalls, fn($c) => $c[1] === '_period_asset_protected_path'),
        );

        $this->assertCount(2, $pathCalls);
        $byId = [];
        foreach ($pathCalls as [$id, , $path]) {
            $byId[$id] = $path;
        }

        $this->assertSame('/protected-uploads/first.pdf', $byId[7]);
        $this->assertSame('/protected-uploads/second.pdf', $byId[8]);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentMetaBridgeHookRegistrar
    // -----------------------------------------------------------------------

    private function makeBridgeForRegistrar(): AssetAttachmentMetaBridge
    {
        $dummy = [];

        return new AssetAttachmentMetaBridge(
            $this->makeUpdater($dummy),
            new CallableAttachmentFingerprintResolver(fn(int $id): ?string => null),
            $this->makeGenerator(),
        );
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
