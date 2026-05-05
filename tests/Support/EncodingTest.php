<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\Encoding;

final class EncodingTest extends TestCase
{
    public function testBase64UrlEncodeAndDecodeRoundtrip(): void
    {
        $value = 'hello+world/=';

        $encoded = Encoding::base64UrlEncode($value);
        $decoded = Encoding::base64UrlDecode($encoded);

        $this->assertSame($value, $decoded);
    }

    public function testDecodeHtmlEntitiesRestoresEntities(): void
    {
        $this->assertSame('<>&"', Encoding::decodeHtmlEntities('&lt;&gt;&amp;&quot;'));
    }

    public function testCharToHexDefaultPrefix(): void
    {
        $this->assertSame('\x41', Encoding::charToHex('A'));
    }

    public function testCharToHexCustomPrefix(): void
    {
        $this->assertSame('%41', Encoding::charToHex('A', '%'));
    }

    public function testCharToHexAlreadyInHexFormatReturnsAsIs(): void
    {
        $this->assertSame('\x41', Encoding::charToHex('\x41'));
    }

    public function testCodepointToUtf8Ascii(): void
    {
        $this->assertSame('A', Encoding::codepointToUtf8(65));
    }

    public function testCodepointToUtf8NegativeReturnsEmpty(): void
    {
        $this->assertSame('', Encoding::codepointToUtf8(-1));
    }

    public function testCodepointToUtf8TwoByteRange(): void
    {
        // U+00E9 = é
        $this->assertSame('é', Encoding::codepointToUtf8(0xE9));
    }

    public function testCodepointToUtf8ThreeByteRange(): void
    {
        // U+3042 = あ
        $this->assertSame('あ', Encoding::codepointToUtf8(0x3042));
    }

    public function testCodepointToUtf8FourByteRange(): void
    {
        // U+1F600 = 😀
        $this->assertSame('😀', Encoding::codepointToUtf8(0x1F600));
    }

    public function testCodepointToUtf8MatchesMbChrWhenAvailable(): void
    {
        if (!function_exists('mb_chr')) {
            $this->markTestSkipped('mb_chr not available');
        }

        $this->assertSame(mb_chr(0x3042, 'UTF-8'), Encoding::codepointToUtf8(0x3042));
        $this->assertSame(mb_chr(0x1F600, 'UTF-8'), Encoding::codepointToUtf8(0x1F600));
    }
}
