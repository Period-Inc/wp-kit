<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\RoleBasedAssetAccessPolicy;

final class RoleBasedAssetAccessPolicyTest extends TestCase
{
    private function makeContext(array $roles): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: $roles,
            requestTime: new DateTimeImmutable(),
        );
    }

    public function testImplementsInterface(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['editor']);

        $this->assertInstanceOf(AssetAccessPolicyInterface::class, $policy);
    }

    public function testAllowsWhenRoleMatches(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['editor', 'administrator']);
        $result = $policy->evaluate($this->makeContext(['editor']));

        $this->assertTrue($result->allowed());
    }

    public function testAllowsWhenOneOfMultipleUserRolesMatches(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['administrator']);
        $result = $policy->evaluate($this->makeContext(['subscriber', 'administrator']));

        $this->assertTrue($result->allowed());
    }

    public function testDeniesWhenRoleDoesNotMatch(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['administrator']);
        $result = $policy->evaluate($this->makeContext(['subscriber']));

        $this->assertFalse($result->allowed());
    }

    public function testDenyHasReason(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['administrator']);
        $result = $policy->evaluate($this->makeContext(['subscriber']));

        $this->assertNotNull($result->reason());
        $this->assertNotEmpty($result->reason());
    }

    public function testDeniesWhenUserHasNoRoles(): void
    {
        $policy = new RoleBasedAssetAccessPolicy(['editor']);
        $result = $policy->evaluate($this->makeContext([]));

        $this->assertFalse($result->allowed());
    }

    public function testDeniesWhenAllowedRolesIsEmpty(): void
    {
        $policy = new RoleBasedAssetAccessPolicy([]);
        $result = $policy->evaluate($this->makeContext(['administrator']));

        $this->assertFalse($result->allowed());
    }

    public function testEmptyRoleConfigHasReason(): void
    {
        $policy = new RoleBasedAssetAccessPolicy([]);
        $result = $policy->evaluate($this->makeContext(['administrator']));

        $this->assertNotNull($result->reason());
    }

    public function testNoWordPressDependency(): void
    {
        // RoleBasedAssetAccessPolicy must evaluate without WordPress runtime.
        $policy = new RoleBasedAssetAccessPolicy(['editor']);
        $result = $policy->evaluate($this->makeContext(['editor']));

        $this->assertTrue($result->allowed());
    }
}
