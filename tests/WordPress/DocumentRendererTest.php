<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\DocumentRenderer;

final class DocumentRendererTest extends TestCase
{
    public function testRenderProducesFullHtmlDocument(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<!doctype html>', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<body', $output);
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    public function testContentIsPlacedBetweenBodyAndEndTags(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render('<h1>Hello</h1>');

        $bodyPos = strpos($output, '<body');
        $contentPos = strpos($output, '<h1>Hello</h1>');
        $endBodyPos = strpos($output, '</body>');

        $this->assertNotFalse($bodyPos);
        $this->assertNotFalse($contentPos);
        $this->assertNotFalse($endBodyPos);
        $this->assertGreaterThan($bodyPos, $contentPos);
        $this->assertLessThan($endBodyPos, $contentPos);
    }

    public function testContentIsNotEscaped(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render('<h1>Hello & World</h1>');

        $this->assertStringContainsString('<h1>Hello & World</h1>', $output);
    }

    public function testWorksWithoutWordPressFunctions(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render('<p>test</p>');

        $this->assertStringContainsString('<!doctype html>', $output);
        $this->assertStringContainsString('<p>test</p>', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    public function testHeadElementsPassedToStartRenderer(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render('', [
            'head_elements' => ['<meta name="description" content="test">'],
        ]);

        $this->assertStringContainsString('<meta name="description" content="test">', $output);
    }

    public function testBodyClassPassedToBodyRenderer(): void
    {
        if (function_exists('get_body_class')) {
            $this->markTestSkipped('get_body_class exists in environment');
        }

        $renderer = new DocumentRenderer();
        $output = $renderer->render('', ['body_class' => ['home', 'my-page']]);

        $this->assertStringContainsString('home', $output);
        $this->assertStringContainsString('my-page', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpHeadFalseDoesNotCallWpHead(): void
    {
        if (function_exists('wp_head')) {
            $this->markTestSkipped('wp_head exists in environment');
        }

        eval(<<<'PHP'
function wp_head() {
    $GLOBALS['_doc_test_wp_head_called'] = true;
    echo '<!-- wp_head -->';
}
PHP
        );

        $renderer = new DocumentRenderer();
        $output = $renderer->render('', ['include_wp_head' => false]);

        $this->assertArrayNotHasKey('_doc_test_wp_head_called', $GLOBALS);
        $this->assertStringNotContainsString('<!-- wp_head -->', $output);
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
    $GLOBALS['_doc_test_wp_footer_called'] = true;
    echo '<!-- wp_footer -->';
}
PHP
        );

        $renderer = new DocumentRenderer();
        $output = $renderer->render('', ['include_wp_footer' => false]);

        $this->assertArrayNotHasKey('_doc_test_wp_footer_called', $GLOBALS);
        $this->assertStringNotContainsString('<!-- wp_footer -->', $output);
    }

    public function testOutputOrderIsDoctype_Head_Body_Content_End(): void
    {
        $renderer = new DocumentRenderer();
        $output = $renderer->render('<p>content</p>');

        $doctypePos = strpos($output, '<!doctype html>');
        $headPos = strpos($output, '<head>');
        $bodyPos = strpos($output, '<body');
        $contentPos = strpos($output, '<p>content</p>');
        $endBodyPos = strpos($output, '</body>');
        $endHtmlPos = strpos($output, '</html>');

        $this->assertGreaterThan($doctypePos, $headPos);
        $this->assertGreaterThan($headPos, $bodyPos);
        $this->assertGreaterThan($bodyPos, $contentPos);
        $this->assertGreaterThan($contentPos, $endBodyPos);
        $this->assertGreaterThan($endBodyPos, $endHtmlPos);
    }
}
