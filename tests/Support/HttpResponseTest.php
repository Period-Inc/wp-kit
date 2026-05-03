<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\HttpResponse;

final class HttpResponseTest extends TestCase
{
    public function testStatusHeadersBodyAccessors(): void
    {
        $response = new HttpResponse(201, ['Content-Type' => 'text/plain'], 'ok');

        $this->assertSame(201, $response->statusCode());
        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
        $this->assertSame('ok', $response->body());
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $response = new HttpResponse(200, ['Content-Type' => 'text/plain'], 'ok');

        $this->assertSame('text/plain', $response->header('content-type'));
        $this->assertSame('text/plain', $response->header('Content-Type'));
    }

    public function testUndefinedHeaderReturnsEmptyString(): void
    {
        $response = new HttpResponse(200, ['Content-Type' => 'text/plain'], 'ok');

        $this->assertSame('', $response->header('X-Unknown'));
    }

    public function testIsOkReturnsTrueFor200To299(): void
    {
        $this->assertTrue((new HttpResponse(200))->isOk());
        $this->assertTrue((new HttpResponse(250))->isOk());
        $this->assertFalse((new HttpResponse(199))->isOk());
        $this->assertFalse((new HttpResponse(300))->isOk());
    }
}
