<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentMeta;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaReader;

final class AssetAttachmentMetaReaderTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @param array<string,mixed> $meta
     */
    private function makeReader(array $meta): AssetAttachmentMetaReader
    {
        return new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use ($meta): mixed {
                return $meta[$key] ?? '';
            },
        );
    }

    private function protectedMeta(
        string $path = '/protected-uploads/file.pdf',
        string $url  = '/asset-access?asset=protected-uploads%2Ffile.pdf',
    ): array {
        return [
            '_period_asset_protected'      => '1',
            '_period_asset_protected_path' => $path,
            '_period_asset_delivery_url'   => $url,
        ];
    }

    private function publicMeta(): array
    {
        return [
            '_period_asset_protected'      => '',
            '_period_asset_protected_path' => '',
            '_period_asset_delivery_url'   => '',
        ];
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentMeta value object
    // -----------------------------------------------------------------------

    public function testIsProtectedReturnsTrueWhenProtected(): void
    {
        $meta = new AssetAttachmentMeta(true, '/protected-uploads/a.pdf', null);

        $this->assertTrue($meta->isProtected());
    }

    public function testIsProtectedReturnsFalseWhenPublic(): void
    {
        $meta = new AssetAttachmentMeta(false, null, null);

        $this->assertFalse($meta->isProtected());
    }

    public function testProtectedPathReturnsValue(): void
    {
        $meta = new AssetAttachmentMeta(true, '/protected-uploads/a.pdf', null);

        $this->assertSame('/protected-uploads/a.pdf', $meta->protectedPath());
    }

    public function testProtectedPathReturnsNullWhenAbsent(): void
    {
        $meta = new AssetAttachmentMeta(false, null, null);

        $this->assertNull($meta->protectedPath());
    }

    public function testDeliveryUrlReturnsValue(): void
    {
        $meta = new AssetAttachmentMeta(true, null, '/asset-access?asset=x');

        $this->assertSame('/asset-access?asset=x', $meta->deliveryUrl());
    }

    public function testDeliveryUrlReturnsNullWhenAbsent(): void
    {
        $meta = new AssetAttachmentMeta(false, null, null);

        $this->assertNull($meta->deliveryUrl());
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentMetaReader — protected meta
    // -----------------------------------------------------------------------

    public function testReadReturnsAssetAttachmentMeta(): void
    {
        $reader = $this->makeReader($this->protectedMeta());

        $this->assertInstanceOf(AssetAttachmentMeta::class, $reader->read(1));
    }

    public function testReadProtectedFlagIsTrue(): void
    {
        $reader = $this->makeReader($this->protectedMeta());

        $this->assertTrue($reader->read(1)->isProtected());
    }

    public function testReadProtectedPathIsCorrect(): void
    {
        $reader = $this->makeReader($this->protectedMeta('/protected-uploads/secret.pdf'));

        $this->assertSame('/protected-uploads/secret.pdf', $reader->read(1)->protectedPath());
    }

    public function testReadDeliveryUrlIsCorrect(): void
    {
        $reader = $this->makeReader($this->protectedMeta(url: '/asset-access?asset=x'));

        $this->assertSame('/asset-access?asset=x', $reader->read(1)->deliveryUrl());
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentMetaReader — public meta
    // -----------------------------------------------------------------------

    public function testReadPublicFlagIsFalse(): void
    {
        $reader = $this->makeReader($this->publicMeta());

        $this->assertFalse($reader->read(1)->isProtected());
    }

    public function testReadPublicPathIsNull(): void
    {
        $reader = $this->makeReader($this->publicMeta());

        $this->assertNull($reader->read(1)->protectedPath());
    }

    public function testReadPublicDeliveryUrlIsNull(): void
    {
        $reader = $this->makeReader($this->publicMeta());

        $this->assertNull($reader->read(1)->deliveryUrl());
    }

    // -----------------------------------------------------------------------
    // Normalization — missing / falsy values
    // -----------------------------------------------------------------------

    public function testMissingProtectedPathIsNormalizedToNull(): void
    {
        $reader = $this->makeReader(['_period_asset_protected' => '1']);

        $this->assertNull($reader->read(1)->protectedPath());
    }

    public function testEmptyProtectedPathIsNormalizedToNull(): void
    {
        $reader = $this->makeReader([
            '_period_asset_protected'      => '1',
            '_period_asset_protected_path' => '',
        ]);

        $this->assertNull($reader->read(1)->protectedPath());
    }

    public function testMissingDeliveryUrlIsNormalizedToNull(): void
    {
        $reader = $this->makeReader([
            '_period_asset_protected'      => '1',
            '_period_asset_protected_path' => '/protected-uploads/a.pdf',
        ]);

        $this->assertNull($reader->read(1)->deliveryUrl());
    }

    public function testEmptyDeliveryUrlIsNormalizedToNull(): void
    {
        $reader = $this->makeReader([
            '_period_asset_protected'      => '1',
            '_period_asset_protected_path' => '/protected-uploads/a.pdf',
            '_period_asset_delivery_url'   => '',
        ]);

        $this->assertNull($reader->read(1)->deliveryUrl());
    }

    public function testZeroStringProtectedFlagIsFalse(): void
    {
        $reader = $this->makeReader(['_period_asset_protected' => '0']);

        $this->assertFalse($reader->read(1)->isProtected());
    }

    public function testFalseReturnFromGetMetaIsNormalizedToNull(): void
    {
        $reader = new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single): mixed {
                return match ($key) {
                    '_period_asset_protected'      => '1',
                    '_period_asset_protected_path' => false,
                    '_period_asset_delivery_url'   => false,
                    default                        => '',
                };
            },
        );

        $this->assertNull($reader->read(1)->protectedPath());
        $this->assertNull($reader->read(1)->deliveryUrl());
    }

    // -----------------------------------------------------------------------
    // getMeta called with correct arguments
    // -----------------------------------------------------------------------

    public function testGetMetaCalledWithAttachmentId(): void
    {
        $seenIds = [];

        $reader = new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use (&$seenIds): mixed {
                $seenIds[] = $id;
                return '';
            },
        );

        $reader->read(77);

        $this->assertNotEmpty($seenIds);
        foreach ($seenIds as $id) {
            $this->assertSame(77, $id);
        }
    }

    public function testGetMetaCalledWithSingleTrue(): void
    {
        $seenSingles = [];

        $reader = new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use (&$seenSingles): mixed {
                $seenSingles[] = $single;
                return '';
            },
        );

        $reader->read(1);

        foreach ($seenSingles as $single) {
            $this->assertTrue($single);
        }
    }

    public function testGetMetaCalledForAllThreeKeys(): void
    {
        $seenKeys = [];

        $reader = new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use (&$seenKeys): mixed {
                $seenKeys[] = $key;
                return '';
            },
        );

        $reader->read(1);

        $this->assertContains('_period_asset_protected', $seenKeys);
        $this->assertContains('_period_asset_protected_path', $seenKeys);
        $this->assertContains('_period_asset_delivery_url', $seenKeys);
    }
}
