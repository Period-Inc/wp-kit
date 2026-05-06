<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\ImageRenderer;

final class ImageRendererTest extends TestCase
{
    public function testRenderReturnsEmptyWithoutWordPressFunctions(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        $renderer = new ImageRenderer();

        $this->assertSame('', $renderer->render(123));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderReturnsEmptyWhenAttachmentNotFound(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        eval(<<<'PHP'
function wp_get_attachment_image_src($attachmentId, $size) {
    return false;
}
PHP
        );

        $renderer = new ImageRenderer();

        $this->assertSame('', $renderer->render(123));
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderGeneratesImageHtml(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        eval(<<<'PHP'
function wp_get_attachment_image_src($attachmentId, $size) {
    return ['http://example.com/image.jpg', 800, 600];
}

function get_post_meta($postId, $key, $single) {
    return 'attachment alt';
}
PHP
        );

        $renderer = new ImageRenderer();
        $output = $renderer->render(123);

        $this->assertStringContainsString('<img src="http://example.com/image.jpg" width="800" height="600" alt="attachment alt" loading="lazy">', $output);
        $this->assertStringContainsString('<div class="image image--landscape">', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderRespectsLazyArgument(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        eval(<<<'PHP'
function wp_get_attachment_image_src($attachmentId, $size) {
    return ['http://example.com/image.jpg', 800, 600];
}

function get_post_meta($postId, $key, $single) {
    return '';
}
PHP
        );

        $renderer = new ImageRenderer();
        $output = $renderer->render(123, ['lazy' => false]);

        $this->assertStringContainsString('<img src="http://example.com/image.jpg" width="800" height="600" alt=""', $output);
        $this->assertStringNotContainsString('loading="lazy"', $output);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderWithoutWrapperReturnsPlainImg(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        eval(<<<'PHP'
function wp_get_attachment_image_src($attachmentId, $size) {
    return ['http://example.com/image.jpg', 800, 600];
}

function get_post_meta($postId, $key, $single) {
    return '';
}
PHP
        );

        $renderer = new ImageRenderer();
        $output = $renderer->render(123, ['wrapper' => false]);

        $this->assertSame('<img src="http://example.com/image.jpg" width="800" height="600" alt="" loading="lazy">', $output);
    }
}
