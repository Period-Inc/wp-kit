<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetStorageInterface;
use Period\WpFramework\WordPress\Access\AssetStorageItem;
use Period\WpFramework\WordPress\Access\InMemoryAssetStorage;

final class AssetStorageTest extends TestCase
{
    // --- AssetStorageItem ---

    public function testItemStoresPath(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertSame('/uploads/file.pdf', $item->path());
    }

    public function testItemStoresUrl(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', 'https://example.com/uploads/file.pdf', null, null, null);

        $this->assertSame('https://example.com/uploads/file.pdf', $item->url());
    }

    public function testItemNullUrl(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertNull($item->url());
    }

    public function testItemStoresMimeType(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, 'application/pdf', null, null);

        $this->assertSame('application/pdf', $item->mimeType());
    }

    public function testItemNullMimeType(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertNull($item->mimeType());
    }

    public function testItemStoresSize(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, 204800, null);

        $this->assertSame(204800, $item->size());
    }

    public function testItemNullSize(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertNull($item->size());
    }

    public function testItemStoresLastModified(): void
    {
        $time = new DateTimeImmutable('2026-01-15 10:00:00');
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, $time);

        $this->assertSame($time, $item->lastModified());
    }

    public function testItemNullLastModified(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertNull($item->lastModified());
    }

    public function testItemDefaultMetadataIsEmpty(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null);

        $this->assertSame([], $item->metadata());
    }

    public function testItemStoresMetadata(): void
    {
        $item = new AssetStorageItem('/uploads/file.pdf', null, null, null, null, ['owner' => 42, 'private' => true]);

        $this->assertSame(['owner' => 42, 'private' => true], $item->metadata());
    }

    // --- InMemoryAssetStorage ---

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetStorageInterface::class, new InMemoryAssetStorage([]));
    }

    public function testFindReturnsMatchingItem(): void
    {
        $item = new AssetStorageItem('/uploads/photo.jpg', null, 'image/jpeg', null, null);
        $storage = new InMemoryAssetStorage([$item]);

        $this->assertSame($item, $storage->find('/uploads/photo.jpg'));
    }

    public function testFindReturnsNullForMissingPath(): void
    {
        $storage = new InMemoryAssetStorage([
            new AssetStorageItem('/uploads/photo.jpg', null, null, null, null),
        ]);

        $this->assertNull($storage->find('/uploads/other.jpg'));
    }

    public function testFindReturnsNullFromEmptyStorage(): void
    {
        $this->assertNull((new InMemoryAssetStorage([]))->find('/uploads/file.pdf'));
    }

    public function testFindMatchesExactPath(): void
    {
        $a = new AssetStorageItem('/uploads/a.pdf', null, null, null, null);
        $b = new AssetStorageItem('/uploads/b.pdf', null, null, null, null);
        $storage = new InMemoryAssetStorage([$a, $b]);

        $this->assertSame($b, $storage->find('/uploads/b.pdf'));
    }

    public function testFindReturnsFirstMatch(): void
    {
        $first  = new AssetStorageItem('/uploads/file.pdf', 'https://cdn1.example.com/file.pdf', null, null, null);
        $second = new AssetStorageItem('/uploads/file.pdf', 'https://cdn2.example.com/file.pdf', null, null, null);
        $storage = new InMemoryAssetStorage([$first, $second]);

        $this->assertSame($first, $storage->find('/uploads/file.pdf'));
    }
}
