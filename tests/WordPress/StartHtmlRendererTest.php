<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\StartHtmlRenderer;
use Period\WpFramework\View\Element;
use Period\WpFramework\View\RawHtml;

final class StartHtmlRendererTest extends TestCase
{
    public function testRenderReturnsHtmlStartWithoutWordPressFunctions(): void
    {
        if (function_exists('language_attributes') || function_exists('get_bloginfo')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<!doctype html>', $output);
        $this->assertStringContainsString('<html lang="ja">', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<meta charset="UTF-8">', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCharsetArgumentIsReflected(): void
    {
        if (function_exists('language_attributes') || function_exists('get_bloginfo')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['charset' => 'Shift_JIS']);

        $this->assertStringContainsString('<meta charset="Shift_JIS">', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testElementsAreAddedToHead(): void
    {
        if (function_exists('language_attributes') || function_exists('get_bloginfo')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $elements = [
            '<title>Test</title>',
            new RawHtml('<meta name="robots" content="noindex">'),
        ];

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['elements' => $elements]);

        $this->assertStringContainsString('<title>Test</title>', $output);
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testVersionXhtmlUsesLanguageAttributesXhtml(): void
    {
        if (function_exists('language_attributes')) {
            $this->markTestSkipped('language_attributes exists in environment');
        }

        eval(<<<'PHP'
function language_attributes($type = '') {
    return $type === 'xhtml' ? 'xmlns="http://www.w3.org/1999/xhtml" lang="ja"' : 'lang="ja"';
}
PHP
        );

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['version' => 'xhtml']);

        $this->assertStringContainsString('<html xmlns="http://www.w3.org/1999/xhtml" lang="ja">', $output);
    }

    public function testEmptyElementsDoesNotBreak(): void
    {
        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['elements' => []]);

        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<meta charset="UTF-8">', $output);
    }

    public function testWpHeadDoesNotThrowWithoutWordPress(): void
    {
        if (function_exists('wp_head')) {
            $this->markTestSkipped('wp_head exists in environment');
        }

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<!doctype html>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpHeadFalseDoesNotCallWpHead(): void
    {
        if (function_exists('wp_head')) {
            $this->markTestSkipped('wp_head exists in environment');
        }

        $called = false;
        eval(<<<'PHP'
function wp_head() {
    $GLOBALS['_test_wp_head_called'] = true;
    echo '<!-- wp_head -->';
}
PHP
        );

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['include_wp_head' => false]);

        $this->assertArrayNotHasKey('_test_wp_head_called', $GLOBALS);
        $this->assertStringNotContainsString('<!-- wp_head -->', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpHeadTrueCallsWpHead(): void
    {
        if (function_exists('wp_head')) {
            $this->markTestSkipped('wp_head exists in environment');
        }

        eval(<<<'PHP'
function wp_head() {
    echo '<!-- wp_head -->';
}
PHP
        );

        $renderer = new StartHtmlRenderer();
        $output = $renderer->render(['include_wp_head' => true]);

        $this->assertStringContainsString('<!-- wp_head -->', $output);
    }
}
