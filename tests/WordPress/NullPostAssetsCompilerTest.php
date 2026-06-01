<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use Period\WpKit\WordPress\NullPostAssetsCompiler;
use PHPUnit\Framework\TestCase;

final class NullPostAssetsCompilerTest extends TestCase
{
    public function testCompileReturnsTrimmedSource(): void
    {
        $compiler = new NullPostAssetsCompiler();

        $result = $compiler->compile("
body {
    color: red;
}
");

        $this->assertTrue($result->success);

        $this->assertSame(
            "body {\n    color: red;\n}",
            $result->compiled
        );

        $this->assertNull($result->error);
    }
}
