<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessSettingsFormHandler;
use Period\WpKit\WordPress\Access\AssetAccessSettingsPageRenderer;
use Period\WpKit\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpKit\WordPress\Access\CallableAssetAccessSettingsRepository;
use Period\WpKit\WordPress\Access\WordPressAssetAccessSettingsPage;
use Period\WpKit\WordPress\Access\WordPressAssetAccessSettingsSaveController;
use Period\WpKit\WordPress\Access\WordPressAssetAccessSettingsSaveHookRegistrar;

final class WordPressAssetAccessSettingsSaveHookTest extends TestCase
{
    private function makeRepository(array &$saveCalls = []): AssetAccessSettingsRepositoryInterface
    {
        $options = ['period_asset_access_settings' => []];

        return new CallableAssetAccessSettingsRepository(
            fn(string $key, mixed $default): mixed => $options[$key] ?? $default,
            function (string $key, mixed $value) use (&$options, &$saveCalls): void {
                $saveCalls[]   = [$key, $value];
                $options[$key] = $value;
            },
        );
    }

    private function makePage(array &$saveCalls = []): WordPressAssetAccessSettingsPage
    {
        $repository = $this->makeRepository($saveCalls);

        return new WordPressAssetAccessSettingsPage(
            $repository,
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($repository),
            fn(string $cap): bool => $cap === 'manage_options',
            fn(): array => [],
        );
    }

    public function testControllerDelegatesToPageHandlePost(): void
    {
        $saveCalls = [];
        $controller = new WordPressAssetAccessSettingsSaveController(
            $this->makePage($saveCalls),
            fn(string $url): null => null,
            fn(string $path): string => $path,
        );

        $controller->handle(['period_asset_access' => ['enabled' => '1']]);

        $this->assertCount(1, $saveCalls);
        $this->assertTrue($saveCalls[0][1]['enabled']);
    }

    public function testControllerRedirectsAfterSave(): void
    {
        $events = [];
        $saveCalls = [];
        $repository = new CallableAssetAccessSettingsRepository(
            fn(string $key, mixed $default): mixed => [],
            function (string $key, mixed $value) use (&$events, &$saveCalls): void {
                $events[] = 'save';
                $saveCalls[] = [$key, $value];
            },
        );
        $page = new WordPressAssetAccessSettingsPage(
            $repository,
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($repository),
            fn(string $cap): bool => $cap === 'manage_options',
            fn(): array => [],
        );
        $controller = new WordPressAssetAccessSettingsSaveController(
            $page,
            function (string $url) use (&$events): void {
                $events[] = 'redirect:' . $url;
            },
            fn(string $path): string => '/wp-admin/' . $path,
        );

        $controller->handle(['period_asset_access' => ['enabled' => '1']]);

        $this->assertSame([
            'save',
            'redirect:/wp-admin/options-general.php?page=period-asset-access&updated=1',
        ], $events);
        $this->assertCount(1, $saveCalls);
    }

    public function testControllerUsesAdminUrlCallable(): void
    {
        $adminUrlPath = null;
        $redirectUrl = null;
        $controller = new WordPressAssetAccessSettingsSaveController(
            $this->makePage(),
            function (string $url) use (&$redirectUrl): void {
                $redirectUrl = $url;
            },
            function (string $path) use (&$adminUrlPath): string {
                $adminUrlPath = $path;
                return 'https://example.test/wp-admin/' . $path;
            },
        );

        $controller->handle([]);

        $this->assertSame('options-general.php?page=period-asset-access&updated=1', $adminUrlPath);
        $this->assertSame(
            'https://example.test/wp-admin/options-general.php?page=period-asset-access&updated=1',
            $redirectUrl,
        );
    }

    public function testControllerDoesNotExit(): void
    {
        $continued = false;
        $controller = new WordPressAssetAccessSettingsSaveController(
            $this->makePage(),
            fn(string $url): null => null,
            fn(string $path): string => $path,
        );

        $controller->handle([]);
        $continued = true;

        $this->assertTrue($continued);
    }

    public function testRegistrarCallsInjectedAddAction(): void
    {
        $calls = [];
        $controller = new WordPressAssetAccessSettingsSaveController(
            $this->makePage(),
            fn(string $url): null => null,
            fn(string $path): string => $path,
        );
        $registrar = new WordPressAssetAccessSettingsSaveHookRegistrar(
            $controller,
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = [$hook, $callback, $priority];
            },
        );

        $registrar->register();

        $this->assertCount(1, $calls);
        $this->assertSame('admin_post_period_asset_access_save', $calls[0][0]);
        $this->assertSame(10, $calls[0][2]);
        $this->assertSame($controller, $calls[0][1][0]);
        $this->assertSame('handle', $calls[0][1][1]);
    }

    public function testRegistrarSupportsCustomHookAndPriority(): void
    {
        $capturedHook = null;
        $capturedPriority = null;
        $controller = new WordPressAssetAccessSettingsSaveController(
            $this->makePage(),
            fn(string $url): null => null,
            fn(string $path): string => $path,
        );
        $registrar = new WordPressAssetAccessSettingsSaveHookRegistrar(
            $controller,
            function (string $hook, callable $callback, int $priority) use (&$capturedHook, &$capturedPriority): void {
                $capturedHook = $hook;
                $capturedPriority = $priority;
            },
        );

        $registrar->register('admin_post_custom_asset_access_save', 20);

        $this->assertSame('admin_post_custom_asset_access_save', $capturedHook);
        $this->assertSame(20, $capturedPriority);
    }
}
