<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\StringUtil;

final class StringUtilTest extends TestCase
{
    public function testQuoteWrapsValueWithQuoteCharacter(): void
    {
        $this->assertSame('"hello"', StringUtil::quote('hello'));
    }

    public function testQuoteEscapesEmbeddedQuoteCharacters(): void
    {
        $this->assertSame('"he\"llo"', StringUtil::quote('he"llo'));
    }

    public function testIndentPrependsIndentToEachLine(): void
    {
        $this->assertSame("\tline1\n\tline2", StringUtil::indent("line1\nline2"));
    }

    public function testWordsSplitsOnWhitespace(): void
    {
        $this->assertSame(['one', 'two', 'three'], StringUtil::words(" one\t two  three "));
    }

    public function testWordsReturnsEmptyArrayForEmptyString(): void
    {
        $this->assertSame([], StringUtil::words(''));
    }

    public function testToJsArrayReturnsJsonArrayString(): void
    {
        $this->assertSame('["a","b",1]', StringUtil::toJsArray(['a', 'b', 1]));
    }
}
