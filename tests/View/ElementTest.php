<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\View;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\View\Element;

final class ElementTest extends TestCase
{
    public function testElGeneratesNormalTag(): void
    {
        $this->assertSame('<div class="box">Hello</div>', Element::el('div', ['class' => 'box'], 'Hello'));
    }

    public function testVoidGeneratesVoidTag(): void
    {
        $this->assertSame('<img src="/a.jpg">', Element::void('img', ['src' => '/a.jpg']));
    }

    public function testEmptyContentStillProducesClosingTag(): void
    {
        $this->assertSame('<span class="empty"></span>', Element::el('span', ['class' => 'empty'], ''));
    }

    public function testClassArrayIsCombined(): void
    {
        $this->assertSame('foo bar', call_user_func([Element::class, 'class'], ['foo', 'bar']));
    }

    public function testClassDuplicatesAreRemoved(): void
    {
        $this->assertSame('foo bar', call_user_func([Element::class, 'class'], ['foo', 'bar', 'foo', ['bar']]));
    }

    public function testBooleanTrueAttributeRendersNameOnly(): void
    {
        $this->assertSame('<input type="checkbox" checked>', Element::void('input', ['type' => 'checkbox', 'checked' => true]));
    }

    public function testBooleanFalseAttributeIsNotRendered(): void
    {
        $this->assertSame('<input type="checkbox">', Element::void('input', ['type' => 'checkbox', 'checked' => false]));
    }

    public function testNullAndEmptyStringAttributesAreNotRendered(): void
    {
        $this->assertSame('<div></div>', Element::el('div', ['id' => null, 'class' => '']));
    }

    public function testDataAttributeArrayIsJsonEncoded(): void
    {
        $output = Element::el('div', ['data-info' => ['a' => 1, 'b' => 'c']], 'x');

        $this->assertSame('<div data-info="{&quot;a&quot;:1,&quot;b&quot;:&quot;c&quot;}">x</div>', $output);
    }

    public function testInvalidAttributeNameIsIgnored(): void
    {
        $this->assertSame('<div>x</div>', Element::el('div', ['in valid' => 'test'], 'x'));
    }

    public function testAttributeValueIsEscaped(): void
    {
        $this->assertSame('<div title="&lt;script&gt;">x</div>', Element::el('div', ['title' => '<script>'], 'x'));
    }

    public function testContentIsEscaped(): void
    {
        $this->assertSame('<div>&lt;strong&gt;</div>', Element::el('div', [], '<strong>'));
    }

    public function testCommentReturnsRawHtml(): void
    {
        $result = Element::comment('debug');

        $this->assertInstanceOf(\Period\WpFramework\View\RawHtml::class, $result);
        $this->assertSame('<!-- debug -->', $result->render());
    }

    public function testCdataReturnsRawHtml(): void
    {
        $result = Element::cdata('var a = 1;');

        $this->assertInstanceOf(\Period\WpFramework\View\RawHtml::class, $result);
        $this->assertSame('<![CDATA[var a = 1;]]>', $result->render());
    }

    public function testElIfNotEmptyReturnsEmptyStringForEmptyContent(): void
    {
        $this->assertSame('', Element::elIfNotEmpty('div', [], ''));
    }

    public function testElIfNotEmptyReturnsEmptyStringForWhitespaceContent(): void
    {
        $this->assertSame('', Element::elIfNotEmpty('div', [], '   '));
    }

    public function testElIfNotEmptyReturnsTagForNonEmptyContent(): void
    {
        $this->assertSame('<div>Hello</div>', Element::elIfNotEmpty('div', [], 'Hello'));
    }
}
