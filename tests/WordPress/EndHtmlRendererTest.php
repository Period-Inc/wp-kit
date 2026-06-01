<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\EndHtmlRenderer;
use Period\WpKit\View\Element;
use Period\WpKit\View\RawHtml;

final class EndHtmlRendererTest extends TestCase
{
    public function testRenderReturnsHtmlEndWithoutWordPressFunctions(): void
    {
        if (function_exists('wp_footer')) {
            $this->markTestSkipped('wp_footer exists in environment');
        }

        $renderer = new EndHtmlRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    public function testElementsAreAddedBeforeClosingTags(): void
    {
        $renderer = new EndHtmlRenderer();
        $output = $renderer->render([
            'elements' => [
                '<script src="/app.js"></script>',
                new RawHtml('<script>window.test=1;</script>'),
                Element::el('div', ['class' => 'test'], 'x'),
            ],
        ]);

        $this->assertStringContainsString('<script src="/app.js"></script>', $output);
        $this->assertStringContainsString('<script>window.test=1;</script>', $output);
        $this->assertStringContainsString('<div class="test">x</div>', $output);
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpFooterUsesWpFooterFunction(): void
    {
        if (function_exists('wp_footer')) {
            $this->markTestSkipped('wp_footer exists in environment');
        }

        eval(<<<'PHP'
function wp_footer() {
    echo '<div id="wp-footer">footer</div>';
}
PHP
        );

        $renderer = new EndHtmlRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<div id="wp-footer">footer</div>', $output);
        $this->assertStringContainsString('</body>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpFooterFalseDoesNotCallWpFooter(): void
    {
        if (function_exists('wp_footer')) {
            $this->markTestSkipped('wp_footer exists in environment');
        }

        eval(<<<'PHP'
function wp_footer() {
    echo '<div id="wp-footer">footer</div>';
}
PHP
        );

        $renderer = new EndHtmlRenderer();
        $output = $renderer->render(['include_wp_footer' => false]);

        $this->assertStringNotContainsString('<div id="wp-footer">footer</div>', $output);
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    public function testEmptyElementsDoesNotBreak(): void
    {
        $renderer = new EndHtmlRenderer();
        $output = $renderer->render(['elements' => []]);

        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }
}
