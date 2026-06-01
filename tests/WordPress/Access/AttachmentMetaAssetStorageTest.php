<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaReader;
use Period\WpKit\WordPress\Access\AssetStorageInterface;
use Period\WpKit\WordPress\Access\AssetStorageItem;
use Period\WpKit\WordPress\Access\AttachmentMetaAssetStorage;

final class AttachmentMetaAssetStorageTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeReader(
        bool    $protected     = true,
        ?string $protectedPath = '/protected-uploads/file.pdf',
        ?string $deliveryUrl   = '/asset-access?asset=protected-uploads%2Ffile.pdf',
    ): AssetAttachmentMetaReader {
        return new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use ($protected, $protectedPath, $deliveryUrl): mixed {
                return match ($key) {
                    '_period_asset_protected'      => $protected ? '1' : '',
                    '_period_asset_protected_path' => $protectedPath ?? '',
                    '_period_asset_delivery_url'   => $deliveryUrl   ?? '',
                    default                        => '',
                };
            },
        );
    }

    private function makeStorage(
        ?int    $resolvedId    = 42,
        bool    $protected     = true,
        ?string $protectedPath = '/protected-uploads/file.pdf',
        ?string $deliveryUrl   = '/asset-access?asset=protected-uploads%2Ffile.pdf',
    ): AttachmentMetaAssetStorage {
        return new AttachmentMetaAssetStorage(
            $this->makeReader($protected, $protectedPath, $deliveryUrl),
            fn(string $path) => $resolvedId,
        );
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    public function testImplementsAssetStorageInterface(): void
    {
        $this->assertInstanceOf(AssetStorageInterface::class, $this->makeStorage());
    }

    // -----------------------------------------------------------------------
    // Protected attachment returns AssetStorageItem
    // -----------------------------------------------------------------------

    public function testProtectedAttachmentReturnsStorageItem(): void
    {
        $storage = $this->makeStorage();

        $result = $storage->find('/protected-uploads/file.pdf');

        $this->assertInstanceOf(AssetStorageItem::class, $result);
    }

    public function testStorageItemPathMatchesAssetPath(): void
    {
        $storage = $this->makeStorage();

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame('/protected-uploads/file.pdf', $item->path());
    }

    public function testStorageItemUrlIsDeliveryUrl(): void
    {
        $storage = $this->makeStorage(deliveryUrl: '/asset-access?asset=x');

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame('/asset-access?asset=x', $item->url());
    }

    public function testStorageItemMimeTypeIsNull(): void
    {
        $storage = $this->makeStorage();

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertNull($item->mimeType());
    }

    public function testStorageItemSizeIsNull(): void
    {
        $storage = $this->makeStorage();

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertNull($item->size());
    }

    public function testStorageItemLastModifiedIsNull(): void
    {
        $storage = $this->makeStorage();

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertNull($item->lastModified());
    }

    // -----------------------------------------------------------------------
    // Public attachment returns null
    // -----------------------------------------------------------------------

    public function testPublicAttachmentReturnsNull(): void
    {
        $storage = $this->makeStorage(protected: false);

        $this->assertNull($storage->find('/uploads/file.pdf'));
    }

    // -----------------------------------------------------------------------
    // Missing attachment id returns null
    // -----------------------------------------------------------------------

    public function testMissingAttachmentIdReturnsNull(): void
    {
        $storage = $this->makeStorage(resolvedId: null);

        $this->assertNull($storage->find('/protected-uploads/file.pdf'));
    }

    // -----------------------------------------------------------------------
    // Missing protected path returns null
    // -----------------------------------------------------------------------

    public function testNullProtectedPathReturnsNull(): void
    {
        $storage = $this->makeStorage(protectedPath: null);

        $this->assertNull($storage->find('/protected-uploads/file.pdf'));
    }

    public function testEmptyProtectedPathReturnsNull(): void
    {
        $storage = $this->makeStorage(protectedPath: '');

        $this->assertNull($storage->find('/protected-uploads/file.pdf'));
    }

    // -----------------------------------------------------------------------
    // Path mismatch returns null
    // -----------------------------------------------------------------------

    public function testPathMismatchReturnsNull(): void
    {
        $storage = $this->makeStorage(protectedPath: '/protected-uploads/other.pdf');

        $this->assertNull($storage->find('/protected-uploads/file.pdf'));
    }

    public function testPathMatchReturnsItem(): void
    {
        $storage = $this->makeStorage(protectedPath: '/protected-uploads/exact.pdf');

        $this->assertInstanceOf(AssetStorageItem::class, $storage->find('/protected-uploads/exact.pdf'));
    }

    // -----------------------------------------------------------------------
    // Delivery URL passthrough
    // -----------------------------------------------------------------------

    public function testNullDeliveryUrlResultsInNullItemUrl(): void
    {
        $storage = $this->makeStorage(deliveryUrl: null);

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertNull($item?->url());
    }

    public function testDeliveryUrlPassedThroughToItem(): void
    {
        $storage = $this->makeStorage(deliveryUrl: '/asset-access?asset=protected-uploads%2Ffile.pdf');

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame('/asset-access?asset=protected-uploads%2Ffile.pdf', $item->url());
    }

    // -----------------------------------------------------------------------
    // Metadata contains attachmentId
    // -----------------------------------------------------------------------

    public function testMetadataContainsAttachmentId(): void
    {
        $storage = $this->makeStorage(resolvedId: 99);

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame(99, $item->metadata()['attachmentId']);
    }

    public function testMetadataContainsProtectedFlag(): void
    {
        $storage = $this->makeStorage();

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertTrue($item->metadata()['protected']);
    }

    public function testMetadataContainsProtectedPath(): void
    {
        $storage = $this->makeStorage(protectedPath: '/protected-uploads/file.pdf');

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame('/protected-uploads/file.pdf', $item->metadata()['protectedPath']);
    }

    public function testMetadataContainsDeliveryUrl(): void
    {
        $storage = $this->makeStorage(deliveryUrl: '/asset-access?asset=x');

        $item = $storage->find('/protected-uploads/file.pdf');

        $this->assertSame('/asset-access?asset=x', $item->metadata()['deliveryUrl']);
    }

    // -----------------------------------------------------------------------
    // attachmentIdResolver receives assetPath
    // -----------------------------------------------------------------------

    public function testAttachmentIdResolverReceivesAssetPath(): void
    {
        $capturedPath = null;

        $storage = new AttachmentMetaAssetStorage(
            $this->makeReader(),
            function (string $path) use (&$capturedPath): ?int {
                $capturedPath = $path;
                return 1;
            },
        );

        $storage->find('/protected-uploads/target.pdf');

        $this->assertSame('/protected-uploads/target.pdf', $capturedPath);
    }
}
