<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\BodyRenderer;

final class BodyRendererTest extends TestCase
{
    public function testRenderReturnsBodyOpenTag(): void
    {
        $renderer = new BodyRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<body>', $output);
    }

    public function testClassStringIsApplied(): void
    {
        if (function_exists('get_body_class')) {
            $this->markTestSkipped('get_body_class exists in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render(['class' => 'page-home']);

        $this->assertStringContainsString('class="page-home"', $output);
    }

    public function testClassArrayIsApplied(): void
    {
        if (function_exists('get_body_class')) {
            $this->markTestSkipped('get_body_class exists in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render(['class' => ['page-home', 'logged-in']]);

        $this->assertStringContainsString('page-home', $output);
        $this->assertStringContainsString('logged-in', $output);
    }

    public function testEmptyClassOmitsAttribute(): void
    {
        if (function_exists('get_body_class')) {
            $this->markTestSkipped('get_body_class exists in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render(['class' => []]);

        $this->assertStringContainsString('<body>', $output);
        $this->assertStringNotContainsString('class=', $output);
    }

    public function testDoesNotThrowWithoutWordPressFunctions(): void
    {
        if (function_exists('get_body_class') || function_exists('wp_body_open')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render();

        $this->assertStringContainsString('<body>', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetBodyClassMergesClasses(): void
    {
        if (function_exists('get_body_class')) {
            $this->markTestSkipped('get_body_class exists in environment');
        }

        eval(<<<'PHP'
function get_body_class(array $extra = []): array {
    return array_merge(['home', 'blog'], $extra);
}
PHP
        );

        $renderer = new BodyRenderer();
        $output = $renderer->render(['class' => ['my-class']]);

        $this->assertStringContainsString('home', $output);
        $this->assertStringContainsString('blog', $output);
        $this->assertStringContainsString('my-class', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpBodyOpenFalseDoesNotCallWpBodyOpen(): void
    {
        if (function_exists('wp_body_open')) {
            $this->markTestSkipped('wp_body_open exists in environment');
        }

        eval(<<<'PHP'
function wp_body_open() {
    $GLOBALS['_test_wp_body_open_called'] = true;
    echo '<!-- wp_body_open -->';
}
PHP
        );

        $renderer = new BodyRenderer();
        $output = $renderer->render(['include_wp_body_open' => false]);

        $this->assertArrayNotHasKey('_test_wp_body_open_called', $GLOBALS);
        $this->assertStringNotContainsString('<!-- wp_body_open -->', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testIncludeWpBodyOpenTrueCallsWpBodyOpen(): void
    {
        if (function_exists('wp_body_open')) {
            $this->markTestSkipped('wp_body_open exists in environment');
        }

        eval(<<<'PHP'
function wp_body_open() {
    echo '<!-- wp_body_open -->';
}
PHP
        );

        $renderer = new BodyRenderer();
        $output = $renderer->render(['include_wp_body_open' => true]);

        $this->assertStringContainsString('<!-- wp_body_open -->', $output);
    }

    public function testWpBodyOpenNotCalledWithoutWordPress(): void
    {
        if (function_exists('wp_body_open')) {
            $this->markTestSkipped('wp_body_open exists in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render(['include_wp_body_open' => true]);

        $this->assertStringContainsString('<body>', $output);
    }

    public function testCustomNewline(): void
    {
        if (function_exists('get_body_class') || function_exists('wp_body_open')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $renderer = new BodyRenderer();
        $output = $renderer->render(['newline' => "\r\n"]);

        $this->assertStringContainsString("<body>\r\n", $output);
    }
}
