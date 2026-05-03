<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\Url;

final class UrlTest extends TestCase
{
    public function testCurrentBuildsFromServerArray(): void
    {
        $server = [
            'HTTPS' => 'off',
            'SERVER_PORT' => '80',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path?x=1',
        ];

        $this->assertSame('http://example.com/path?x=1', Url::current($server));
    }

    public function testCurrentDetectsHttpsFromServer(): void
    {
        $server = [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path',
        ];

        $this->assertSame('https://example.com/path', Url::current($server));

        $server = [
            'SERVER_PORT' => '443',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path',
        ];

        $this->assertSame('https://example.com/path', Url::current($server));

        $server = [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_HOST' => 'example.com',
            'REQUEST_URI' => '/path',
        ];

        $this->assertSame('https://example.com/path', Url::current($server));
    }

    public function testRootReturnsSchemeAndHost(): void
    {
        $this->assertSame('https://example.com', Url::root('https://example.com/path')); 
    }

    public function testRootPreservesPort(): void
    {
        $this->assertSame('https://example.com:8443', Url::root('https://example.com:8443/path'));
    }

    public function testJoinReturnsAbsoluteUrlAsIs(): void
    {
        $this->assertSame('https://example.com/absolute', Url::join('https://example.com', 'https://example.com/absolute'));
    }

    public function testJoinPrefillsSchemeForProtocolRelativeUrl(): void
    {
        $this->assertSame('https://example.com/path', Url::join('https://example.com/base', '//example.com/path'));
    }

    public function testJoinResolvesRootRelativePath(): void
    {
        $this->assertSame('https://example.com/rooted', Url::join('https://example.com/base/page', '/rooted'));
    }

    public function testJoinNormalizesRelativePath(): void
    {
        $this->assertSame('https://example.com/base/dir/file', Url::join('https://example.com/base/page', '../dir/file'));
    }

    public function testJoinPreservesQueryAndFragment(): void
    {
        $this->assertSame('https://example.com/base/dir/file?x=1#frag', Url::join('https://example.com/base/page', '../dir/file?x=1#frag'));
    }

    public function testRelativeReturnsPathQueryAndFragment(): void
    {
        $this->assertSame('/path?x=1#frag', Url::relative('https://example.com/path?x=1#frag'));
    }

    public function testToPathCombinesDocumentRootAndUrlPath(): void
    {
        $this->assertSame('/var/www/html/path', Url::toPath('https://example.com/path', '/var/www/html'));
    }
}
