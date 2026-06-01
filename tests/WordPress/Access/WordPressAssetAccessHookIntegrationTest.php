<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessManager;
use Period\WpKit\WordPress\Access\AssetDeliveryResult;
use Period\WpKit\WordPress\Access\AssetEmitResult;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\AssetRequestMatcher;
use Period\WpKit\WordPress\Access\AssetResponseEmitterInterface;
use Period\WpKit\WordPress\Access\DefaultRequestContextFactory;
use Period\WpKit\WordPress\Access\InMemoryAssetStorage;
use Period\WpKit\WordPress\Access\MemoryAssetResponseEmitter;
use Period\WpKit\WordPress\Access\NullAssetDelivery;
use Period\WpKit\WordPress\Access\PublicAssetAccessPolicy;
use Period\WpKit\WordPress\Access\WordPressAssetAccessBootstrap;
use Period\WpKit\WordPress\Access\WordPressAssetAccessController;
use Period\WpKit\WordPress\Access\WordPressAssetAccessHookRegistrar;
use Period\WpKit\WordPress\Access\WordPressAssetAccessKernel;

final class WordPressAssetAccessHookIntegrationTest extends TestCase
{
    private function makeBootstrap(bool $matchUri = true): WordPressAssetAccessBootstrap
    {
        $matcher = new AssetRequestMatcher(
            $matchUri ? ['/wp-content/uploads/'] : ['/never-matches/']
        );

        $kernel = new WordPressAssetAccessKernel(
            $matcher,
            new AssetAccessManager(new PublicAssetAccessPolicy()),
            new NullAssetDelivery(),
            new InMemoryAssetStorage([]),
        );

        $factory = new DefaultRequestContextFactory(
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            assetUrlResolver: fn($uri) => 'https://example.com' . $uri,
        );

        return new WordPressAssetAccessBootstrap($kernel, fn($uri) => $factory->create($uri));
    }

    private function makeBootstrapReturning(AssetDeliveryResult $fixed): WordPressAssetAccessBootstrap
    {
        $kernel = new WordPressAssetAccessKernel(
            new AssetRequestMatcher(['/wp-content/uploads/']),
            new AssetAccessManager(new PublicAssetAccessPolicy()),
            new class($fixed) implements \Period\WpKit\WordPress\Access\AssetDeliveryInterface {
                public function __construct(private readonly AssetDeliveryResult $result) {}

                public function deliver(AssetRequestContext $ctx): AssetDeliveryResult
                {
                    return $this->result;
                }
            },
            new \Period\WpKit\WordPress\Access\InMemoryAssetStorage([
                new \Period\WpKit\WordPress\Access\AssetStorageItem(
                    '/wp-content/uploads/file.pdf', null, null, null, null
                ),
            ]),
        );

        $factory = new DefaultRequestContextFactory(
            currentUserId: 1,
            currentUserRoles: [],
            assetUrlResolver: fn($uri) => 'https://example.com' . $uri,
        );

        return new WordPressAssetAccessBootstrap($kernel, fn($uri) => $factory->create($uri));
    }

    // --- WordPressAssetAccessController ---

    public function testHandleReturnsNullWhenBootstrapReturnsNull(): void
    {
        $controller = new WordPressAssetAccessController(
            $this->makeBootstrap(matchUri: false),
            new MemoryAssetResponseEmitter(),
            fn() => '/wp-content/uploads/file.pdf',
        );

        $this->assertNull($controller->handle());
    }

    public function testHandleEmitsDeliveryResult(): void
    {
        $bootstrap = $this->makeBootstrapReturning(AssetDeliveryResult::ok(200));

        $controller = new WordPressAssetAccessController(
            $bootstrap,
            new MemoryAssetResponseEmitter(),
            fn() => '/wp-content/uploads/file.pdf',
        );

        $result = $controller->handle();

        $this->assertInstanceOf(AssetEmitResult::class, $result);
        $this->assertSame(200, $result->statusCode());
    }

