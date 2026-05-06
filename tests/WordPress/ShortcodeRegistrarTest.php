<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\ShortcodeRegistrar;

final class ShortcodeRegistrarTest extends TestCase
{
    public function testRegisterDoesNotThrowWithoutAddShortcode(): void
    {
        if (function_exists('add_shortcode')) {
            $this->markTestSkipped('add_shortcode exists in environment');
        }

        $registrar = new ShortcodeRegistrar();
        $registrar->register();

        $this->assertTrue(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegisterCallsAddShortcodeForAllThreeShortcodes(): void
    {
        if (function_exists('add_shortcode')) {
            $this->markTestSkipped('add_shortcode exists in environment');
        }

        $registered = [];
        eval(<<<'PHP'
function add_shortcode(string $tag, callable $callback): void {
    $GLOBALS['_test_registered_shortcodes'][] = $tag;
}
PHP
        );

        $registrar = new ShortcodeRegistrar();
        $registrar->register();

        $registered = $GLOBALS['_test_registered_shortcodes'] ?? [];

        $this->assertContains('document', $registered);
        $this->assertContains('title', $registered);
        $this->assertContains('site_name', $registered);
    }

    public function testRenderTitleReturnsString(): void
    {
        $registrar = new ShortcodeRegistrar();
        $result = $registrar->renderTitle([]);

        $this->assertIsString($result);
    }

    public function testRenderSiteNameReturnsString(): void
    {
        $registrar = new ShortcodeRegistrar();
        $result = $registrar->renderSiteName([]);

        $this->assertIsString($result);
    }

    public function testRenderDocumentReturnsFullHtml(): void
    {
        $registrar = new ShortcodeRegistrar();
        $result = $registrar->renderDocument([], '<p>Hello</p>');

        $this->assertStringContainsString('<!doctype html>', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
        $this->assertStringContainsString('</html>', $result);
    }

    public function testRenderDocumentWithNullContentRendersEmpty(): void
    {
        $registrar = new ShortcodeRegistrar();
        $result = $registrar->renderDocument([], null);

        $this->assertStringContainsString('<!doctype html>', $result);
        $this->assertStringContainsString('</html>', $result);
    }

    public function testRenderDocumentWithStringAttsDoesNotError(): void
    {
        $registrar = new ShortcodeRegistrar();
        $result = $registrar->renderDocument('', '<p>test</p>');

        $this->assertStringContainsString('<p>test</p>', $result);
    }

    public function testAllRenderMethodsWorkWithoutWordPress(): void
    {
        if (function_exists('get_bloginfo') || function_exists('wp_get_document_title')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $registrar = new ShortcodeRegistrar();

        $this->assertIsString($registrar->renderTitle([]));
        $this->assertIsString($registrar->renderSiteName([]));
        $this->assertIsString($registrar->renderDocument([], ''));
    }
}
