<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessManager;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetAccessResult;
use Period\WpKit\WordPress\Access\AssetRequestContext;

final class AssetAccessManagerTest extends TestCase
{
    private function makeContext(): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: '/uploads/file.pdf',
            assetUrl: 'https://example.com/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );
    }

    public function testAllowResultPassthrough(): void
    {
        $policy = new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $context): AssetAccessResult
            {
                return AssetAccessResult::allow();
            }
        };

        $result = (new AssetAccessManager($policy))->authorize($this->makeContext());

        $this->assertTrue($result->allowed());
        $this->assertNull($result->reason());
    }

    public function testDenyResultPassthrough(): void
    {
        $policy = new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $context): AssetAccessResult
            {
                return AssetAccessResult::deny('subscription required');
            }
        };

        $result = (new AssetAccessManager($policy))->authorize($this->makeContext());

        $this->assertFalse($result->allowed());
        $this->assertSame('subscription required', $result->reason());
    }

    public function testManagerDoesNotMutateContext(): void
    {
        $received = null;

        $policy = new class($received) implements AssetAccessPolicyInterface {
            public mixed $captured = null;

            public function evaluate(AssetRequestContext $context): AssetAccessResult
            {
                $this->captured = $context;
                return AssetAccessResult::allow();
            }
        };

        $context = $this->makeContext();
        (new AssetAccessManager($policy))->authorize($context);

        $this->assertSame($context, $policy->captured);
    }
}
