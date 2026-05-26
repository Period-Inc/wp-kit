<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\OutsideWebrootAssetPathStrategy;
use Period\WpFramework\WordPress\Access\ProtectedAssetPathStrategyInterface;

final class OutsideWebrootAssetPathStrategyTest extends TestCase
{
    public function testImplementsProtectedAssetPathStrategyInterface(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertInstanceOf(ProtectedAssetPathStrategyInterface::class, $strategy);
    }

    public function testProtectedPathConversion(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame('/var/private-assets/foo.pdf', $strategy->protectedPath('uploads/foo.pdf'));
    }

    public function testProtectedPathPreservesSubdirectories(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame(
            '/var/private-assets/2026/05/report.pdf',
            $strategy->protectedPath('/uploads/2026/05/report.pdf'),
        );
    }

    public function testPublicPathConversion(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame('uploads/foo.pdf', $strategy->publicPath('/var/private-assets/foo.pdf'));
    }

    public function testPublicPathKeepsPublicUploadsPrefix(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame('uploads/foo.pdf', $strategy->publicPath('uploads/foo.pdf'));
    }

    public function testIsProtectedReturnsTrueForPrivateRootPath(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertTrue($strategy->isProtected('/var/private-assets/foo.pdf'));
    }

    public function testIsProtectedReturnsFalseForPublicUploadsPath(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertFalse($strategy->isProtected('uploads/foo.pdf'));
    }

    public function testIsProtectedDoesNotMatchSimilarPrefix(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertFalse($strategy->isProtected('/var/private-assets-other/foo.pdf'));
    }

    public function testTraversalCleanup(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame(
            '/var/private-assets/secret.pdf',
            $strategy->protectedPath('uploads/2026/../secret.pdf'),
        );
        $this->assertSame(
            'uploads/secret.pdf',
            $strategy->publicPath('/var/private-assets/2026/../secret.pdf'),
        );
    }

    public function testDuplicateSlashNormalization(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('//var//private-assets//');

        $this->assertSame(
            '/var/private-assets/foo.pdf',
            $strategy->protectedPath('//uploads//foo.pdf'),
        );
        $this->assertSame(
            'uploads/foo.pdf',
            $strategy->publicPath('//var//private-assets//foo.pdf'),
        );
    }

    public function testCustomPublicUploadsPrefix(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets', 'wp-content/uploads');

        $this->assertSame(
            '/var/private-assets/foo.pdf',
            $strategy->protectedPath('/wp-content/uploads/foo.pdf'),
        );
        $this->assertSame(
            'wp-content/uploads/foo.pdf',
            $strategy->publicPath('/var/private-assets/foo.pdf'),
        );
    }

    public function testDeterministicOutput(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame(
            $strategy->protectedPath('uploads/foo.pdf'),
            $strategy->protectedPath('uploads/foo.pdf'),
        );
        $this->assertSame(
            $strategy->publicPath('/var/private-assets/foo.pdf'),
            $strategy->publicPath('/var/private-assets/foo.pdf'),
        );
    }

    public function testNoFileSystemBehavior(): void
    {
        $strategy = new OutsideWebrootAssetPathStrategy('/var/private-assets');

        $this->assertSame('/var/private-assets/foo.pdf', $strategy->protectedPath('uploads/foo.pdf'));
        $this->assertSame('uploads/foo.pdf', $strategy->publicPath('/var/private-assets/foo.pdf'));
        $this->assertTrue($strategy->isProtected('/var/private-assets/foo.pdf'));
    }
}
