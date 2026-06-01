<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessManager;
use Period\WpKit\WordPress\Access\AssetAccessPolicyInterface;
use Period\WpKit\WordPress\Access\AssetAccessResult;
use Period\WpKit\WordPress\Access\AssetDeliveryInterface;
use Period\WpKit\WordPress\Access\AssetRequestContext;
use Period\WpKit\WordPress\Access\AssetStorageItem;
use Period\WpKit\WordPress\Access\FileStreamInterface;
use Period\WpKit\WordPress\Access\InMemoryAssetStorage;
use Period\WpKit\WordPress\Access\NativeFileStream;
use Period\WpKit\WordPress\Access\PublicAssetAccessPolicy;
use Period\WpKit\WordPress\Access\StreamedAssetDelivery;

final class StreamedAssetDeliveryTest extends TestCase
{
    private function makeContext(string $path = '/uploads/file.pdf'): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: $path,
            assetUrl: 'https://example.com' . $path,
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            requestTime: new DateTimeImmutable(),
        );
    }

    private function allowManager(): AssetAccessManager
    {
        return new AssetAccessManager(new PublicAssetAccessPolicy());
    }

    private function denyManager(): AssetAccessManager
    {
        return new AssetAccessManager(new class implements AssetAccessPolicyInterface {
            public function evaluate(AssetRequestContext $ctx): AssetAccessResult
            {
                return AssetAccessResult::deny('Forbidden');
            }
        });
    }

    private function streamWith(string $path, string $contents): FileStreamInterface
    {
        return new class($path, $contents) implements FileStreamInterface {
            public function __construct(
                private readonly string $path,
                private readonly string $contents,
            ) {}

            public function exists(string $p): bool
            {
                return $p === $this->path;
            }

            public function read(string $p): string
            {
                if ($p !== $this->path) {
                    throw new \RuntimeException('File not found: ' . $p);
                }
                return $this->contents;
            }
        };
    }

    private function emptyStream(): FileStreamInterface
    {
        return new class implements FileStreamInterface {
            public function exists(string $path): bool { return false; }

            public function read(string $path): string
            {
                throw new \RuntimeException('File not found: ' . $path);
            }
        };
    }

    private function throwingStream(): FileStreamInterface
    {
        return new class implements FileStreamInterface {
            public function exists(string $path): bool { return true; }

            public function read(string $path): string
            {
                throw new \RuntimeException('Read error');
            }
        };
    }

    private function storageWith(string $path, ?string $mime = null): InMemoryAssetStorage
    {
        return new InMemoryAssetStorage([
            new AssetStorageItem($path, null, $mime, null, null),
        ]);
    }

    // --- interface ---

    public function testImplementsInterface(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            new InMemoryAssetStorage([]),
            $this->emptyStream(),
        );

        $this->assertInstanceOf(AssetDeliveryInterface::class, $delivery);
    }

    // --- missing asset ---

    public function testMissingStorageItemReturns404(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            new InMemoryAssetStorage([]),
            $this->emptyStream(),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/missing.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(404, $result->statusCode());
    }

    // --- denied asset ---

    public function testDeniedAssetReturns403(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->denyManager(),
            $this->storageWith('/uploads/file.pdf'),
            $this->streamWith('/uploads/file.pdf', 'content'),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(403, $result->statusCode());
    }

    // --- stream failure ---

    public function testMissingFileReturns500(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf'),
            $this->emptyStream(),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(500, $result->statusCode());
    }

    public function testReadExceptionReturns500(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf'),
            $this->throwingStream(),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertFalse($result->success());
        $this->assertSame(500, $result->statusCode());
    }

    // --- stream success ---

    public function testSuccessReturns200(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf'),
            $this->streamWith('/uploads/file.pdf', 'file bytes'),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertTrue($result->success());
        $this->assertSame(200, $result->statusCode());
    }

    public function testSuccessBodyContainsFileContents(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf'),
            $this->streamWith('/uploads/file.pdf', 'PDF file contents here'),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertSame('PDF file contents here', $result->body());
    }

    // --- mime passthrough ---

    public function testMimeTypeSetAsContentTypeHeader(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf', 'application/pdf'),
            $this->streamWith('/uploads/file.pdf', 'content'),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertSame('application/pdf', $result->headers()['Content-Type']);
    }

    public function testNullMimeTypeOmitsContentTypeHeader(): void
    {
        $delivery = new StreamedAssetDelivery(
            $this->allowManager(),
            $this->storageWith('/uploads/file.pdf', null),
            $this->streamWith('/uploads/file.pdf', 'content'),
        );

        $result = $delivery->deliver($this->makeContext('/uploads/file.pdf'));

        $this->assertArrayNotHasKey('Content-Type', $result->headers());
    }

    // --- NativeFileStream ---

    public function testNativeFileStreamImplementsInterface(): void
    {
        $this->assertInstanceOf(FileStreamInterface::class, new NativeFileStream());
    }

    public function testNativeFileStreamExistsReturnsFalseForMissingFile(): void
    {
        $this->assertFalse((new NativeFileStream())->exists('/nonexistent/path/file.pdf'));
    }

    public function testNativeFileStreamReadThrowsForMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        (new NativeFileStream())->read('/nonexistent/path/file.pdf');
    }
}
