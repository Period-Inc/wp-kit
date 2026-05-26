<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetStorageInterface;
use Period\WpFramework\WordPress\Access\AssetStorageItem;
use Period\WpFramework\WordPress\Access\AttachmentAssetStorage;
use Period\WpFramework\WordPress\Access\WordPressAttachmentAssetStorageFactory;
use Period\WpFramework\WordPress\Access\WpAttachmentResolver;

final class WpAttachmentAdapterTest extends TestCase
{
    // --- WpAttachmentResolver ---

    public function testResolverPassesPathToLookup(): void
    {
        $received = null;
        $resolver = new WpAttachmentResolver(function (string $path) use (&$received): ?array {
            $received = $path;
            return null;
        });

        $resolver->resolve('/uploads/file.pdf');

        $this->assertSame('/uploads/file.pdf', $received);
    }

    public function testResolverReturnsNullWhenLookupReturnsNull(): void
    {
        $resolver = new WpAttachmentResolver(fn() => null);

        $this->assertNull($resolver->resolve('/uploads/missing.pdf'));
    }

    public function testResolverPassesThroughLookupResult(): void
    {
        $data = [
            'path'     => '/uploads/file.pdf',
            'url'      => 'https://example.com/uploads/file.pdf',
            'mimeType' => 'application/pdf',
        ];
        $resolver = new WpAttachmentResolver(fn() => $data);

        $this->assertSame($data, $resolver->resolve('/uploads/file.pdf'));
    }

    // --- WordPressAttachmentAssetStorageFactory ---

    public function testFactoryCreatesAttachmentAssetStorage(): void
    {
        $resolver = new WpAttachmentResolver(fn() => null);
        $factory  = new WordPressAttachmentAssetStorageFactory();
        $storage  = $factory->create($resolver);

        $this->assertInstanceOf(AttachmentAssetStorage::class, $storage);
    }

    public function testFactoryCreatedStorageImplementsInterface(): void
    {
        $resolver = new WpAttachmentResolver(fn() => null);
        $storage  = (new WordPressAttachmentAssetStorageFactory())->create($resolver);

        $this->assertInstanceOf(AssetStorageInterface::class, $storage);
    }

    public function testCreatedStorageReturnNullForMissingAttachment(): void
    {
        $resolver = new WpAttachmentResolver(fn() => null);
        $storage  = (new WordPressAttachmentAssetStorageFactory())->create($resolver);

        $this->assertNull($storage->find('/uploads/missing.pdf'));
    }

    public function testCreatedStorageReturnsItemForFoundAttachment(): void
    {
        $resolver = new WpAttachmentResolver(fn() => [
            'path'     => '/uploads/file.pdf',
            'url'      => 'https://example.com/uploads/file.pdf',
            'mimeType' => 'application/pdf',
            'size'     => 204800,
        ]);
        $storage = (new WordPressAttachmentAssetStorageFactory())->create($resolver);

        $item = $storage->find('/uploads/file.pdf');

        $this->assertInstanceOf(AssetStorageItem::class, $item);
        $this->assertSame('/uploads/file.pdf', $item->path());
        $this->assertSame('https://example.com/uploads/file.pdf', $item->url());
        $this->assertSame('application/pdf', $item->mimeType());
        $this->assertSame(204800, $item->size());
    }

    public function testCreatedStoragePassesThroughMetadata(): void
    {
        $resolver = new WpAttachmentResolver(fn() => [
            'path'     => '/uploads/file.pdf',
            'metadata' => ['attachmentId' => 99, 'protected' => true],
        ]);
        $storage = (new WordPressAttachmentAssetStorageFactory())->create($resolver);

        $item = $storage->find('/uploads/file.pdf');

        $this->assertSame(['attachmentId' => 99, 'protected' => true], $item->metadata());
    }

    public function testResolverIsInvokedWithCorrectPathViaStorage(): void
    {
        $received = null;
        $resolver = new WpAttachmentResolver(function (string $path) use (&$received): ?array {
            $received = $path;
            return null;
        });
        $storage = (new WordPressAttachmentAssetStorageFactory())->create($resolver);

        $storage->find('/uploads/photo.jpg');

        $this->assertSame('/uploads/photo.jpg', $received);
    }
}
