<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Translator;

final class TranslatorTest extends TestCase
{
    public function testTextReturnsStringAsIsWhenWordPressAbsent(): void
    {
        $translator = new Translator('test-domain');

        $this->assertSame('Hello', $translator->text('Hello'));
    }

    public function testTextPreservesSpecialCharactersWhenWordPressAbsent(): void
    {
        $translator = new Translator('test-domain');

        $this->assertSame('Select image', $translator->text('Select image'));
    }

    public function testHtmlEscapesHtmlEntities(): void
    {
        $translator = new Translator('test-domain');
        $result = $translator->html('<script>alert("xss")</script>');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    public function testHtmlEscapesQuotes(): void
    {
        $translator = new Translator('test-domain');
        $result = $translator->html('"quoted" & \'single\'');

        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringContainsString('&amp;', $result);
    }

    public function testAttrEscapesDoubleQuotes(): void
    {
        $translator = new Translator('test-domain');
        $result = $translator->attr('"value"');

        $this->assertStringNotContainsString('"value"', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testAttrEscapesAngleBrackets(): void
    {
        $translator = new Translator('test-domain');
        $result = $translator->attr('<tag>');

        $this->assertStringNotContainsString('<tag>', $result);
        $this->assertStringContainsString('&lt;', $result);
    }

    public function testPluralReturnsSingleWhenNumberIsOne(): void
    {
        $translator = new Translator('test-domain');

        $this->assertSame('item', $translator->plural('item', 'items', 1));
    }

    public function testPluralReturnsPluralWhenNumberIsZero(): void
    {
        $translator = new Translator('test-domain');

        $this->assertSame('items', $translator->plural('item', 'items', 0));
    }

    public function testPluralReturnsPluralWhenNumberIsGreaterThanOne(): void
    {
        $translator = new Translator('test-domain');

        $this->assertSame('items', $translator->plural('item', 'items', 5));
    }

    public function testDomainReturnsConfiguredDomain(): void
    {
        $translator = new Translator('my-plugin');

        $this->assertSame('my-plugin', $translator->domain());
    }

    public function testDefaultDomainIsPeriodWpKit(): void
    {
        $translator = new Translator();

        $this->assertSame('period-wp-kit', $translator->domain());
    }
}
