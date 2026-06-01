<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\HtmlDocument;

final class HtmlDocumentTest extends TestCase
{
    public function testFromStringParsesHtml(): void
    {
        $doc = HtmlDocument::fromString('<html><body><div class="test">Hello</div></body></html>');

        $this->assertSame(['Hello'], $doc->filter('.test'));
    }

    public function testFirstTextReturnsFirstMatchedText(): void
    {
        $doc = HtmlDocument::fromString('<html><body><p>One</p><p>Two</p></body></html>');

        $this->assertSame('One', $doc->firstText('p'));
    }

    public function testFilterReturnsMultipleTexts(): void
    {
        $doc = HtmlDocument::fromString('<html><body><p>One</p><p>Two</p></body></html>');

        $this->assertSame(['One', 'Two'], $doc->filter('p'));
    }

    public function testFirstAttrReturnsAttributeValue(): void
    {
        $doc = HtmlDocument::fromString('<html><body><a href="https://example.com">Link</a></body></html>');

        $this->assertSame('https://example.com', $doc->firstAttr('a', 'href'));
    }

    public function testHtmlReturnsInnerHtml(): void
    {
        $doc = HtmlDocument::fromString('<html><body><div class="x"><span>Text</span></div></body></html>');

        $this->assertSame('<span>Text</span>', $doc->html('.x'));
    }

    public function testMissingSelectorsReturnEmptyValues(): void
    {
        $doc = HtmlDocument::fromString('<html><body></body></html>');

        $this->assertSame([], $doc->filter('p'));
        $this->assertSame('', $doc->firstText('p'));
        $this->assertSame('', $doc->firstAttr('p', 'href'));
        $this->assertSame('', $doc->html('p'));
    }

    public function testBrokenHtmlDoesNotThrow(): void
    {
        $doc = HtmlDocument::fromString('<html><body><p>Unclosed');

        $this->assertSame('Unclosed', $doc->firstText('p'));
    }
}
