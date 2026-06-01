<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\PageNavigationRenderer;

final class PageNavigationRendererTest extends TestCase
{
    public function testRenderReturnsEmptyWithoutWordPressFunctions(): void
    {
        if (function_exists('paginate_links')) {
            $this->markTestSkipped('paginate_links exists in environment');
        }

        $renderer = new PageNavigationRenderer();

        $this->assertSame('', $renderer->render());
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderHandlesPaginateLinksStringReturn(): void
    {
        if (function_exists('paginate_links')) {
            $this->markTestSkipped('paginate_links exists in environment');
        }

        eval(<<<'PHP'
function paginate_links(array $args = []) {
    return '<a href="http://example.com/page/1">1</a><a href="http://example.com/page/2">2</a>';
}

function get_pagenum_link($pagenum) {
    return 'http://example.com/page/' . (int) $pagenum;
}

function get_query_var($var) {
    return $var === 'paged' ? 1 : 0;
}
PHP
        );

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 2];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<nav class="period-wp-page-navigation"', $output);
        $this->assertStringContainsString('<a href="http://example.com/page/1">1</a>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderAddsCurrentClassForArrayLinks(): void
    {
        if (function_exists('paginate_links')) {
            $this->markTestSkipped('paginate_links exists in environment');
        }

        eval(<<<'PHP'
function paginate_links(array $args = []) {
    return [
        '<a href="http://example.com/page/1">1</a>',
        '<span class="current">2</span>',
    ];
}

function get_pagenum_link($pagenum) {
    return 'http://example.com/page/' . (int) $pagenum;
}

function get_query_var($var) {
    return $var === 'paged' ? 2 : 0;
}
PHP
        );

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<li class="is-current"><span class="current">2</span></li>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderReflectsClassArg(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render(['class' => 'custom-nav']);

        $this->assertStringContainsString('class="custom-nav"', $output);
        $this->assertStringContainsString('<nav', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderDoesNotThrowWithoutPostQuery(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 2];

        $renderer = new PageNavigationRenderer();

        $this->assertIsString($renderer->render());
    }

    public function testRenderReturnsEmptyWhenMaxPagesIsOne(): void
    {
        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 1];

        $renderer = new PageNavigationRenderer();

        $this->assertSame('', $renderer->render());
    }

    /**
     * @runInSeparateProcess
     */
    public function testAriaLabelFromAriaLabelArg(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render(['aria_label' => 'Page navigation']);

        $this->assertStringContainsString('aria-label="Page navigation"', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAriaLabelFromLabelArgWhenAriaLabelNotSet(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render(['label' => 'ページナビ']);

        $this->assertStringContainsString('aria-label="ページナビ"', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAriaLabelAriaLabelTakesPrecedenceOverLabel(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render(['aria_label' => 'Custom aria', 'label' => 'Ignored label']);

        $this->assertStringContainsString('aria-label="Custom aria"', $output);
        $this->assertStringNotContainsString('aria-label="Ignored label"', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testAriaLabelDefaultsToPaginationWhenNeitherSet(): void
    {
        $this->ensurePageNavigationFunctions();

        global $wp_query;
        $wp_query = (object) ['max_num_pages' => 3];

        $renderer = new PageNavigationRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('aria-label="pagination"', $output);
        $this->assertStringNotContainsString('ページナビゲーション', $output);
    }

    private function ensurePageNavigationFunctions(): void
    {
        if (!function_exists('paginate_links')) {
            eval(<<<'PHP'
function paginate_links(array $args = []) {
    $current = $args['current'] ?? 1;
    $total = $args['total'] ?? 1;
    $prev = $args['prev_text'] ?? '前へ';
    $next = $args['next_text'] ?? '次へ';
    $base = $args['base'] ?? 'http://example.com/%#%/';

    $links = '';
    if ($current > 1) {
        $links .= sprintf('<a href="%s">%s</a>', str_replace('%#%', $current - 1, $base), $prev);
    }
    for ($i = 1; $i <= $total; $i++) {
        $links .= sprintf('<a href="%s">%d</a>', str_replace('%#%', $i, $base), $i);
    }
    if ($current < $total) {
        $links .= sprintf('<a href="%s">%s</a>', str_replace('%#%', $current + 1, $base), $next);
    }

    return $links;
}
PHP
);
        }

        if (!function_exists('get_pagenum_link')) {
            eval(<<<'PHP'
function get_pagenum_link($pagenum) {
    return 'http://example.com/page/' . (int) $pagenum;
}
PHP
);
        }

        if (!function_exists('get_query_var')) {
            eval(<<<'PHP'
function get_query_var($var) {
    return $var === 'paged' ? 2 : 0;
}
PHP
);
        }
    }
}
