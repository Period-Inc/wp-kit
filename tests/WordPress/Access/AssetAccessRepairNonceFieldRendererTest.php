<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessRepairNonceFieldRenderer;

final class AssetAccessRepairNonceFieldRendererTest extends TestCase
{
    public function testNonceRendererCallsInjectedCallable(): void
    {
        $calls = [];
        $renderer = new AssetAccessRepairNonceFieldRenderer(
            function (string $action) use (&$calls): string {
                $calls[] = $action;

                return '<input type="hidden" name="_wpnonce" value="abc">';
            },
        );

        $this->assertSame('<input type="hidden" name="_wpnonce" value="abc">', $renderer->render());
        $this->assertSame(['period_asset_access_repair'], $calls);
    }

    public function testNonceRendererCastsReturnValueToString(): void
    {
        $renderer = new AssetAccessRepairNonceFieldRenderer(
            static fn(string $action): int => 123,
        );

        $this->assertSame('123', $renderer->render());
    }
}
