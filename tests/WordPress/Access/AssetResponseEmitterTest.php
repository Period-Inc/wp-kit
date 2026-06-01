<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetDeliveryResult;
use Period\WpKit\WordPress\Access\AssetEmitResult;
use Period\WpKit\WordPress\Access\AssetResponseEmitterInterface;
use Period\WpKit\WordPress\Access\MemoryAssetResponseEmitter;

final class AssetResponseEmitterTest extends TestCase
{
    private MemoryAssetResponseEmitter $emitter;

    protected function setUp(): void
    {
        $this->emitter = new MemoryAssetResponseEmitter();
    }

    // --- interface ---

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetResponseEmitterInterface::class, $this->emitter);
    }

    // --- status emit ---

    public function testEmit200StatusCode(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::ok(200));

        $this->assertSame(200, $result->statusCode());
    }

    public function testEmit403StatusCode(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::deny(403));

        $this->assertSame(403, $result->statusCode());
    }

    public function testEmit404StatusCode(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::deny(404));

        $this->assertSame(404, $result->statusCode());
    }

    public function testEmit501StatusCode(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::deny(501, 'Not implemented'));

        $this->assertSame(501, $result->statusCode());
    }

    public function testEmittedIsTrue(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::ok());

        $this->assertTrue($result->emitted());
    }

    // --- headers emit ---

    public function testEmptyHeadersPreserved(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::ok());

        $this->assertSame([], $result->headers());
    }

    public function testHeadersPassedThrough(): void
    {
        $delivery = AssetDeliveryResult::ok(200, [
            'Content-Type'  => 'application/pdf',
            'X-Asset-Path'  => '/uploads/file.pdf',
        ]);

        $result = $this->emitter->emit($delivery);

        $this->assertSame('application/pdf', $result->headers()['Content-Type']);
        $this->assertSame('/uploads/file.pdf', $result->headers()['X-Asset-Path']);
    }

    public function testDenyHeadersPassedThrough(): void
    {
        $delivery = AssetDeliveryResult::deny(403, null, ['WWW-Authenticate' => 'Bearer']);

        $result = $this->emitter->emit($delivery);

        $this->assertSame('Bearer', $result->headers()['WWW-Authenticate']);
    }

    // --- body emit ---

    public function testNullBodyPreserved(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::ok());

        $this->assertNull($result->body());
    }

    public function testBodyPassedThrough(): void
    {
        $delivery = AssetDeliveryResult::ok(200, [], 'file content here');

        $result = $this->emitter->emit($delivery);

        $this->assertSame('file content here', $result->body());
    }

    public function testDenyBodyPassedThrough(): void
    {
        $delivery = AssetDeliveryResult::deny(403, 'Access denied');

        $result = $this->emitter->emit($delivery);

        $this->assertSame('Access denied', $result->body());
    }

    // --- redirect emit ---

    public function testRedirectUrlPassedThrough(): void
    {
        $url      = 'https://cdn.example.com/signed/file.pdf?token=abc';
        $delivery = AssetDeliveryResult::redirect($url, 302);

        $result = $this->emitter->emit($delivery);

        $this->assertSame($url, $result->redirectUrl());
        $this->assertSame(302, $result->statusCode());
    }

    public function testNullRedirectUrlPreserved(): void
    {
        $result = $this->emitter->emit(AssetDeliveryResult::ok());

        $this->assertNull($result->redirectUrl());
    }

    // --- AssetEmitResult value object ---

    public function testEmitResultStoresAllFields(): void
    {
        $emitResult = new AssetEmitResult(
            emitted: true,
            statusCode: 206,
            headers: ['Content-Range' => 'bytes 0-1023/2048'],
            body: 'partial content',
            redirectUrl: null,
        );

        $this->assertTrue($emitResult->emitted());
        $this->assertSame(206, $emitResult->statusCode());
        $this->assertSame(['Content-Range' => 'bytes 0-1023/2048'], $emitResult->headers());
        $this->assertSame('partial content', $emitResult->body());
        $this->assertNull($emitResult->redirectUrl());
    }
}
