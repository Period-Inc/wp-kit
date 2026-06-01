<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessManagerFactory;
use Period\WpKit\WordPress\Access\AssetAccessPolicyFactory;
use Period\WpKit\WordPress\Access\AssetAccessSettings;
use Period\WpKit\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\CallableAssetAccessSettingsRepository;

final class AssetAccessManagerFactoryTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeRepository(array $stored = [], int &$getCalls = 0): AssetAccessSettingsRepositoryInterface
    {
        $options = ['period_asset_access_settings' => $stored];

        return new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$options, &$getCalls): mixed {
                $getCalls++;
                return array_key_exists($key, $options) ? $options[$key] : $default;
            },
            function (string $key, mixed $value) use (&$options): void {
                $options[$key] = $value;
            },
        );
    }

    private function makeFactory(array $stored = [], int &$getCalls = 0): AssetAccessManagerFactory
    {
        return new AssetAccessManagerFactory(
            $this->makeRepository($stored, $getCalls),
            new AssetAccessPolicyFactory(),
        );
    }

    private function makeContext(array $userRoles = []): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath:        '/uploads/file.pdf',
            assetUrl:         'https://example.com/uploads/file.pdf',
            currentUserId:    1,
            currentUserRoles: $userRoles,
            requestTime:      new DateTimeImmutable('2026-01-01T00:00:00Z'),
        );
    }

    private function storedPublic(): array
    {
        return ['enabled' => true, 'protected_roles' => [], 'default_visibility' => 'public'];
    }

    private function storedPrivateNoRoles(): array
    {
        return ['enabled' => true, 'protected_roles' => [], 'default_visibility' => 'private'];
    }

    private function storedPrivateWithRoles(array $roles): array
    {
        return ['enabled' => true, 'protected_roles' => $roles, 'default_visibility' => 'private'];
    }

    // -----------------------------------------------------------------------
    // Repository interaction
    // -----------------------------------------------------------------------

    public function testSettingsRepositoryIsReadOnCreate(): void
    {
        $getCalls = 0;
        $factory  = $this->makeFactory(getCalls: $getCalls);

        $factory->create();

        $this->assertGreaterThanOrEqual(1, $getCalls);
    }

    public function testRepositoryIsReadEachTimeCreateIsCalled(): void
    {
        $getCalls = 0;
        $factory  = $this->makeFactory(getCalls: $getCalls);

        $factory->create();
        $factory->create();

        $this->assertGreaterThanOrEqual(2, $getCalls);
    }

    // -----------------------------------------------------------------------
    // Returned manager type
    // -----------------------------------------------------------------------

    public function testCreateReturnsAssetAccessManager(): void
    {
        $manager = $this->makeFactory()->create();

        $this->assertInstanceOf(\Period\WpKit\WordPress\Access\AssetAccessManager::class, $manager);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $factory = $this->makeFactory();

        $m1 = $factory->create();
        $m2 = $factory->create();

        $this->assertNotSame($m1, $m2);
    }

    // -----------------------------------------------------------------------
    // Manager behaviour — public settings
    // -----------------------------------------------------------------------

    public function testManagerAllowsWhenSettingsArePublic(): void
    {
        $manager = $this->makeFactory($this->storedPublic())->create();

        $this->assertTrue($manager->authorize($this->makeContext())->allowed());
    }

    public function testManagerAllowsWhenDisabled(): void
    {
        $stored  = ['enabled' => false, 'protected_roles' => [], 'default_visibility' => 'private'];
        $manager = $this->makeFactory($stored)->create();

        $this->assertTrue($manager->authorize($this->makeContext())->allowed());
    }

    // -----------------------------------------------------------------------
    // Manager behaviour — private without roles
    // -----------------------------------------------------------------------

    public function testManagerDeniesWhenPrivateAndNoRoles(): void
    {
        $manager = $this->makeFactory($this->storedPrivateNoRoles())->create();

        $this->assertFalse($manager->authorize($this->makeContext(['administrator']))->allowed());
    }

    public function testManagerDeniesGuestWhenPrivateAndNoRoles(): void
    {
        $manager = $this->makeFactory($this->storedPrivateNoRoles())->create();

        $this->assertFalse($manager->authorize($this->makeContext([]))->allowed());
    }

    // -----------------------------------------------------------------------
    // Manager behaviour — role-based
    // -----------------------------------------------------------------------

    public function testManagerAllowsMatchingRole(): void
    {
        $manager = $this->makeFactory($this->storedPrivateWithRoles(['subscriber', 'editor']))->create();

        $this->assertTrue($manager->authorize($this->makeContext(['editor']))->allowed());
    }

    public function testManagerDeniesNonMatchingRole(): void
    {
        $manager = $this->makeFactory($this->storedPrivateWithRoles(['subscriber']))->create();

        $this->assertFalse($manager->authorize($this->makeContext(['editor']))->allowed());
    }

    public function testManagerDeniesGuestWhenRoleBased(): void
    {
        $manager = $this->makeFactory($this->storedPrivateWithRoles(['subscriber']))->create();

        $this->assertFalse($manager->authorize($this->makeContext([]))->allowed());
    }

    // -----------------------------------------------------------------------
    // Settings change is reflected in next create() call
    // -----------------------------------------------------------------------

    public function testManagerReflectsLatestSettings(): void
    {
        $options = ['period_asset_access_settings' => $this->storedPublic()];

        $repo = new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$options): mixed {
                return $options[$key] ?? $default;
            },
            function (string $key, mixed $value) use (&$options): void {
                $options[$key] = $value;
            },
        );

        $factory = new AssetAccessManagerFactory($repo, new AssetAccessPolicyFactory());

        $managerBefore = $factory->create();
        $this->assertTrue($managerBefore->authorize($this->makeContext())->allowed());

        // Change settings to private with no roles
        $repo->save(new AssetAccessSettings(true, [], AssetAccessSettings::VISIBILITY_PRIVATE));

        $managerAfter = $factory->create();
        $this->assertFalse($managerAfter->authorize($this->makeContext())->allowed());
    }
}
