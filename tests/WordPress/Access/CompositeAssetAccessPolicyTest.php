<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpFramework\WordPress\Access\AssetAccessResult;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\CompositeAssetAccessPolicy;
use Period\WpFramework\WordPress\Access\CompositeMode;

final class CompositeAssetAccessPolicyTest extends TestCase
{
    private AssetRequestContext $context;

    protected function setUp(): void
    {
        $this->context = new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            requestTime: new DateTimeImmutable(),
        );
    }

    private function allow(): AssetAccessPolicyInterface
    {
        return new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $context): AssetAccessResult
            {
                return AssetAccessResult::allow();
            }
        };
    }

    private function deny(string $reason): AssetAccessPolicyInterface
    {
        return new class($reason) implements AssetAccessPolicyInterface {
            public function __construct(private readonly string $reason) {}

            public function evaluate(AssetRequestContext $context): AssetAccessResult
            {
                return AssetAccessResult::deny($this->reason);
            }
        };
    }

    // --- mode: all ---

    public function testAllAllowsWhenAllPoliciesAllow(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->allow(), $this->allow()],
            CompositeMode::All,
        );

        $this->assertTrue($policy->evaluate($this->context)->allowed());
    }

    public function testAllDeniesWhenOnePolicyDenies(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->allow(), $this->deny('not premium')],
            CompositeMode::All,
        );

        $this->assertFalse($policy->evaluate($this->context)->allowed());
    }

    public function testAllDeniesWhenAllPoliciesDeny(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->deny('reason A'), $this->deny('reason B')],
            CompositeMode::All,
        );

        $this->assertFalse($policy->evaluate($this->context)->allowed());
    }

    public function testAllConcatenatesDenyReasons(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->deny('reason A'), $this->deny('reason B')],
            CompositeMode::All,
        );

        $result = $policy->evaluate($this->context);
        $this->assertSame('reason A; reason B', $result->reason());
    }

    public function testAllWithSingleDenyHasSingleReason(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->allow(), $this->deny('only this failed')],
            CompositeMode::All,
        );

        $result = $policy->evaluate($this->context);
        $this->assertSame('only this failed', $result->reason());
    }

    public function testAllWithEmptyPoliciesAllows(): void
    {
        $policy = new CompositeAssetAccessPolicy([], CompositeMode::All);

        $this->assertTrue($policy->evaluate($this->context)->allowed());
    }

    // --- mode: any ---

    public function testAnyAllowsWhenOnePolicyAllows(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->deny('no role'), $this->allow()],
            CompositeMode::Any,
        );

        $this->assertTrue($policy->evaluate($this->context)->allowed());
    }

    public function testAnyAllowsWhenAllPoliciesAllow(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->allow(), $this->allow()],
            CompositeMode::Any,
        );

        $this->assertTrue($policy->evaluate($this->context)->allowed());
    }

    public function testAnyDeniesWhenAllPoliciesDeny(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->deny('reason A'), $this->deny('reason B')],
            CompositeMode::Any,
        );

        $this->assertFalse($policy->evaluate($this->context)->allowed());
    }

    public function testAnyConcatenatesDenyReasonsWhenAllDeny(): void
    {
        $policy = new CompositeAssetAccessPolicy(
            [$this->deny('reason A'), $this->deny('reason B')],
            CompositeMode::Any,
        );

        $result = $policy->evaluate($this->context);
        $this->assertSame('reason A; reason B', $result->reason());
    }

    public function testAnyWithEmptyPoliciesDenies(): void
    {
        $policy = new CompositeAssetAccessPolicy([], CompositeMode::Any);

        $this->assertFalse($policy->evaluate($this->context)->allowed());
    }

    public function testImplementsInterface(): void
    {
        $policy = new CompositeAssetAccessPolicy([], CompositeMode::All);

        $this->assertInstanceOf(AssetAccessPolicyInterface::class, $policy);
    }
}
