<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetPathNormalizer;
use Period\WpFramework\WordPress\Access\DefaultProtectedAssetPathStrategy;
use Period\WpFramework\WordPress\Access\ProtectedAssetPathStrategyInterface;

final class ProtectedAssetPathTest extends TestCase
{
    private DefaultProtectedAssetPathStrategy $strategy;
    private AssetPathNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->strategy  = new DefaultProtectedAssetPathStrategy();
        $this->normalizer = new AssetPathNormalizer();
    }

    // --- DefaultProtectedAssetPathStrategy interface ---

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(ProtectedAssetPathStrategyInterface::class, $this->strategy);
    }

    // --- protectedPath ---

    public function testProtectedPathConvertsUploadsPrefix(): void
    {
        $this->assertSame(
            '/protected-uploads/example.pdf',
            $this->strategy->protectedPath('/uploads/example.pdf'),
        );
    }

    public function testProtectedPathPreservesSubdirectory(): void
    {
        $this->assertSame(
            '/protected-uploads/2026/01/photo.jpg',
            $this->strategy->protectedPath('/uploads/2026/01/photo.jpg'),
        );
    }

    public function testProtectedPathOnAlreadyProtectedPathWrapsAgain(): void
    {
        // Non-/uploads/ path gets prefix applied
        $result = $this->strategy->protectedPath('/protected-uploads/file.pdf');

        $this->assertSame('/protected-uploads/protected-uploads/file.pdf', $result);
    }

    public function testProtectedPathOnArbitraryPath(): void
    {
        $result = $this->strategy->protectedPath('/other/path/file.pdf');

        $this->assertStringStartsWith('/protected-uploads/', $result);
    }

    // --- publicPath ---

    public function testPublicPathConvertsProtectedPrefix(): void
    {
        $this->assertSame(
            '/uploads/example.pdf',
            $this->strategy->publicPath('/protected-uploads/example.pdf'),
        );
    }

    public function testPublicPathPreservesSubdirectory(): void
    {
        $this->assertSame(
            '/uploads/2026/01/photo.jpg',
            $this->strategy->publicPath('/protected-uploads/2026/01/photo.jpg'),
        );
    }

    public function testPublicPathOnAlreadyPublicPathReturnsAsIs(): void
    {
        $this->assertSame(
            '/uploads/file.pdf',
            $this->strategy->publicPath('/uploads/file.pdf'),
        );
    }

    // --- roundtrip ---

    public function testProtectedToPublicRoundtrip(): void
    {
        $original  = '/uploads/2026/03/document.pdf';
        $protected = $this->strategy->protectedPath($original);
        $restored  = $this->strategy->publicPath($protected);

        $this->assertSame($original, $restored);
    }

    // --- isProtected ---

    public function testIsProtectedReturnsTrueForProtectedPath(): void
    {
        $this->assertTrue($this->strategy->isProtected('/protected-uploads/file.pdf'));
    }

    public function testIsProtectedReturnsFalseForPublicPath(): void
    {
        $this->assertFalse($this->strategy->isProtected('/uploads/file.pdf'));
    }

    public function testIsProtectedReturnsFalseForArbitraryPath(): void
    {
        $this->assertFalse($this->strategy->isProtected('/wp-content/themes/style.css'));
    }

    public function testIsProtectedReturnsFalseForRoot(): void
    {
        $this->assertFalse($this->strategy->isProtected('/'));
    }

    // --- AssetPathNormalizer ---

    public function testNormalizeDuplicateSlashes(): void
    {
        $this->assertSame(
            '/uploads/file.pdf',
            $this->normalizer->normalize('//uploads//file.pdf'),
        );
    }

    public function testNormalizeAddsLeadingSlash(): void
    {
        $this->assertSame(
            '/uploads/file.pdf',
            $this->normalizer->normalize('uploads/file.pdf'),
        );
    }

    public function testNormalizeRemovesDotSegment(): void
    {
        $this->assertSame(
            '/uploads/file.pdf',
            $this->normalizer->normalize('/uploads/./file.pdf'),
        );
    }

    public function testNormalizeRemovesDoubleDotTraversal(): void
    {
        $this->assertSame(
            '/uploads/file.pdf',
            $this->normalizer->normalize('/uploads/../uploads/file.pdf'),
        );
    }

    public function testNormalizeTraversalAboveRoot(): void
    {
        // Cannot traverse above root; excess .. is ignored
        $this->assertSame(
            '/etc/passwd',
            $this->normalizer->normalize('/../etc/passwd'),
        );
    }

    public function testNormalizeMultipleTraversals(): void
    {
        $this->assertSame(
            '/a/d',
            $this->normalizer->normalize('/a/b/c/../../d'),
        );
    }

    public function testNormalizeAlreadyCleanPathUnchanged(): void
    {
        $this->assertSame(
            '/uploads/2026/01/file.pdf',
            $this->normalizer->normalize('/uploads/2026/01/file.pdf'),
        );
    }

    public function testNormalizeDeterministic(): void
    {
        $path = '//uploads/../uploads//file.pdf';

        $this->assertSame(
            $this->normalizer->normalize($path),
            $this->normalizer->normalize($path),
        );
    }

    public function testNormalizeRootPath(): void
    {
        $this->assertSame('/', $this->normalizer->normalize('/'));
    }
}
