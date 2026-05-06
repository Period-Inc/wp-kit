<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use Period\WpFramework\WordPress\ScssPhpPostAssetsCompiler;
use PHPUnit\Framework\TestCase;

final class ScssPhpPostAssetsCompilerTest extends TestCase
{
    public function testCompilesScssToCss(): void
    {
        $compiler = new ScssPhpPostAssetsCompiler();

        $result = $compiler->compile('$color: red; body { color: $color; }');

        $this->assertTrue($result->success);
        $this->assertStringContainsString('body', $result->compiled);
        $this->assertStringContainsString('color: red', $result->compiled);
        $this->assertNull($result->error);
    }

    public function testPassesPlainCssThroughCompilePipeline(): void
    {
        $compiler = new ScssPhpPostAssetsCompiler();

        $result = $compiler->compile('body { color: red; }');

        $this->assertTrue($result->success);
        $this->assertStringContainsString('body', $result->compiled);
        $this->assertStringContainsString('color: red', $result->compiled);
    }

    public function testReturnsFailureOnInvalidScss(): void
    {
        $compiler = new ScssPhpPostAssetsCompiler();

        $result = $compiler->compile('$color: ; body { color: $color; }');

        $this->assertFalse($result->success);
        $this->assertSame('', $result->compiled);
        $this->assertIsString($result->error);
        $this->assertNotSame('', $result->error);
    }
}
