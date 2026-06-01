<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetRequestContext;

final class AssetRequestContextTest extends TestCase
{
    private AssetRequestContext $context;
    private DateTimeImmutable $time;

    protected function setUp(): void
    {
        $this->time = new DateTimeImmutable('2026-01-01 00:00:00');
        $this->context = new AssetRequestContext(
            assetPath: '/var/www/wp-content/uploads/file.pdf',
            assetUrl: 'https://example.com/wp-content/uploads/file.pdf',
            currentUserId: 42,
            currentUserRoles: ['editor', 'subscriber'],
            requestTime: $this->time,
            metadata: ['source' => 'direct'],
        );
    }

    public function testAssetPath(): void
    {
        $this->assertSame('/var/www/wp-content/uploads/file.pdf', $this->context->assetPath());
    }

    public function testAssetUrl(): void
    {
        $this->assertSame('https://example.com/wp-content/uploads/file.pdf', $this->context->assetUrl());
    }

    public function testCurrentUserId(): void
    {
        $this->assertSame(42, $this->context->currentUserId());
    }

    public function testCurrentUserRoles(): void
    {
        $this->assertSame(['editor', 'subscriber'], $this->context->currentUserRoles());
    }

    public function testRequestTime(): void
    {
        $this->assertSame($this->time, $this->context->requestTime());
    }

    public function testMetadata(): void
    {
        $this->assertSame(['source' => 'direct'], $this->context->metadata());
    }

    public function testDefaultMetadataIsEmpty(): void
    {
        $context = new AssetRequestContext(
            assetPath: '/path/file.jpg',
            assetUrl: 'https://example.com/file.jpg',
            currentUserId: 0,
            currentUserRoles: [],
            requestTime: new DateTimeImmutable(),
        );

        $this->assertSame([], $context->metadata());
    }

    public function testWithMetadataReturnsNewInstance(): void
    {
        $updated = $this->context->withMetadata(['foo' => 'bar']);

        $this->assertNotSame($this->context, $updated);
    }

    public function testWithMetadataDoesNotMutateOriginal(): void
    {
        $this->context->withMetadata(['foo' => 'bar']);

        $this->assertSame(['source' => 'direct'], $this->context->metadata());
    }

    public function testWithMetadataUpdatesMetadata(): void
    {
        $updated = $this->context->withMetadata(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $updated->metadata());
    }

    public function testWithMetadataPreservesOtherFields(): void
    {
        $updated = $this->context->withMetadata(['x' => 1]);

        $this->assertSame($this->context->assetPath(), $updated->assetPath());
        $this->assertSame($this->context->currentUserId(), $updated->currentUserId());
        $this->assertSame($this->context->currentUserRoles(), $updated->currentUserRoles());
    }

    public function testNoWordPressDependency(): void
    {
        // AssetRequestContext must be instantiable without WordPress runtime.
        // If this test passes in the test environment (no WordPress loaded),
        // the class has no hidden WordPress dependency.
        $context = new AssetRequestContext(
            assetPath: '/uploads/test.png',
            assetUrl: 'https://example.com/uploads/test.png',
            currentUserId: 1,
            currentUserRoles: ['administrator'],
            requestTime: new DateTimeImmutable(),
        );

        $this->assertInstanceOf(AssetRequestContext::class, $context);
    }
}
