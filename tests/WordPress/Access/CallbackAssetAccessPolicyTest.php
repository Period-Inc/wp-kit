<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetAccessResult;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\CallbackAssetAccessPolicy;

final class CallbackAssetAccessPolicyTest extends TestCase
{
    private AssetRequestContext $context;

    protected function setUp(): void
    {
        $this->context = new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 5,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );
    }

    public function testBoolTrueAllows(): void
    {
        $policy = new CallbackAssetAccessPolicy(fn() => true);

        $this->assertTrue($policy->evaluate($this->context)->allowed());
    }

    public function testBoolFalseDenies(): void
    {
        $policy = new CallbackAssetAccessPolicy(fn() => false);

        $this->assertFalse($policy->evaluate($this->context)->allowed());
    }

    public function testBoolFalseHasDefaultReason(): void
    {
        $policy = new CallbackAssetAccessPolicy(fn() => false);

        $this->assertSame('Callback denied', $policy->evaluate($this->context)->reason());
    }

    public function testPassthroughAllowResult(): void
    {
        $policy = new CallbackAssetAccessPolicy(
            fn() => AssetAccessResult::allow()
        );

        $result = $policy->evaluate($this->context);
        $this->assertTrue($result->allowed());
        $this->assertNull($result->reason());
    }

    public function testPassthroughDenyResult(): void
    {
        $policy = new CallbackAssetAccessPolicy(
            fn() => AssetAccessResult::deny('custom reason')
        );

        $result = $policy->evaluate($this->context);
        $this->assertFalse($result->allowed());
        $this->assertSame('custom reason', $result->reason());
    }

    public function testCallbackReceivesContext(): void
    {
        $received = null;
        $policy = new CallbackAssetAccessPolicy(function (AssetRequestContext $ctx) use (&$received): bool {
            $received = $ctx;
            return true;
        });

        $policy->evaluate($this->context);

        $this->assertSame($this->context, $received);
    }

    public function testImplementsInterface(): void
    {
        $policy = new CallbackAssetAccessPolicy(fn() => true);

        $this->assertInstanceOf(AssetAccessPolicyInterface::class, $policy);
    }
}
