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
use Period\WpKit\WordPress\Access\WordPressAssetUploadPipelineHookRegistrar;

final class WordPressAssetUploadPipelineHookRegistrarTest extends TestCase
{
    private function makeCoordinator(): AssetUploadPipelineCoordinator
    {
        $interceptor = new AssetUploadInterceptor(
            new RoleBasedAssetUploadPolicy([]),
            new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy()),
            fn(array $u) => new AssetRequestContext(
                assetPath: (string) ($u['file'] ?? ''),
                assetUrl: '',
                currentUserId: 1,
                currentUserRoles: [],
                requestTime: new DateTimeImmutable(),
            ),
        );

        $mover = new class implements AssetFileMoverInterface {
            public function move(string $from, string $to): AssetFileMoveResult
            {
                return AssetFileMoveResult::success($from, $to);
            }
        };

        return new AssetUploadPipelineCoordinator(
            $interceptor,
            new AssetUploadMoveProcessor($mover),
            new AssetUploadUrlRewriteProcessor(new ProxyAssetUrlRewriteStrategy('/asset-access')),
        );
    }

    public function testRegisterCallsAddFilterOnce(): void
    {
        $calls = [];

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function (string $hook, callable $cb, int $priority) use (&$calls): void {
                $calls[] = [$hook, $cb, $priority];
            },
        );

        $registrar->register();

        $this->assertCount(1, $calls);
    }

    public function testRegisterUsesDefaultHook(): void
    {
        $captured = null;

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register();

        $this->assertSame('wp_handle_upload', $captured);
    }

    public function testRegisterUsesDefaultPriority(): void
    {
        $captured = null;

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register();

        $this->assertSame(10, $captured);
    }

    public function testRegisterPassesCoordinatorProcessAsCallback(): void
    {
        $capturedCallback = null;
        $coordinator      = $this->makeCoordinator();

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $coordinator,
            function (string $hook, callable $cb, int $priority) use (&$capturedCallback): void {
                $capturedCallback = $cb;
            },
        );

        $registrar->register();

        $this->assertSame([$coordinator, 'process'], $capturedCallback);
    }

    public function testRegisterSupportsCustomHook(): void
    {
        $captured = null;

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $hook;
            },
        );

        $registrar->register('wp_handle_sideload');

        $this->assertSame('wp_handle_sideload', $captured);
    }

    public function testRegisterSupportsCustomPriority(): void
    {
        $captured = null;

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function (string $hook, callable $cb, int $priority) use (&$captured): void {
                $captured = $priority;
            },
        );

        $registrar->register('wp_handle_upload', 20);

        $this->assertSame(20, $captured);
    }

    public function testAddFilterIsNeverCalledDirectly(): void
    {
        // If this test executes without calling the global add_filter(),
        // the implementation uses only the injected callable.
        $called = false;

        $registrar = new WordPressAssetUploadPipelineHookRegistrar(
            $this->makeCoordinator(),
            function () use (&$called): void {
                $called = true;
            },
        );

        $registrar->register();

        $this->assertTrue($called, 'Injected addFilter callable must be invoked');
    }
}