    public function testHandleUsesRequestUriResolver(): void
    {
        $receivedUri = null;

        $bootstrap = new WordPressAssetAccessBootstrap(
            new WordPressAssetAccessKernel(
                new AssetRequestMatcher(['/never/']),
                new AssetAccessManager(new PublicAssetAccessPolicy()),
                new NullAssetDelivery(),
                new InMemoryAssetStorage([]),
            ),
            function (string $uri) use (&$receivedUri): AssetRequestContext {
                $receivedUri = $uri;
                return new AssetRequestContext(
                    assetPath: $uri,
                    assetUrl: 'https://example.com' . $uri,
                    currentUserId: 0,
                    currentUserRoles: [],
                    requestTime: new DateTimeImmutable(),
                );
            },
        );

        $controller = new WordPressAssetAccessController(
            $bootstrap,
            new MemoryAssetResponseEmitter(),
            fn() => '/wp-content/uploads/photo.jpg',
        );

        $controller->handle();

        $this->assertSame('/wp-content/uploads/photo.jpg', $receivedUri);
    }

    public function testHandleEmitResultIsReturnedFromEmitter(): void
    {
        $fixedEmitResult = new AssetEmitResult(true, 200, [], null, null);

        $emitter = new class($fixedEmitResult) implements AssetResponseEmitterInterface {
            public function __construct(private readonly AssetEmitResult $result) {}

            public function emit(AssetDeliveryResult $r): AssetEmitResult
            {
                return $this->result;
            }
        };

        $bootstrap = $this->makeBootstrapReturning(AssetDeliveryResult::ok(200));

        $controller = new WordPressAssetAccessController(
            $bootstrap,
            $emitter,
            fn() => '/wp-content/uploads/file.pdf',
        );

        $this->assertSame($fixedEmitResult, $controller->handle());
    }

    // --- WordPressAssetAccessHookRegistrar ---

    public function testRegisterCallsAddAction(): void
    {
        $called = false;

        $controller = new WordPressAssetAccessController(
            $this->makeBootstrap(),
            new MemoryAssetResponseEmitter(),
            fn() => '/',
        );

        $registrar = new WordPressAssetAccessHookRegistrar(
            $controller,
            function () use (&$called): void { $called = true; },
        );

        $registrar->register();

        $this->assertTrue($called);
    }

    public function testRegisterPassesDefaultHookAndPriority(): void
    {
        $capturedHook     = null;
        $capturedPriority = null;

        $controller = new WordPressAssetAccessController(
            $this->makeBootstrap(),
            new MemoryAssetResponseEmitter(),
            fn() => '/',
        );

        $registrar = new WordPressAssetAccessHookRegistrar(
            $controller,
            function (string $hook, callable $cb, int $priority) use (&$capturedHook, &$capturedPriority): void {
                $capturedHook     = $hook;
                $capturedPriority = $priority;
            },
        );

        $registrar->register();

        $this->assertSame('init', $capturedHook);
        $this->assertSame(0, $capturedPriority);
    }

    public function testRegisterPassesCustomHookAndPriority(): void
    {
        $capturedHook     = null;
        $capturedPriority = null;

        $controller = new WordPressAssetAccessController(
            $this->makeBootstrap(),
            new MemoryAssetResponseEmitter(),
            fn() => '/',
        );

        $registrar = new WordPressAssetAccessHookRegistrar(
            $controller,
            function (string $hook, callable $cb, int $priority) use (&$capturedHook, &$capturedPriority): void {
                $capturedHook     = $hook;
                $capturedPriority = $priority;
            },
        );

        $registrar->register('template_redirect', 5);

        $this->assertSame('template_redirect', $capturedHook);
        $this->assertSame(5, $capturedPriority);
    }

    public function testRegisterPassesControllerHandleAsCallback(): void
    {
        $capturedCallback = null;

        $controller = new WordPressAssetAccessController(
            $this->makeBootstrap(),
            new MemoryAssetResponseEmitter(),
            fn() => '/',
        );

        $registrar = new WordPressAssetAccessHookRegistrar(
            $controller,
            function (string $hook, callable $cb, int $priority) use (&$capturedCallback): void {
                $capturedCallback = $cb;
            },
        );

        $registrar->register();

        $this->assertIsArray($capturedCallback);
        $this->assertSame($controller, $capturedCallback[0]);
        $this->assertSame('handle', $capturedCallback[1]);
    }
}
