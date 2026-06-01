<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetStorageInterface;
use Period\WpKit\WordPress\Access\AssetStorageItem;
use Period\WpKit\WordPress\Access\AssetStorageItemFactory;
use Period\WpKit\WordPress\Access\AttachmentAssetStorage;

final class AttachmentAssetStorageTest extends TestCase
{
    // --- AttachmentAssetStorage ---

    public function testImplementsInterface(): void
    {
        $storage = new AttachmentAssetStorage(fn() => null);

        $this->assertInstanceOf(AssetStorageInterface::class, $storage);
    }

    public function testFindReturnsNullWhenResolverReturnsNull(): void
    {
        $storage = new AttachmentAssetStorage(fn(string $path) => null);

        $this->assertNull($storage->find('/uploads/missing.pdf'));
    }

    public function testFindPassesPathToResolver(): void
    {
        $received = null;
        $storage = new AttachmentAssetStorage(function (string $path) use (&$received): ?array {
            $received = $path;
            return null;
        });

        $storage->find('/uploads/file.pdf');

        $this->assertSame('/uploads/file.pdf', $received);
    }

    public function testFindReturnsItemFromResolverData(): void
    {
        $storage = new AttachmentAssetStorage(fn() => [
            'path'     => '/uploads/file.pdf',
            'url'      => 'https://example.com/uploads/file.pdf',
            'mimeType' => 'application/pdf',
            'size'     => 102400,
        ]);

        $item = $storage->find('/uploads/file.pdf');

        $this->assertInstanceOf(AssetStorageItem::class, $item);
        $this->assertSame('/uploads/file.pdf', $item->path());
        $this->assertSame('https://example.com/uploads/file.pdf', $item->url());
        $this->assertSame('application/pdf', $item->mimeType());
        $this->assertSame(102400, $item->size());
    }

    public function testFindPassesThroughMetadata(): void
    {
        $storage = new AttachmentAssetStorage(fn() => [
            'path'     => '/uploads/file.pdf',
            'metadata' => ['attachmentId' => 42, 'protected' => true],
        ]);

        $item = $storage->find('/uploads/file.pdf');

        $this->assertSame(['attachmentId' => 42, 'protected' => true], $item->metadata());
    }

    public function testFindWithMinimalDataReturnsItem(): void
    {
        $storage = new AttachmentAssetStorage(fn() => ['path' => '/uploads/file.pdf']);

        $item = $storage->find('/uploads/file.pdf');

        $this->assertNotNull($item);
        $this->assertSame('/uploads/file.pdf', $item->path());
        $this->assertNull($item->url());
        $this->assertNull($item->mimeType());
        $this->assertNull($item->size());
        $this->assertNull($item->lastModified());
    }

    // --- AssetStorageItemFactory ---

    public function testFactoryRequiresPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AssetStorageItemFactory())->fromArray(['url' => 'https://example.com/file.pdf']);
    }

    public function testFactoryRejectsEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AssetStorageItemFactory())->fromArray(['path' => '']);
    }

    public function testFactoryNormalizesDateTimeImmutableAsIs(): void
    {
        $time = new DateTimeImmutable('2026-03-15 09:00:00');
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'         => '/uploads/file.pdf',
            'lastModified' => $time,
        ]);

        $this->assertSame($time, $item->lastModified());
    }

    public function testFactoryNormalizesDateTimeString(): void
    {
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'         => '/uploads/file.pdf',
            'lastModified' => '2026-03-15 09:00:00',
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $item->lastModified());
        $this->assertSame('2026-03-15 09:00:00', $item->lastModified()->format('Y-m-d H:i:s'));
    }

    public function testFactoryNormalizesUnixTimestamp(): void
    {
        $timestamp = mktime(9, 0, 0, 3, 15, 2026);
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'         => '/uploads/file.pdf',
            'lastModified' => $timestamp,
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $item->lastModified());
        $this->assertSame((string) $timestamp, $item->lastModified()->format('U'));
    }

    public function testFactoryNormalizesMutableDateTime(): void
    {
        $mutable = new DateTime('2026-03-15 09:00:00');
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'         => '/uploads/file.pdf',
            'lastModified' => $mutable,
        ]);

        $this->assertInstanceOf(DateTimeImmutable::class, $item->lastModified());
        $this->assertSame('2026-03-15 09:00:00', $item->lastModified()->format('Y-m-d H:i:s'));
    }

    public function testFactoryNullLastModifiedStaysNull(): void
    {
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'         => '/uploads/file.pdf',
            'lastModified' => null,
        ]);

        $this->assertNull($item->lastModified());
    }

    public function testFactoryIgnoresNonArrayMetadata(): void
    {
        $item = (new AssetStorageItemFactory())->fromArray([
            'path'     => '/uploads/file.pdf',
            'metadata' => 'invalid',
        ]);

        $this->assertSame([], $item->metadata());
    }
}
