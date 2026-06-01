<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\HtmlTemplate;

final class HtmlTemplateTest extends TestCase
{
    public function testHtmlEscapesByDefault(): void
    {
        $template = new HtmlTemplate('<p>{{ key }}</p>');
        $this->assertSame('<p>&lt;script&gt;</p>', $template->render(['key' => '<script>']));
    }

    public function testAttrEscapesAttributes(): void
    {
        $template = new HtmlTemplate('<a href="{{ attr:url }}"></a>');
        $this->assertSame('<a href="&lt;script&gt;"></a>', $template->render(['url' => '<script>']));
    }

    public function testUrlEscapesUrlValue(): void
    {
        $template = new HtmlTemplate('<a href="{{ url:url }}"></a>');
        $this->assertSame('<a href="&lt;script&gt;"></a>', $template->render(['url' => '<script>']));
    }

    public function testHtmlFallsBackToStripTags(): void
    {
        $template = new HtmlTemplate('<div>{{ html:content }}</div>');
        $this->assertSame('<div>text</div>', $template->render(['content' => '<strong>text</strong>']));
    }

    public function testRawOutputsWithoutEscaping(): void
    {
        $template = new HtmlTemplate('<div>{{{ content }}}</div>');
        $this->assertSame('<div><strong>text</strong></div>', $template->render(['content' => '<strong>text</strong>']));
    }

    public function testUndefinedKeyReturnsEmptyString(): void
    {
        $template = new HtmlTemplate('<div>{{ missing }}</div>');
        $this->assertSame('<div></div>', $template->render([]));
    }

    public function testDotNotationResolvesNestedArrays(): void
    {
        $template = new HtmlTemplate('<div>{{ user.name }}</div>');
        $this->assertSame('<div>Alice</div>', $template->render(['user' => ['name' => 'Alice']]));
    }

    public function testNonScalarValuesReturnEmptyString(): void
    {
        $template = new HtmlTemplate('<div>{{ item }}</div>');
        $this->assertSame('<div></div>', $template->render(['item' => ['value' => 1]]));
    }
}
