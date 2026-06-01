<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Value;

final class ValueTest extends TestCase
{
    public function testFallbackReturnsFallbackForEmptyValue(): void
    {
        $this->assertSame('fallback', Value::fallback('', 'fallback'));
    }

    public function testFallbackPreservesZeroWhenConsiderZeroTrue(): void
    {
        $this->assertSame(0, Value::fallback(0, 'fallback', true));
    }

    public function testFallbackUsesFallbackForZeroWhenConsiderZeroFalse(): void
    {
        $this->assertSame('fallback', Value::fallback(0, 'fallback', false));
    }
}
