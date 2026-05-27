<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessHealthCheckInterface;
use Period\WpFramework\WordPress\Access\AssetAccessHealthReporter;
use Period\WpFramework\WordPress\Access\AssetAccessHealthSettingsSection;
use Period\WpFramework\WordPress\Access\AssetAccessHealthStatus;
use Period\WpFramework\WordPress\Access\AssetAccessHealthStatusRenderer;
use Period\WpFramework\WordPress\Access\AssetAccessSettings;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsFormHandler;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsPageRenderer;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpFramework\WordPress\Access\CallableAssetAccessSettingsRepository;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsMenuRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsPage;

final class AssetAccessSettingsPageTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeRepository(mixed $initial = [], array &$saveCalls = []): AssetAccessSettingsRepositoryInterface
    {
        $options = ['period_asset_access_settings' => $initial];

        return new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$options): mixed {
                return array_key_exists($key, $options) ? $options[$key] : $default;
            },
            function (string $key, mixed $value) use (&$options, &$saveCalls): void {
                $saveCalls[]    = [$key, $value];
                $options[$key]  = $value;
            },
        );
    }

    private function makeHandler(
        AssetAccessSettingsRepositoryInterface $repository,
        array &$saveCalls = [],
    ): AssetAccessSettingsFormHandler {
        $options = [];

        $repo = new CallableAssetAccessSettingsRepository(
            fn(string $key, mixed $default): mixed => $options[$key] ?? $default,
            function (string $key, mixed $value) use (&$options, &$saveCalls): void {
                $saveCalls[]    = [$key, $value];
                $options[$key]  = $value;
            },
        );

        return new AssetAccessSettingsFormHandler($repo);
    }

    private function makeHandlerWith(AssetAccessSettingsRepositoryInterface $repository): AssetAccessSettingsFormHandler
    {
        return new AssetAccessSettingsFormHandler($repository);
    }

    private function makePage(
        bool $userCanManage                              = true,
        ?AssetAccessSettingsRepositoryInterface $repo    = null,
        array $availableRoles                            = [],
        ?AssetAccessHealthSettingsSection $healthSection = null,
    ): WordPressAssetAccessSettingsPage {
        return new WordPressAssetAccessSettingsPage(
            $repo ?? $this->makeRepository(),
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($repo ?? $this->makeRepository()),
            fn(string $cap): bool => $userCanManage && $cap === 'manage_options',
            fn(): array => $availableRoles,
            $healthSection,
        );
    }

    private function makeHealthSection(): AssetAccessHealthSettingsSection
    {
        return new AssetAccessHealthSettingsSection(
            new AssetAccessHealthReporter([
                new class implements AssetAccessHealthCheckInterface {
                    public function check(): array
                    {
                        return [AssetAccessHealthStatus::info('health_ok', 'health ok')];
                    }
                },
            ]),
            new AssetAccessHealthStatusRenderer(),
        );
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsFormHandler — enabled flag
    // -----------------------------------------------------------------------

    public function testHandleSavesEnabledTrueWhenCheckboxPresent(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $handler   = $this->makeHandlerWith($repo);

        $settings = $handler->handle(['period_asset_access' => ['enabled' => '1']]);

        $this->assertTrue($settings->isEnabled());
    }

    public function testHandleSavesEnabledFalseWhenCheckboxAbsent(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $handler   = $this->makeHandlerWith($repo);

        $settings = $handler->handle(['period_asset_access' => []]);

        $this->assertFalse($settings->isEnabled());
    }

    public function testHandleSavesEnabledFalseWhenPeriodKeyMissing(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([]);

        $this->assertFalse($settings->isEnabled());
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsFormHandler — protected roles
    // -----------------------------------------------------------------------

    public function testHandleSavesProtectedRoles(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => [
                'enabled'         => '1',
                'protected_roles' => ['subscriber', 'contributor'],
            ],
        ]);

        $this->assertSame(['subscriber', 'contributor'], $settings->protectedRoles());
    }

    public function testHandleFiltersNonStringRoles(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => [
                'protected_roles' => ['editor', 123, null, 'author'],
            ],
        ]);

        $this->assertSame(['editor', 'author'], $settings->protectedRoles());
    }

    public function testHandleFiltersEmptyStringRoles(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => [
                'protected_roles' => ['', 'editor', '  '],
            ],
        ]);

        $this->assertSame(['editor'], $settings->protectedRoles());
    }

    public function testHandleReturnsEmptyRolesWhenKeyMissing(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle(['period_asset_access' => []]);

        $this->assertSame([], $settings->protectedRoles());
    }

    public function testHandleReturnsEmptyRolesWhenRolesIsNotArray(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => ['protected_roles' => 'not-an-array'],
        ]);

        $this->assertSame([], $settings->protectedRoles());
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsFormHandler — visibility
    // -----------------------------------------------------------------------

    public function testHandleSavesPublicVisibility(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => ['default_visibility' => 'public'],
        ]);

        $this->assertSame('public', $settings->defaultVisibility());
    }

    public function testHandleSavesPrivateVisibility(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => ['default_visibility' => 'private'],
        ]);

        $this->assertSame('private', $settings->defaultVisibility());
    }

    public function testHandleDefaultsToPublicForInvalidVisibility(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => ['default_visibility' => 'bogus'],
        ]);

        $this->assertSame('public', $settings->defaultVisibility());
    }

    public function testHandleDefaultsToPublicWhenVisibilityKeyMissing(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle(['period_asset_access' => []]);

        $this->assertSame('public', $settings->defaultVisibility());
    }

    public function testHandleSavesPrivateAssetRoot(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $handler   = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => [
                'private_asset_root' => '  /var/private-assets  ',
            ],
        ]);

        $this->assertSame('/var/private-assets', $settings->privateAssetRoot());
        $this->assertSame('/var/private-assets', $saveCalls[0][1]['private_asset_root']);
    }

    public function testHandleNormalizesEmptyPrivateAssetRootToNull(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $settings = $handler->handle([
            'period_asset_access' => [
                'private_asset_root' => '   ',
            ],
        ]);

        $this->assertNull($settings->privateAssetRoot());
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsFormHandler — repository interaction
    // -----------------------------------------------------------------------

    public function testHandleCallsRepositorySave(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $handler   = $this->makeHandlerWith($repo);

        $handler->handle(['period_asset_access' => ['enabled' => '1']]);

        $this->assertCount(1, $saveCalls);
    }

    public function testHandleReturnsAssetAccessSettingsInstance(): void
    {
        $repo    = $this->makeRepository();
        $handler = $this->makeHandlerWith($repo);

        $result = $handler->handle([]);

        $this->assertInstanceOf(AssetAccessSettings::class, $result);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAccessSettingsPage — render
    // -----------------------------------------------------------------------

    public function testRenderReturnsEmptyStringWhenUnauthorized(): void
    {
        $page = $this->makePage(userCanManage: false);

        $this->assertSame('', $page->render());
    }

    public function testRenderReturnsNonEmptyHtmlWhenAuthorized(): void
    {
        $page = $this->makePage(userCanManage: true);

        $this->assertNotSame('', $page->render());
    }

    public function testRenderCallsCurrentUserCanWithManageOptions(): void
    {
        $capturedCap = null;

        $page = new WordPressAssetAccessSettingsPage(
            $this->makeRepository(),
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($this->makeRepository()),
            function (string $cap) use (&$capturedCap): bool {
                $capturedCap = $cap;
                return true;
            },
            fn(): array => [],
        );
        $page->render();

        $this->assertSame('manage_options', $capturedCap);
    }

    public function testRenderPassesAvailableRolesToRenderer(): void
    {
        $page = $this->makePage(availableRoles: ['subscriber' => 'Subscriber']);
        $html = $page->render();

        $this->assertStringContainsString('Subscriber', $html);
    }

    public function testRenderReflectsCurrentSettings(): void
    {
        $repo = $this->makeRepository([
            'enabled'            => true,
            'protected_roles'    => [],
            'default_visibility' => 'public',
        ]);
        $page = new WordPressAssetAccessSettingsPage(
            $repo,
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($repo),
            fn(string $cap): bool => true,
            fn(): array => [],
        );

        $html = $page->render();

        $this->assertStringContainsString(' checked', $html);
    }

    public function testRenderIncludesHealthSectionWhenProvided(): void
    {
        $page = $this->makePage(healthSection: $this->makeHealthSection());

        $html = $page->render();

        $this->assertStringContainsString('health_ok', $html);
        $this->assertStringContainsString('health ok', $html);
    }

    public function testRenderOutputUnchangedWhenHealthSectionIsNull(): void
    {
        $repo = $this->makeRepository();
        $page = $this->makePage(repo: $repo, healthSection: null);
        $expected = (new AssetAccessSettingsPageRenderer())->render($repo->get(), []);

        $this->assertSame($expected, $page->render());
    }

    public function testUnauthorizedUserDoesNotRenderHealthSection(): void
    {
        $page = $this->makePage(userCanManage: false, healthSection: $this->makeHealthSection());

        $this->assertSame('', $page->render());
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAccessSettingsPage — handlePost
    // -----------------------------------------------------------------------

    public function testHandlePostReturnsNullWhenUnauthorized(): void
    {
        $page = $this->makePage(userCanManage: false);

        $this->assertNull($page->handlePost(['period_asset_access' => ['enabled' => '1']]));
    }

    public function testHandlePostReturnsSettingsWhenAuthorized(): void
    {
        $page   = $this->makePage(userCanManage: true);
        $result = $page->handlePost(['period_asset_access' => ['enabled' => '1']]);

        $this->assertInstanceOf(AssetAccessSettings::class, $result);
    }

    public function testHandlePostDelegatesToHandler(): void
    {
        $page   = $this->makePage(userCanManage: true);
        $result = $page->handlePost([
            'period_asset_access' => [
                'enabled'         => '1',
                'protected_roles' => ['editor'],
                'default_visibility' => 'private',
            ],
        ]);

        $this->assertNotNull($result);
        $this->assertTrue($result->isEnabled());
        $this->assertSame(['editor'], $result->protectedRoles());
        $this->assertSame('private', $result->defaultVisibility());
    }

    public function testHandlePostIsUnchangedWhenHealthSectionExists(): void
    {
        $page = $this->makePage(userCanManage: true, healthSection: $this->makeHealthSection());
        $result = $page->handlePost(['period_asset_access' => ['enabled' => '1']]);

        $this->assertInstanceOf(AssetAccessSettings::class, $result);
        $this->assertTrue($result->isEnabled());
    }

    public function testHandlePostCallsCurrentUserCan(): void
    {
        $capturedCap = null;

        $page = new WordPressAssetAccessSettingsPage(
            $this->makeRepository(),
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($this->makeRepository()),
            function (string $cap) use (&$capturedCap): bool {
                $capturedCap = $cap;
                return false;
            },
            fn(): array => [],
        );
        $page->handlePost([]);

        $this->assertSame('manage_options', $capturedCap);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAccessSettingsMenuRegistrar — registration
    // -----------------------------------------------------------------------

    private function makeRegistrar(array &$calls = []): WordPressAssetAccessSettingsMenuRegistrar
    {
        return new WordPressAssetAccessSettingsMenuRegistrar(
            $this->makePage(),
            function () use (&$calls): void {
                $calls[] = func_get_args();
            },
        );
    }

    public function testRegisterCallsAddOptionsPage(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $this->assertCount(1, $calls);
    }

    public function testRegisterPassesManageOptionsCapability(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $this->assertSame('manage_options', $calls[0][2]);
    }

    public function testRegisterPassesMenuSlug(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $this->assertSame('period-asset-access', $calls[0][3]);
    }

    public function testRegisterPassesCallbackAsLastArg(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $this->assertIsCallable($calls[0][4]);
    }

    public function testRegisterCallbackOutputsHtml(): void
    {
        $calls = [];
        $this->makeRegistrar($calls)->register();

        $callback = $calls[0][4];
        ob_start();
        $callback();
        $output = ob_get_clean();

        $this->assertIsString($output);
    }

    public function testRegisterCallbackOutputsNonEmptyHtml(): void
    {
        $calls = [];

        $page = $this->makePage(userCanManage: true);
        $reg  = new WordPressAssetAccessSettingsMenuRegistrar(
            $page,
            function () use (&$calls): void {
                $calls[] = func_get_args();
            },
        );
        $reg->register();

        $callback = $calls[0][4];
        ob_start();
        $callback();
        $output = ob_get_clean();

        $this->assertNotSame('', $output);
    }

    public function testAddOptionsPageIsNotCalledDirectly(): void
    {
        $called = false;

        $reg = new WordPressAssetAccessSettingsMenuRegistrar(
            $this->makePage(),
            function () use (&$called): void {
                $called = true;
            },
        );
        $reg->register();

        $this->assertTrue($called, 'addOptionsPage callable must be invoked');
    }
}
