<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Infrastructure\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Infrastructure\WordPress\ImageTagRenderer;

final class ImageTagRendererTest extends TestCase
{
    public function testRenderReturnsEmptyWithoutWordPressFunctions(): void
    {
        if (function_exists('wp_get_attachment_image_src')) {
            $this->markTestSkipped('wp_get_attachment_image_src exists in environment');
        }

        $renderer = new ImageTagRenderer();

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
    return 'alt text';
}
PHP
        );

        $renderer = new ImageTagRenderer();
        $output = $renderer->render(1);

        $this->assertStringContainsString('<img src="http://example.com/image.jpg"', $output);
        $this->assertStringContainsString('alt="alt text"', $output);
    }

    public function testImageRendererIsAliasOfImageTagRenderer(): void
    {
        $imageRenderer = new \Period\WpFramework\Infrastructure\WordPress\ImageRenderer();

        $this->assertInstanceOf(ImageTagRenderer::class, $imageRenderer);
    }
}
