<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessSettings;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsPageRenderer;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpFramework\WordPress\Access\CallableAssetAccessSettingsRepository;

final class AssetAccessSettingsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeSettings(
        bool $enabled         = false,
        array $protectedRoles = [],
        string $visibility    = AssetAccessSettings::VISIBILITY_PUBLIC,
        ?string $privateAssetRoot = null,
    ): AssetAccessSettings {
        return new AssetAccessSettings($enabled, $protectedRoles, $visibility, $privateAssetRoot);
    }

    /**
     * Creates a repository backed by a shared in-memory options map.
     * Pass $saveCalls by reference to capture update_option calls.
     *
     * @param array<array{string, mixed}> $saveCalls
     */
    private function makeRepository(mixed $storedValue, array &$saveCalls = []): CallableAssetAccessSettingsRepository
    {
        $options = ['period_asset_access_settings' => $storedValue];

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

    private function makeRenderer(): AssetAccessSettingsPageRenderer
    {
        return new AssetAccessSettingsPageRenderer();
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettings — value object
    // -----------------------------------------------------------------------

    public function testIsEnabledReturnsTrueWhenEnabled(): void
    {
        $this->assertTrue($this->makeSettings(enabled: true)->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenDisabled(): void
    {
        $this->assertFalse($this->makeSettings(enabled: false)->isEnabled());
    }

    public function testProtectedRolesReturnsStoredArray(): void
    {
        $settings = $this->makeSettings(protectedRoles: ['subscriber', 'contributor']);

        $this->assertSame(['subscriber', 'contributor'], $settings->protectedRoles());
    }

    public function testProtectedRolesReturnsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->makeSettings()->protectedRoles());
    }

    public function testDefaultVisibilityReturnsStoredValue(): void
    {
        $settings = $this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PRIVATE);

        $this->assertSame(AssetAccessSettings::VISIBILITY_PRIVATE, $settings->defaultVisibility());
    }

    public function testVisibilityConstantPublic(): void
    {
        $this->assertSame('public', AssetAccessSettings::VISIBILITY_PUBLIC);
    }

    public function testVisibilityConstantPrivate(): void
    {
        $this->assertSame('private', AssetAccessSettings::VISIBILITY_PRIVATE);
    }

    public function testDefaultFactoryReturnsDisabled(): void
    {
        $this->assertFalse(AssetAccessSettings::default()->isEnabled());
    }

    public function testDefaultFactoryReturnsEmptyRoles(): void
    {
        $this->assertSame([], AssetAccessSettings::default()->protectedRoles());
    }

    public function testDefaultFactoryReturnsPublicVisibility(): void
    {
        $this->assertSame(AssetAccessSettings::VISIBILITY_PUBLIC, AssetAccessSettings::default()->defaultVisibility());
    }

    public function testPrivateAssetRootReturnsStoredValue(): void
    {
        $settings = $this->makeSettings(privateAssetRoot: '/var/private-assets');

        $this->assertSame('/var/private-assets', $settings->privateAssetRoot());
    }

    public function testSettingsNormalizesEmptyPrivateRootToNull(): void
    {
        $this->assertNull($this->makeSettings(privateAssetRoot: '')->privateAssetRoot());
        $this->assertNull($this->makeSettings(privateAssetRoot: '   ')->privateAssetRoot());
    }

    public function testDefaultFactoryReturnsNullPrivateAssetRoot(): void
    {
        $this->assertNull(AssetAccessSettings::default()->privateAssetRoot());
    }

    public function testWithEnabledProducesNewInstance(): void
    {
        $original = $this->makeSettings(enabled: false);
        $modified = $original->withEnabled(true);

        $this->assertFalse($original->isEnabled());
        $this->assertTrue($modified->isEnabled());
    }

    public function testWithProtectedRolesProducesNewInstance(): void
    {
        $original = $this->makeSettings(protectedRoles: []);
        $modified = $original->withProtectedRoles(['editor']);

        $this->assertSame([], $original->protectedRoles());
        $this->assertSame(['editor'], $modified->protectedRoles());
    }

    public function testWithDefaultVisibilityProducesNewInstance(): void
    {
        $original = $this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PUBLIC);
        $modified = $original->withDefaultVisibility(AssetAccessSettings::VISIBILITY_PRIVATE);

        $this->assertSame(AssetAccessSettings::VISIBILITY_PUBLIC, $original->defaultVisibility());
        $this->assertSame(AssetAccessSettings::VISIBILITY_PRIVATE, $modified->defaultVisibility());
    }

    public function testWithPrivateAssetRootProducesNewInstance(): void
    {
        $original = $this->makeSettings(privateAssetRoot: null);
        $modified = $original->withPrivateAssetRoot('/var/private-assets');

        $this->assertNull($original->privateAssetRoot());
        $this->assertSame('/var/private-assets', $modified->privateAssetRoot());
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsRepositoryInterface — contract
    // -----------------------------------------------------------------------

    public function testRepositoryImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetAccessSettingsRepositoryInterface::class, $this->makeRepository([]));
    }

    // -----------------------------------------------------------------------
    // CallableAssetAccessSettingsRepository — get
    // -----------------------------------------------------------------------

    public function testGetReturnsDefaultSettingsWhenOptionIsEmpty(): void
    {
        $settings = $this->makeRepository([])->get();

        $this->assertFalse($settings->isEnabled());
        $this->assertSame([], $settings->protectedRoles());
        $this->assertSame(AssetAccessSettings::VISIBILITY_PUBLIC, $settings->defaultVisibility());
    }

    public function testGetReturnsDefaultWhenStoredValueIsNotArray(): void
    {
        $settings = $this->makeRepository(false)->get();

        $this->assertInstanceOf(AssetAccessSettings::class, $settings);
        $this->assertFalse($settings->isEnabled());
    }

    public function testGetDeserializesEnabled(): void
    {
        $raw = ['enabled' => true, 'protected_roles' => [], 'default_visibility' => 'public'];

        $this->assertTrue($this->makeRepository($raw)->get()->isEnabled());
    }

    public function testGetDeserializesEnabledFalse(): void
    {
        $raw = ['enabled' => false, 'protected_roles' => [], 'default_visibility' => 'public'];

        $this->assertFalse($this->makeRepository($raw)->get()->isEnabled());
    }

    public function testGetDeserializesProtectedRoles(): void
    {
        $raw = ['enabled' => false, 'protected_roles' => ['subscriber', 'contributor'], 'default_visibility' => 'public'];

        $this->assertSame(['subscriber', 'contributor'], $this->makeRepository($raw)->get()->protectedRoles());
    }

    public function testGetFiltersNonStringRoles(): void
    {
        $raw = ['enabled' => false, 'protected_roles' => ['subscriber', 123, null, 'editor'], 'default_visibility' => 'public'];

        $this->assertSame(['subscriber', 'editor'], $this->makeRepository($raw)->get()->protectedRoles());
    }

    public function testGetDeserializesDefaultVisibility(): void
    {
        $raw = ['enabled' => false, 'protected_roles' => [], 'default_visibility' => 'private'];

        $this->assertSame('private', $this->makeRepository($raw)->get()->defaultVisibility());
    }

    public function testGetDeserializesPrivateAssetRoot(): void
    {
        $raw = [
            'enabled'            => false,
            'protected_roles'    => [],
            'default_visibility' => 'public',
            'private_asset_root' => '/var/private-assets',
        ];

        $this->assertSame('/var/private-assets', $this->makeRepository($raw)->get()->privateAssetRoot());
    }

    public function testGetNormalizesEmptyPrivateAssetRootToNull(): void
    {
        $raw = [
            'enabled'            => false,
            'protected_roles'    => [],
            'default_visibility' => 'public',
            'private_asset_root' => '',
        ];

        $this->assertNull($this->makeRepository($raw)->get()->privateAssetRoot());
    }

    public function testGetOptionIsCalledWithCorrectKey(): void
    {
        $capturedKey = null;

        $repo = new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$capturedKey): mixed {
                $capturedKey = $key;
                return [];
            },
            function (string $key, mixed $value): void {},
        );
        $repo->get();

        $this->assertSame('period_asset_access_settings', $capturedKey);
    }

    // -----------------------------------------------------------------------
    // CallableAssetAccessSettingsRepository — save
    // -----------------------------------------------------------------------

    public function testSaveUsesCorrectOptionKey(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings());

        $this->assertSame('period_asset_access_settings', $saveCalls[0][0]);
    }

    public function testSaveSerializesEnabled(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings(enabled: true));

        $this->assertTrue($saveCalls[0][1]['enabled']);
    }

    public function testSaveSerializesEnabledFalse(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings(enabled: false));

        $this->assertFalse($saveCalls[0][1]['enabled']);
    }

    public function testSaveSerializesProtectedRoles(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings(protectedRoles: ['editor', 'author']));

        $this->assertSame(['editor', 'author'], $saveCalls[0][1]['protected_roles']);
    }

    public function testSaveSerializesDefaultVisibility(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PRIVATE));

        $this->assertSame('private', $saveCalls[0][1]['default_visibility']);
    }

    public function testSaveSerializesPrivateAssetRoot(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings(privateAssetRoot: '/var/private-assets'));

        $this->assertSame('/var/private-assets', $saveCalls[0][1]['private_asset_root']);
    }

    public function testSaveMakesExactlyOneUpdateCall(): void
    {
        $saveCalls = [];
        $repo      = $this->makeRepository([], $saveCalls);
        $repo->save($this->makeSettings());

        $this->assertCount(1, $saveCalls);
    }

    public function testUpdateOptionIsNotCalledDirectly(): void
    {
        $called = false;

        $repo = new CallableAssetAccessSettingsRepository(
            fn(string $key, mixed $default): mixed => [],
            function (string $key, mixed $value) use (&$called): void {
                $called = true;
            },
        );
        $repo->save($this->makeSettings());

        $this->assertTrue($called, 'updateOption callable must be called instead of direct update_option()');
    }

    public function testGetOptionIsNotCalledDirectly(): void
    {
        $called = false;

        $repo = new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$called): mixed {
                $called = true;
                return [];
            },
            function (string $key, mixed $value): void {},
        );
        $repo->get();

        $this->assertTrue($called, 'getOption callable must be called instead of direct get_option()');
    }

    public function testGetThenSaveRoundtrip(): void
    {
        $options = [
            'period_asset_access_settings' => [
                'enabled'            => true,
                'protected_roles'    => ['subscriber'],
                'default_visibility' => 'private',
                'private_asset_root' => '/var/private-assets',
            ],
        ];

        $repo = new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$options): mixed {
                return $options[$key] ?? $default;
            },
            function (string $key, mixed $value) use (&$options): void {
                $options[$key] = $value;
            },
        );

        $loaded = $repo->get();
        $repo->save($loaded);
        $reloaded = $repo->get();

        $this->assertTrue($reloaded->isEnabled());
        $this->assertSame(['subscriber'], $reloaded->protectedRoles());
        $this->assertSame('private', $reloaded->defaultVisibility());
        $this->assertSame('/var/private-assets', $reloaded->privateAssetRoot());
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsPageRenderer — enabled checkbox
    // -----------------------------------------------------------------------

    public function testRenderContainsEnabledCheckbox(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), []);

        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function testRenderEnabledCheckedWhenEnabled(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(enabled: true), []);

        $this->assertStringContainsString(' checked', $html);
    }

    public function testRenderEnabledNotCheckedWhenDisabled(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(enabled: false), []);

        $this->assertStringNotContainsString(' checked', $html);
    }

    public function testRenderEnabledCheckboxNameContainsKey(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), []);

        $this->assertStringContainsString('period_asset_access[enabled]', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsPageRenderer — role checkboxes
    // -----------------------------------------------------------------------

    public function testRenderContainsCheckboxForEachAvailableRole(): void
    {
        $roles = ['subscriber' => 'Subscriber', 'editor' => 'Editor'];
        $html  = $this->makeRenderer()->render($this->makeSettings(), $roles);

        // 1 enabled + 2 role checkboxes
        $this->assertSame(3, substr_count($html, 'type="checkbox"'));
    }

    public function testRenderRoleCheckedWhenInProtectedRoles(): void
    {
        $roles    = ['subscriber' => 'Subscriber', 'editor' => 'Editor'];
        $settings = $this->makeSettings(protectedRoles: ['subscriber']);
        $html     = $this->makeRenderer()->render($settings, $roles);

        $this->assertStringContainsString('value="subscriber" checked', $html);
    }

    public function testRenderRoleNotCheckedWhenNotInProtectedRoles(): void
    {
        $roles    = ['subscriber' => 'Subscriber', 'editor' => 'Editor'];
        $settings = $this->makeSettings(protectedRoles: ['subscriber']);
        $html     = $this->makeRenderer()->render($settings, $roles);

        $this->assertStringNotContainsString('value="editor" checked', $html);
    }

    public function testRenderRoleCheckboxValueMatchesSlug(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), ['author' => 'Author']);

        $this->assertStringContainsString('value="author"', $html);
    }

    public function testRenderRoleLabelIsDisplayed(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), ['subscriber' => 'Subscriber']);

        $this->assertStringContainsString('Subscriber', $html);
    }

    public function testRenderRoleCheckboxNameContainsKey(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), ['editor' => 'Editor']);

        $this->assertStringContainsString('period_asset_access[protected_roles][]', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsPageRenderer — visibility select
    // -----------------------------------------------------------------------

    public function testRenderContainsVisibilitySelect(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), []);

        $this->assertStringContainsString('<select', $html);
    }

    public function testRenderVisibilitySelectNameContainsKey(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), []);

        $this->assertStringContainsString('period_asset_access[default_visibility]', $html);
    }

    public function testRenderPublicOptionSelectedWhenDefaultIsPublic(): void
    {
        $settings = $this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PUBLIC);
        $html     = $this->makeRenderer()->render($settings, []);

        $this->assertStringContainsString('value="public" selected', $html);
    }

    public function testRenderPrivateOptionSelectedWhenDefaultIsPrivate(): void
    {
        $settings = $this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PRIVATE);
        $html     = $this->makeRenderer()->render($settings, []);

        $this->assertStringContainsString('value="private" selected', $html);
    }

    public function testRenderPublicNotSelectedWhenDefaultIsPrivate(): void
    {
        $settings = $this->makeSettings(visibility: AssetAccessSettings::VISIBILITY_PRIVATE);
        $html     = $this->makeRenderer()->render($settings, []);

        $this->assertStringNotContainsString('value="public" selected', $html);
    }

    public function testRenderSelectContainsBothOptions(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), []);

        $this->assertStringContainsString('value="public"', $html);
        $this->assertStringContainsString('value="private"', $html);
    }

    public function testRenderOutputsPrivateAssetRootInput(): void
    {
        $html = $this->makeRenderer()->render(
            $this->makeSettings(privateAssetRoot: '/var/private-assets'),
            [],
        );

        $this->assertStringContainsString('Private asset root', $html);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('period_asset_access[private_asset_root]', $html);
        $this->assertStringContainsString('value="/var/private-assets"', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsPageRenderer — HTML escaping
    // -----------------------------------------------------------------------

    public function testRenderEscapesRoleSlugXss(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), ['<script>alert(1)</script>' => 'Bad Role']);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderEscapesRoleLabelXss(): void
    {
        $html = $this->makeRenderer()->render($this->makeSettings(), ['safe-slug' => '"onload="alert(1)']);

        $this->assertStringNotContainsString('"onload="', $html);
        $this->assertStringContainsString('&quot;onload=', $html);
    }

    public function testRenderEscapesPrivateAssetRoot(): void
    {
        $html = $this->makeRenderer()->render(
            $this->makeSettings(privateAssetRoot: '"><script>alert(1)</script>'),
            [],
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAccessSettingsPageRenderer — no inline styles
    // -----------------------------------------------------------------------

    public function testRenderHasNoInlineStyles(): void
    {
        $roles    = ['subscriber' => 'Subscriber', 'editor' => 'Editor'];
        $settings = $this->makeSettings(true, ['subscriber'], AssetAccessSettings::VISIBILITY_PRIVATE);
        $html     = $this->makeRenderer()->render($settings, $roles);

        $this->assertStringNotContainsString('style=', $html);
    }
}
