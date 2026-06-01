<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessPolicyFactory;
use Period\WpKit\WordPress\Access\AssetAccessSettings;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\PrivateAssetAccessPolicy;
use Period\WpKit\WordPress\Access\PublicAssetAccessPolicy;
use Period\WpKit\WordPress\Access\RoleBasedAssetAccessPolicy;

final class AssetAccessPolicyFactoryTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeSettings(
        bool $enabled         = true,
        array $protectedRoles = [],
        string $visibility    = AssetAccessSettings::VISIBILITY_PUBLIC,
    ): AssetAccessSettings {
        return new AssetAccessSettings($enabled, $protectedRoles, $visibility);
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

    private function factory(): AssetAccessPolicyFactory
    {
        return new AssetAccessPolicyFactory();
    }

    // -----------------------------------------------------------------------
    // Policy type selection
    // -----------------------------------------------------------------------

    public function testDisabledSettingsReturnPublicPolicy(): void
    {
        $settings = $this->makeSettings(enabled: false);
        $policy   = $this->factory()->create($settings);

        $this->assertInstanceOf(PublicAssetAccessPolicy::class, $policy);
    }

    public function testEnabledWithPublicVisibilityReturnsPublicPolicy(): void
    {
        $settings = $this->makeSettings(
            enabled:    true,
            visibility: AssetAccessSettings::VISIBILITY_PUBLIC,
        );
        $policy = $this->factory()->create($settings);

        $this->assertInstanceOf(PublicAssetAccessPolicy::class, $policy);
    }

    public function testEnabledWithPrivateVisibilityAndRolesReturnsRoleBasedPolicy(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: ['editor', 'author'],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertInstanceOf(RoleBasedAssetAccessPolicy::class, $policy);
    }

    public function testEnabledWithPrivateVisibilityAndNoRolesReturnsPrivatePolicy(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: [],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertInstanceOf(PrivateAssetAccessPolicy::class, $policy);
    }

    public function testDisabledWithPrivateVisibilityStillReturnsPublicPolicy(): void
    {
        $settings = $this->makeSettings(
            enabled:    false,
            visibility: AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertInstanceOf(PublicAssetAccessPolicy::class, $policy);
    }

    // -----------------------------------------------------------------------
    // Policy evaluation — public
    // -----------------------------------------------------------------------

    public function testPublicPolicyAllowsAnyUser(): void
    {
        $settings = $this->makeSettings(enabled: false);
        $policy   = $this->factory()->create($settings);

        $this->assertTrue($policy->evaluate($this->makeContext())->allowed());
    }

    public function testPublicPolicyAllowsGuestUser(): void
    {
        $settings = $this->makeSettings(
            enabled:    true,
            visibility: AssetAccessSettings::VISIBILITY_PUBLIC,
        );
        $policy = $this->factory()->create($settings);
        $ctx    = new AssetRequestContext(
            assetPath:        '/file.pdf',
            assetUrl:         'https://example.com/file.pdf',
            currentUserId:    0,
            currentUserRoles: [],
            requestTime:      new DateTimeImmutable('2026-01-01'),
        );

        $this->assertTrue($policy->evaluate($ctx)->allowed());
    }

    // -----------------------------------------------------------------------
    // Policy evaluation — private
    // -----------------------------------------------------------------------

    public function testPrivatePolicyDeniesAllRequests(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: [],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertFalse($policy->evaluate($this->makeContext(['administrator']))->allowed());
    }

    // -----------------------------------------------------------------------
    // Policy evaluation — role-based
    // -----------------------------------------------------------------------

    public function testRoleBasedPolicyAllowsUserWithMatchingRole(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: ['subscriber', 'editor'],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertTrue($policy->evaluate($this->makeContext(['editor']))->allowed());
    }

    public function testRoleBasedPolicyDeniesUserWithoutMatchingRole(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: ['subscriber'],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertFalse($policy->evaluate($this->makeContext(['editor']))->allowed());
    }

    public function testRoleBasedPolicyDeniesGuestUser(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: ['subscriber'],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertFalse($policy->evaluate($this->makeContext([]))->allowed());
    }

    public function testRoleBasedPolicyAllowsUserWithAnyOfMultipleRoles(): void
    {
        $settings = $this->makeSettings(
            enabled:        true,
            protectedRoles: ['editor', 'author', 'contributor'],
            visibility:     AssetAccessSettings::VISIBILITY_PRIVATE,
        );
        $policy = $this->factory()->create($settings);

        $this->assertTrue($policy->evaluate($this->makeContext(['contributor']))->allowed());
    }

    // -----------------------------------------------------------------------
    // Factory is stateless — multiple calls produce independent policies
    // -----------------------------------------------------------------------

    public function testFactoryProducesNewInstanceOnEachCall(): void
    {
        $settings = $this->makeSettings(enabled: false);
        $factory  = $this->factory();

        $p1 = $factory->create($settings);
        $p2 = $factory->create($settings);

        $this->assertNotSame($p1, $p2);
    }
}
