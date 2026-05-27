<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessBootstrapFactory;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessPluginBootstrap;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeDefaults;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeDefaultsFactory;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeInstaller;

final class WordPressAssetAccessPluginBootstrapTest extends TestCase
{
    public function testBootReturnsInstaller(): void
    {
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $this->makeDefaultsFactory(),
            null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $this->assertInstanceOf(WordPressAssetAccessRuntimeInstaller::class, $bootstrap->boot());
    }

    public function testDefaultsFactoryIsUsedWhenBootstrapFactoryMissing(): void
    {
        $wpContentCalls = 0;
        $defaultsFactory = $this->makeDefaultsFactory(
            wpContentDirResolver: function () use (&$wpContentCalls): string {
                $wpContentCalls++;

                return '/var/www/wp-content';
            },
        );
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $defaultsFactory,
            null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $bootstrap->boot();

        $this->assertSame(1, $wpContentCalls);
    }

    public function testInjectedBootstrapFactoryIsUsedWhenProvided(): void
    {
        $wpContentCalls = 0;
        $actions = [];
        $defaultsFactory = $this->makeDefaultsFactory(
            wpContentDirResolver: function () use (&$wpContentCalls): string {
                $wpContentCalls++;

                return '/should/not/be/used';
            },
        );
        $bootstrapFactory = new WordPressAssetAccessBootstrapFactory(
            $this->makeDefaults(addAction: function (string $hook, callable $callback, int $priority) use (&$actions): void {
                $actions[] = [$hook, $priority];
            }),
        );
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $defaultsFactory,
            $bootstrapFactory,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $bootstrap->boot();

        $this->assertSame(0, $wpContentCalls);
        $this->assertContains('init', array_column($actions, 0));
    }

    public function testInstallerInstallIsCalled(): void
    {
        $actions = [];
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $this->makeDefaultsFactory(),
            new WordPressAssetAccessBootstrapFactory(
                $this->makeDefaults(addAction: function (string $hook, callable $callback, int $priority) use (&$actions): void {
                    $actions[] = [$hook, $priority];
                }),
            ),
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $bootstrap->boot();

        $this->assertContains('init', array_column($actions, 0));
    }

    public function testRequestUriResolverIsPassedThrough(): void
    {
        $requestCalls = 0;
        $actions = [];
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $this->makeDefaultsFactory(),
            new WordPressAssetAccessBootstrapFactory(
                $this->makeDefaults(addAction: function (string $hook, callable $callback, int $priority) use (&$actions): void {
                    $actions[$hook] = $callback;
                }),
            ),
            function () use (&$requestCalls): string {
                $requestCalls++;

                return '/wp-content/uploads/file.pdf';
            },
        );

        $bootstrap->boot();
        $actions['init']();

        $this->assertSame(1, $requestCalls);
    }

    public function testBootReturnsNewInstallerEachCall(): void
    {
        $bootstrap = new WordPressAssetAccessPluginBootstrap(
            $this->makeDefaultsFactory(),
            new WordPressAssetAccessBootstrapFactory($this->makeDefaults()),
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $this->assertNotSame($bootstrap->boot(), $bootstrap->boot());
    }

    private function makeDefaultsFactory(?callable $wpContentDirResolver = null): WordPressAssetAccessRuntimeDefaultsFactory
    {
        return new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => [],
            wpContentDirResolver: $wpContentDirResolver ?? fn(): string => '/var/www/wp-content',
        );
    }

    private function makeDefaults(?callable $addAction = null): WordPressAssetAccessRuntimeDefaults
    {
        return new WordPressAssetAccessRuntimeDefaults(
            '/private-assets',
            null,
            static fn(string $key, mixed $default): mixed => $default,
            static fn(string $key, mixed $value): null => null,
            static fn(int $id, string $key, bool $single): mixed => '',
            static fn(int $id, string $key, mixed $value): null => null,
            $addAction ?? static fn(mixed ...$args): null => null,
            static fn(mixed ...$args): null => null,
        );
    }
}
