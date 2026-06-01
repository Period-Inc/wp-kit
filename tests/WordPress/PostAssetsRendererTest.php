<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\PostAssets;
use Period\WpKit\WordPress\PostAssetsRenderer;
use Period\WpKit\WordPress\PostMetaManager;
use Period\WpKit\WordPress\ScriptStyleRegistrar;
use ReflectionClass;

class PostAssetsRendererTest extends TestCase
{
    private const POST_ID = 42;

    private function makeRenderer(): array
    {
        $meta = new PostMetaManager();
        $assets = new PostAssets($meta);
        $registrar = new ScriptStyleRegistrar();

        return [new PostAssetsRenderer($assets, $meta, $registrar), $registrar];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCssFileEnqueuesStyle(): void
    {
        eval('
            function get_post_meta($postId, $key, $single = false) {
                global $PARENDER_META;
                return $PARENDER_META[$postId][$key] ?? null;
            }
            function wp_register_style($handle, $src, $deps = [], $ver = false, $media = "all"): void {
                global $PARENDER_REGISTERED_STYLES;
                $PARENDER_REGISTERED_STYLES[] = ["handle" => $handle, "src" => $src];
            }
            function wp_enqueue_style($handle): void {
                global $PARENDER_ENQUEUED_STYLES;
                $PARENDER_ENQUEUED_STYLES[] = $handle;
            }
            function wp_add_inline_style($handle, $data): bool { return true; }
        ');

        global $PARENDER_META, $PARENDER_REGISTERED_STYLES, $PARENDER_ENQUEUED_STYLES;
        $PARENDER_META = [self::POST_ID => ['cssfile' => 'https://example.com/style.css']];
        $PARENDER_REGISTERED_STYLES = [];
        $PARENDER_ENQUEUED_STYLES = [];

        [$renderer] = $this->makeRenderer();
        $renderer->renderCss(self::POST_ID);

        $this->assertCount(1, $PARENDER_ENQUEUED_STYLES);
        $this->assertSame('post-assets-42-css-file', $PARENDER_ENQUEUED_STYLES[0]);
        $this->assertSame('https://example.com/style.css', $PARENDER_REGISTERED_STYLES[0]['src']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testJsFileEnqueuesScript(): void
    {
        eval('
            function get_post_meta($postId, $key, $single = false) {
                global $PARENDER_META;
                return $PARENDER_META[$postId][$key] ?? null;
            }
            function wp_register_script($handle, $src, $deps = [], $ver = false, $in_footer = false): void {
                global $PARENDER_REGISTERED_SCRIPTS;
                $PARENDER_REGISTERED_SCRIPTS[] = ["handle" => $handle, "src" => $src];
            }
            function wp_add_inline_script($handle, $data, $position = "after"): bool { return true; }
        ');

        global $PARENDER_META, $PARENDER_REGISTERED_SCRIPTS, $PERIOD_WP_ENQUEUED_SCRIPTS;
        $PARENDER_META = [self::POST_ID => ['jsfile' => 'https://example.com/script.js']];
        $PARENDER_REGISTERED_SCRIPTS = [];
        $PERIOD_WP_ENQUEUED_SCRIPTS = [];

        [$renderer] = $this->makeRenderer();
        $renderer->renderJs(self::POST_ID);

        $this->assertCount(1, $PERIOD_WP_ENQUEUED_SCRIPTS);
        $this->assertSame('post-assets-42-js-file', $PERIOD_WP_ENQUEUED_SCRIPTS[0]['handle']);
        $this->assertSame('https://example.com/script.js', $PARENDER_REGISTERED_SCRIPTS[0]['src']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCssMinifiedTakesPriorityOverCompiled(): void
    {
        eval('
            function get_post_meta($postId, $key, $single = false) {
                global $PARENDER_META;
                return $PARENDER_META[$postId][$key] ?? null;
            }
            function wp_register_style($handle, $src, $deps = [], $ver = false, $media = "all"): void {}
            function wp_enqueue_style($handle): void {}
            function wp_add_inline_style($handle, $data): bool {
                global $PARENDER_INLINE_STYLES;
                $PARENDER_INLINE_STYLES[] = ["handle" => $handle, "data" => $data];
                return true;
            }
        ');

        global $PARENDER_META, $PARENDER_INLINE_STYLES;
        $PARENDER_META = [self::POST_ID => [
            'csscode_minified' => 'minified{}',
            'csscode_compiled' => 'compiled{}',
        ]];
        $PARENDER_INLINE_STYLES = [];

        [$renderer] = $this->makeRenderer();
        $renderer->renderCss(self::POST_ID);

        $this->assertCount(1, $PARENDER_INLINE_STYLES);
        $this->assertSame('post-assets-42-css-inline', $PARENDER_INLINE_STYLES[0]['handle']);
        $this->assertSame('minified{}', $PARENDER_INLINE_STYLES[0]['data']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCssCompiledUsedWhenMinifiedEmpty(): void
    {
        eval('
            function get_post_meta($postId, $key, $single = false) {
                global $PARENDER_META;
                return $PARENDER_META[$postId][$key] ?? null;
            }
            function wp_register_style($handle, $src, $deps = [], $ver = false, $media = "all"): void {}
            function wp_enqueue_style($handle): void {}
            function wp_add_inline_style($handle, $data): bool {
                global $PARENDER_INLINE_STYLES;
                $PARENDER_INLINE_STYLES[] = ["handle" => $handle, "data" => $data];
                return true;
            }
        ');

        global $PARENDER_META, $PARENDER_INLINE_STYLES;
        $PARENDER_META = [self::POST_ID => [
            'csscode_minified' => '',
            'csscode_compiled' => 'compiled{}',
        ]];
        $PARENDER_INLINE_STYLES = [];

        [$renderer] = $this->makeRenderer();
        $renderer->renderCss(self::POST_ID);

        $this->assertCount(1, $PARENDER_INLINE_STYLES);
        $this->assertSame('post-assets-42-css-inline', $PARENDER_INLINE_STYLES[0]['handle']);
        $this->assertSame('compiled{}', $PARENDER_INLINE_STYLES[0]['data']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testJsCodeUsesInlineScript(): void
    {
        eval('
            function get_post_meta($postId, $key, $single = false) {
                global $PARENDER_META;
                return $PARENDER_META[$postId][$key] ?? null;
            }
            function wp_register_script($handle, $src, $deps = [], $ver = false, $in_footer = false): void {}
            function wp_add_inline_script($handle, $data, $position = "after"): bool {
                global $PARENDER_INLINE_SCRIPTS;
                $PARENDER_INLINE_SCRIPTS[] = ["handle" => $handle, "data" => $data];
                return true;
            }
        ');

        global $PARENDER_META, $PARENDER_INLINE_SCRIPTS, $PERIOD_WP_ENQUEUED_SCRIPTS;
        $PARENDER_META = [self::POST_ID => ['jscode' => 'console.log(1);']];
        $PARENDER_INLINE_SCRIPTS = [];
        $PERIOD_WP_ENQUEUED_SCRIPTS = [];

        [$renderer] = $this->makeRenderer();
        $renderer->renderJs(self::POST_ID);

        $this->assertCount(1, $PARENDER_INLINE_SCRIPTS);
        $this->assertSame('post-assets-42-js-inline', $PARENDER_INLINE_SCRIPTS[0]['handle']);
        $this->assertSame('console.log(1);', $PARENDER_INLINE_SCRIPTS[0]['data']);
    }

    public function testAllEmptyProducesNoEnqueueOrInline(): void
    {
        [$renderer, $registrar] = $this->makeRenderer();
        $renderer->render(self::POST_ID);

        $rc = new ReflectionClass($registrar);

        $stylesProperty = $rc->getProperty('registeredStyles');
        $this->assertEmpty($stylesProperty->getValue($registrar));

        $scriptsProperty = $rc->getProperty('registeredScripts');
        $this->assertEmpty($scriptsProperty->getValue($registrar));
    }
}
