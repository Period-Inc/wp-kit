<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetDeliveryResult;
use Period\WpFramework\WordPress\Access\AssetResponseEmitterInterface;
use Period\WpFramework\WordPress\Access\HttpAssetResponseEmitter;
use Period\WpFramework\WordPress\Access\HttpEmitterInterface;
use Period\WpFramework\WordPress\Access\MemoryHttpEmitter;
use Period\WpFramework\WordPress\Access\NativePhpHttpEmitter;

final class HttpAssetResponseEmitterTest extends TestCase
{
    private function make(): array
    {
        $http    = new MemoryHttpEmitter();
        $emitter = new HttpAssetResponseEmitter($http);
        return [$emitter, $http];
    }

    // --- interface ---

    public function testImplementsAssetResponseEmitterInterface(): void
    {
        [$emitter] = $this->make();
        $this->assertInstanceOf(AssetResponseEmitterInterface::class, $emitter);
    }

    // --- status emit ---

    public function testStatusCodeEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok(200));

        $this->assertSame(200, $http->emittedStatus());
    }

    public function testDenyStatusCodeEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::deny(403));

        $this->assertSame(403, $http->emittedStatus());
    }

    public function test404StatusCodeEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::deny(404));

        $this->assertSame(404, $http->emittedStatus());
    }

    // --- headers emit ---

    public function testHeadersEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok(200, [
            'Content-Type' => 'application/pdf',
            'X-Asset-Path' => '/uploads/file.pdf',
        ]));

        $this->assertSame('application/pdf', $http->emittedHeaders()['Content-Type']);
        $this->assertSame('/uploads/file.pdf', $http->emittedHeaders()['X-Asset-Path']);
    }

    public function testEmptyHeadersEmitNothing(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok());

        $this->assertSame([], $http->emittedHeaders());
    }

    // --- body emit ---

    public function testBodyEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok(200, [], 'file bytes'));

        $this->assertSame('file bytes', $http->emittedBody());
    }

    public function testNullBodyNotEmitted(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok(200, [], null));

        $this->assertNull($http->emittedBody());
    }

    // --- redirect priority ---

    public function testRedirectEmittedInsteadOfBody(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf', 302));

        $this->assertSame('https://cdn.example.com/file.pdf', $http->emittedRedirectUrl());
        $this->assertSame(302, $http->emittedRedirectStatus());
    }

    public function testBodyNotEmittedWhenRedirect(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf'));

        $this->assertNull($http->emittedBody());
    }

    public function testNonRedirectDoesNotCallRedirect(): void
    {
        [$emitter, $http] = $this->make();
        $emitter->emit(AssetDeliveryResult::ok(200, [], 'content'));

        $this->assertNull($http->emittedRedirectUrl());
    }

    // --- AssetEmitResult returned ---

    public function testEmitResultStatusCode(): void
    {
        [$emitter] = $this->make();
        $result = $emitter->emit(AssetDeliveryResult::deny(403));

        $this->assertSame(403, $result->statusCode());
    }

    public function testEmitResultEmittedIsTrue(): void
    {
        [$emitter] = $this->make();
        $result = $emitter->emit(AssetDeliveryResult::ok());

        $this->assertTrue($result->emitted());
    }

    public function testEmitResultRedirectUrl(): void
    {
        [$emitter] = $this->make();
        $result = $emitter->emit(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf'));

        $this->assertSame('https://cdn.example.com/file.pdf', $result->redirectUrl());
    }

    // --- no exit behavior ---

    public function testNoExitCalledOnDeny(): void
    {
        [$emitter] = $this->make();
        // If exit/die were called, this test would never complete
        $emitter->emit(AssetDeliveryResult::deny(403));
        $this->assertTrue(true);
    }

    public function testNoExitCalledOnRedirect(): void
    {
        [$emitter] = $this->make();
        $emitter->emit(AssetDeliveryResult::redirect('https://cdn.example.com/file.pdf'));
        $this->assertTrue(true);
    }

    // --- MemoryHttpEmitter ---

    public function testMemoryHttpEmitterImplementsInterface(): void
    {
        $this->assertInstanceOf(HttpEmitterInterface::class, new MemoryHttpEmitter());
    }

    public function testMemoryHttpEmitterRecordsStatus(): void
    {
        $http = new MemoryHttpEmitter();
        $http->status(418);

        $this->assertSame(418, $http->emittedStatus());
    }

    public function testMemoryHttpEmitterRecordsHeaders(): void
    {
        $http = new MemoryHttpEmitter();
        $http->header('X-Foo', 'bar');

        $this->assertSame('bar', $http->emittedHeaders()['X-Foo']);
    }

    public function testMemoryHttpEmitterRecordsBody(): void
    {
        $http = new MemoryHttpEmitter();
        $http->body('hello');

        $this->assertSame('hello', $http->emittedBody());
    }

    public function testMemoryHttpEmitterNullBodyStaysNull(): void
    {
        $http = new MemoryHttpEmitter();
        $http->body(null);

        $this->assertNull($http->emittedBody());
    }

    public function testMemoryHttpEmitterRecordsRedirect(): void
    {
        $http = new MemoryHttpEmitter();
        $http->redirect('https://example.com/file.pdf', 301);

        $this->assertSame('https://example.com/file.pdf', $http->emittedRedirectUrl());
        $this->assertSame(301, $http->emittedRedirectStatus());
    }

    // --- NativePhpHttpEmitter ---

    public function testNativePhpHttpEmitterImplementsInterface(): void
    {
        $this->assertInstanceOf(HttpEmitterInterface::class, new NativePhpHttpEmitter());
    }
}
