<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\HookRegistrar;

final class HookRegistrarTest extends TestCase
{
    public function testDoesNotThrowWithoutWordPressFunctions(): void
    {
        if (function_exists('add_action') || function_exists('add_filter') || function_exists('add_shortcode')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $hooks = new HookRegistrar();
        $hooks->action('init', fn() => null)
              ->filter('the_content', fn($c) => $c)
              ->shortcode('test', fn() => '');

        $this->assertTrue(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testActionRegistersCallback(): void
    {
        if (function_exists('add_action')) {
            $this->markTestSkipped('add_action exists in environment');
        }

        eval(<<<'PHP'
function add_action(string $hook, callable $cb, int $priority = 10, int $args = 1): void {
    $GLOBALS['_test_actions'][] = $hook;
}
PHP
        );

        $hooks = new HookRegistrar();
        $hooks->action('init', fn() => null);

        $this->assertContains('init', $GLOBALS['_test_actions'] ?? []);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFilterRegistersCallback(): void
    {
        if (function_exists('add_filter')) {
            $this->markTestSkipped('add_filter exists in environment');
        }

        eval(<<<'PHP'
function add_filter(string $hook, callable $cb, int $priority = 10, int $args = 1): void {
    $GLOBALS['_test_filters'][] = $hook;
}
PHP
        );

        $hooks = new HookRegistrar();
        $hooks->filter('the_title', fn($t) => $t);

        $this->assertContains('the_title', $GLOBALS['_test_filters'] ?? []);
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortcodeRegistersTag(): void
    {
        if (function_exists('add_shortcode')) {
            $this->markTestSkipped('add_shortcode exists in environment');
        }

        eval(<<<'PHP'
function add_shortcode(string $tag, callable $cb): void {
    $GLOBALS['_test_shortcodes'][] = $tag;
}
PHP
        );

        $hooks = new HookRegistrar();
        $hooks->shortcode('my_tag', fn() => '');

        $this->assertContains('my_tag', $GLOBALS['_test_shortcodes'] ?? []);
    }

    public function testMethodsReturnSelfForChaining(): void
    {
        if (function_exists('add_action') || function_exists('add_filter') || function_exists('add_shortcode')) {
            $this->markTestSkipped('WordPress functions exist in environment');
        }

        $hooks = new HookRegistrar();
        $result = $hooks->action('a', fn() => null)
                        ->filter('b', fn($v) => $v)
                        ->shortcode('c', fn() => '');

        $this->assertSame($hooks, $result);
    }
}
