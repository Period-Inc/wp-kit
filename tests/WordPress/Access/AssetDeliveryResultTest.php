<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetDeliveryResult;

final class AssetDeliveryResultTest extends TestCase
{
    // --- ok ---

    public function testOkIsSuccess(): void
    {
        $this->assertTrue(AssetDeliveryResult::ok()->success());
    }

    public function testOkDefaultStatusCode(): void
    {
        $this->assertSame(200, AssetDeliveryResult::ok()->statusCode());
    }

    public function testOkCustomStatusCode(): void
    {
        $this->assertSame(206, AssetDeliveryResult::ok(206)->statusCode());
    }

    public function testOkDefaultHeadersAreEmpty(): void
    {
        $this->assertSame([], AssetDeliveryResult::ok()->headers());
    }

    public function testOkStoresHeaders(): void
    {
        $result = AssetDeliveryResult::ok(200, ['Content-Type' => 'application/pdf']);

        $this->assertSame(['Content-Type' => 'application/pdf'], $result->headers());
    }

    public function testOkDefaultBodyIsNull(): void
    {
        $this->assertNull(AssetDeliveryResult::ok()->body());
    }

    public function testOkStoresBody(): void
    {
        $result = AssetDeliveryResult::ok(200, [], 'content');

        $this->assertSame('content', $result->body());
    }

    public function testOkRedirectUrlIsNull(): void
    {
        $this->assertNull(AssetDeliveryResult::ok()->redirectUrl());
    }

    // --- deny ---

    public function testDenyIsNotSuccess(): void
    {
        $this->assertFalse(AssetDeliveryResult::deny()->success());
    }

    public function testDenyDefaultStatusCode(): void
    {
        $this->assertSame(403, AssetDeliveryResult::deny()->statusCode());
    }

    public function testDenyCustomStatusCode(): void
    {
        $this->assertSame(401, AssetDeliveryResult::deny(401)->statusCode());
    }

    public function testDenyDefaultBodyIsNull(): void
    {
        $this->assertNull(AssetDeliveryResult::deny()->body());
    }

    public function testDenyStoresBody(): void
    {
        $result = AssetDeliveryResult::deny(403, 'Forbidden');

        $this->assertSame('Forbidden', $result->body());
    }

    public function testDenyDefaultHeadersAreEmpty(): void
    {
        $this->assertSame([], AssetDeliveryResult::deny()->headers());
    }

    public function testDenyRedirectUrlIsNull(): void
    {
        $this->assertNull(AssetDeliveryResult::deny()->redirectUrl());
    }

    // --- redirect ---

    public function testRedirectIsSuccess(): void
    {
        $this->assertTrue(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf')->success());
    }

    public function testRedirectDefaultStatusCode(): void
    {
        $this->assertSame(302, AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf')->statusCode());
    }

    public function testRedirectCustomStatusCode(): void
    {
        $result = AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf', 301);

        $this->assertSame(301, $result->statusCode());
    }

    public function testRedirectStoresUrl(): void
    {
        $url = 'https://cdn.example.com/file.pdf';
        $result = AssetDeliveryResult::redirect($url);

        $this->assertSame($url, $result->redirectUrl());
    }

    public function testRedirectBodyIsNull(): void
    {
        $this->assertNull(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf')->body());
    }

    public function testRedirectStoresHeaders(): void
    {
        $result = AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf', 302, ['Cache-Control' => 'no-store']);

        $this->assertSame(['Cache-Control' => 'no-store'], $result->headers());
    }
}
