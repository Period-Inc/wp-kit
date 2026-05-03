<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\CookieJar;

final class CookieJarTest extends TestCase
{
    public function testSetGetAll(): void
    {
        $jar = new CookieJar();
        $jar->set('a', '1');
        $jar->set('b', '2');

        $this->assertSame('1', $jar->get('a'));
        $this->assertSame('2', $jar->get('b'));
        $this->assertSame(['a' => '1', 'b' => '2'], $jar->all());
    }

    public function testToHeaderReturnsCookieHeaderFormat(): void
    {
        $jar = new CookieJar();
        $jar->set('a', '1')->set('b', '2');

        $this->assertSame('a=1; b=2', $jar->toHeader());
    }

    public function testFromHeaderAcceptsSetCookieString(): void
    {
        $jar = new CookieJar();
        $jar->fromHeader('Set-Cookie: a=1; Path=/; HttpOnly');

        $this->assertSame('1', $jar->get('a'));
    }

    public function testFromHeaderAcceptsPlainCookieString(): void
    {
        $jar = new CookieJar();
        $jar->fromHeader('a=1; Path=/');

        $this->assertSame('1', $jar->get('a'));
    }

    public function testFromHeaderAcceptsMultipleSetCookieStrings(): void
    {
        $jar = new CookieJar();
        $jar->fromHeader("Set-Cookie: a=1; Path=/\nSet-Cookie: b=2; Secure");

        $this->assertSame('1', $jar->get('a'));
        $this->assertSame('2', $jar->get('b'));
    }
}
