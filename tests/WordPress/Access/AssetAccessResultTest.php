<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessResult;

final class AssetAccessResultTest extends TestCase
{
    public function testAllowIsAllowed(): void
    {
        $result = AssetAccessResult::allow();

        $this->assertTrue($result->allowed());
    }

    public function testAllowHasNullReason(): void
    {
        $result = AssetAccessResult::allow();

        $this->assertNull($result->reason());
    }

    public function testDenyIsNotAllowed(): void
    {
        $result = AssetAccessResult::deny('insufficient role');

        $this->assertFalse($result->allowed());
    }

    public function testDenyPreservesReason(): void
    {
        $result = AssetAccessResult::deny('insufficient role');

        $this->assertSame('insufficient role', $result->reason());
    }
}
