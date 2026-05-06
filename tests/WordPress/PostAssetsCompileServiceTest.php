<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use Period\WpFramework\WordPress\PostAssetsCompileResult;
use Period\WpFramework\WordPress\PostAssetsCompileService;
use Period\WpFramework\WordPress\PostAssetsCompilerInterface;
use Period\WpFramework\WordPress\PostMetaManager;
use PHPUnit\Framework\TestCase;

final class PostAssetsCompileServiceTest extends TestCase
{
    /** @runInSeparateProcess */
    public function testCompileSuccessUpdatesCompiledMetaAndClearsError(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $compiler = new class implements PostAssetsCompilerInterface {
            public function compile(string $source): PostAssetsCompileResult
            {
                return new PostAssetsCompileResult(true, 'body{color:red;}');
            }
        };

        $service = new PostAssetsCompileService(
            new PostMetaManager(),
            $compiler
        );

        $result = $service->compileCss(123, 'body { color: red; }');

        $this->assertTrue($result->success);

        $this->assertSame('csscode_compiled', $METABOX_TEST_META_UPDATES[0]['key']);
        $this->assertSame('body{color:red;}', $METABOX_TEST_META_UPDATES[0]['value']);

        $this->assertSame('csscode_last_compile_error', $METABOX_TEST_META_UPDATES[1]['key']);
        $this->assertSame('', $METABOX_TEST_META_UPDATES[1]['value']);

        $this->assertSame('csscode_last_compiled_at', $METABOX_TEST_META_UPDATES[2]['key']);
        $this->assertIsString($METABOX_TEST_META_UPDATES[2]['value']);
        $this->assertNotSame('', $METABOX_TEST_META_UPDATES[2]['value']);
    }

    /** @runInSeparateProcess */
    public function testCompileFailureOnlyUpdatesErrorMeta(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $compiler = new class implements PostAssetsCompilerInterface {
            public function compile(string $source): PostAssetsCompileResult
            {
                return new PostAssetsCompileResult(false, '', 'Compile failed');
            }
        };

        $service = new PostAssetsCompileService(
            new PostMetaManager(),
            $compiler
        );

        $result = $service->compileCss(123, '$color: ;');

        $this->assertFalse($result->success);

        $this->assertCount(1, $METABOX_TEST_META_UPDATES);
        $this->assertSame('csscode_last_compile_error', $METABOX_TEST_META_UPDATES[0]['key']);
        $this->assertSame('Compile failed', $METABOX_TEST_META_UPDATES[0]['value']);
    }
}
