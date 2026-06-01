<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\View;

use PHPUnit\Framework\TestCase;
use Period\WpKit\View\Element;

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

        $this->assertInstanceOf(\Period\WpKit\View\RawHtml::class, $result);
        $this->assertSame('<!-- debug -->', $result->render());
    }

    public function testCdataReturnsRawHtml(): void
    {
        $result = Element::cdata('var a = 1;');

        $this->assertInstanceOf(\Period\WpKit\View\RawHtml::class, $result);
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

    public function testNormalElementShorthandReturnsString(): void
    {
        $this->assertSame('<p class="lead">Hello</p>', Element::p(['class' => 'lead'], 'Hello'));
    }

    public function testSectionShorthand(): void
    {
        $this->assertSame('<section id="main"></section>', Element::section(['id' => 'main']));
    }

    public function testH1Shorthand(): void
    {
        $this->assertSame('<h1>Title</h1>', Element::h1([], 'Title'));
    }

    public function testTableShorthand(): void
    {
        $row = Element::tr([], Element::raw(Element::td([], 'Cell')));
        $this->assertSame('<tr><td>Cell</td></tr>', $row);
    }

    public function testScriptShorthand(): void
    {
        $this->assertSame('<script type="text/javascript"></script>', Element::script(['type' => 'text/javascript']));
    }

    public function testStyleShorthand(): void
    {
        $this->assertSame('<style>body{}</style>', Element::style([], Element::raw('body{}')));
    }

    public function testObjectTagShorthand(): void
    {
        $this->assertSame('<object data="/file.pdf"></object>', Element::objectTag(['data' => '/file.pdf']));
    }

    public function testVarTagShorthand(): void
    {
        $this->assertSame('<var>x</var>', Element::varTag([], 'x'));
    }

    public function testVoidShorthandInputReturnsStringWithNoClosingTag(): void
    {
        $this->assertSame('<input type="text" name="q">', Element::input(['type' => 'text', 'name' => 'q']));
    }

    public function testVoidShorthandMetaReturnsStringWithNoClosingTag(): void
    {
        $this->assertSame('<meta charset="UTF-8">', Element::meta(['charset' => 'UTF-8']));
    }

    public function testVoidShorthandLinkReturnsStringWithNoClosingTag(): void
    {
        $this->assertSame('<link rel="stylesheet" href="/app.css">', Element::link(['rel' => 'stylesheet', 'href' => '/app.css']));
    }

    public function testVoidShorthandHrReturnsStringWithNoClosingTag(): void
    {
        $this->assertSame('<hr>', Element::hr());
    }

    public function testVoidShorthandSourceReturnsStringWithNoClosingTag(): void
    {
        $this->assertSame('<source src="/v.mp4" type="video/mp4">', Element::source(['src' => '/v.mp4', 'type' => 'video/mp4']));
    }

    public function testElAcceptsArrayContent(): void
    {
        $result = Element::ul([], [
            Element::li([], 'A'),
            Element::li([], 'B'),
        ]);

        $this->assertSame('<ul><li>A</li><li>B</li></ul>', $result);
    }

    public function testElArrayContentDeepNest(): void
    {
        $result = Element::nav(['aria-label' => 'main'], [
            Element::ul([], [
                Element::li([], [
                    Element::el('a', ['href' => '/'], 'Home'),
                ]),
            ]),
        ]);

        $this->assertSame('<nav aria-label="main"><ul><li><a href="/">Home</a></li></ul></nav>', $result);
    }

    public function testElArrayContentMixedTypes(): void
    {
        $result = Element::p([], [
            'Before ',
            new \Period\WpKit\View\RawHtml('<strong>bold</strong>'),
            ' after',
        ]);

        $this->assertSame('<p>Before <strong>bold</strong> after</p>', $result);
    }

    public function testElArrayContentEmpty(): void
    {
        $this->assertSame('<div></div>', Element::el('div', [], []));
    }
}
