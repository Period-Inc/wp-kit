<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AttachmentIdResolverInterface;
use Period\WpKit\WordPress\Access\AttachmentMetaAssetStorageResolverAdapter;
use Period\WpKit\WordPress\Access\WordPressAttachmentIdResolver;

final class AttachmentIdResolverTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeResolver(
        int|false $returnId       = 42,
        string    $uploadsBaseUrl = 'https://example.com/wp-content/uploads',
    ): WordPressAttachmentIdResolver {
        return new WordPressAttachmentIdResolver(
            fn(string $url): int|false => $returnId,
            fn(): string               => $uploadsBaseUrl,
        );
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    public function testImplementsAttachmentIdResolverInterface(): void
    {
        $this->assertInstanceOf(AttachmentIdResolverInterface::class, $this->makeResolver());
    }

    // -----------------------------------------------------------------------
    // Positive int returned
    // -----------------------------------------------------------------------

    public function testPositiveIdIsReturned(): void
    {
        $resolver = $this->makeResolver(returnId: 7);

        $this->assertSame(7, $resolver->resolve('/protected-uploads/file.pdf'));
    }

    // -----------------------------------------------------------------------
    // Falsy / zero results become null
    // -----------------------------------------------------------------------

    public function testFalseResultBecomesNull(): void
    {
        $resolver = $this->makeResolver(returnId: false);

        $this->assertNull($resolver->resolve('/protected-uploads/file.pdf'));
    }

    public function testZeroResultBecomesNull(): void
    {
        $resolver = new WordPressAttachmentIdResolver(
            fn(string $url): int|false => 0,
            fn(): string               => 'https://example.com/wp-content/uploads',
        );

        $this->assertNull($resolver->resolve('/protected-uploads/file.pdf'));
    }

    public function testNegativeIdBecomesNull(): void
    {
        $resolver = new WordPressAttachmentIdResolver(
            fn(string $url): int|false => -1,
            fn(): string               => 'https://example.com/wp-content/uploads',
        );

        $this->assertNull($resolver->resolve('/protected-uploads/file.pdf'));
    }

    // -----------------------------------------------------------------------
    // Relative path → absolute URL via uploads base
    // -----------------------------------------------------------------------

    public function testRelativePathReceivesUploadsBaseUrl(): void
    {
        $receivedUrl = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$receivedUrl): int|false {
                $receivedUrl = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $resolver->resolve('/protected-uploads/file.pdf');

        $this->assertSame('https://example.com/wp-content/uploads/protected-uploads/file.pdf', $receivedUrl);
    }

    public function testRelativePathWithoutLeadingSlash(): void
    {
        $receivedUrl = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$receivedUrl): int|false {
                $receivedUrl = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $resolver->resolve('protected-uploads/file.pdf');

        $this->assertSame('https://example.com/wp-content/uploads/protected-uploads/file.pdf', $receivedUrl);
    }

    public function testUploadsBaseUrlTrailingSlashIsNormalized(): void
    {
        $receivedUrl = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$receivedUrl): int|false {
                $receivedUrl = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads/',
        );

        $resolver->resolve('/file.pdf');

        $this->assertSame('https://example.com/wp-content/uploads/file.pdf', $receivedUrl);
    }

    // -----------------------------------------------------------------------
    // Absolute URL passthrough
    // -----------------------------------------------------------------------

    public function testAbsoluteHttpUrlIsPassedDirectly(): void
    {
        $receivedUrl = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$receivedUrl): int|false {
                $receivedUrl = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $resolver->resolve('https://cdn.example.com/file.pdf');

        $this->assertSame('https://cdn.example.com/file.pdf', $receivedUrl);
    }

    public function testAbsoluteHttpsUrlIsPassedDirectly(): void
    {
        $receivedUrl = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$receivedUrl): int|false {
                $receivedUrl = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $resolver->resolve('http://example.com/uploads/file.pdf');

        $this->assertSame('http://example.com/uploads/file.pdf', $receivedUrl);
    }

    public function testAbsoluteUrlDoesNotPrependUploadsBase(): void
    {
        $baseUrlCalled = false;

        $resolver = new WordPressAttachmentIdResolver(
            fn(string $url): int|false => 1,
            function () use (&$baseUrlCalled): string {
                $baseUrlCalled = true;
                return 'https://example.com/wp-content/uploads';
            },
        );

        $resolver->resolve('https://cdn.example.com/file.pdf');

        $this->assertFalse($baseUrlCalled);
    }

    // -----------------------------------------------------------------------
    // attachmentUrlToPostId receives the correct URL
    // -----------------------------------------------------------------------

    public function testAttachmentUrlToPostIdIsCalledOnce(): void
    {
        $callCount = 0;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$callCount): int|false {
                $callCount++;
                return 5;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $resolver->resolve('/file.pdf');

        $this->assertSame(1, $callCount);
    }

    // -----------------------------------------------------------------------
    // AttachmentMetaAssetStorageResolverAdapter
    // -----------------------------------------------------------------------

    public function testAdapterReturnsCallable(): void
    {
        $adapter = new AttachmentMetaAssetStorageResolverAdapter($this->makeResolver());

        $this->assertIsCallable($adapter->resolver());
    }

    public function testAdapterCallableDelegatesToResolver(): void
    {
        $resolver = $this->makeResolver(returnId: 99);
        $adapter  = new AttachmentMetaAssetStorageResolverAdapter($resolver);

        $callable = $adapter->resolver();
        $result   = $callable('/protected-uploads/file.pdf');

        $this->assertSame(99, $result);
    }

    public function testAdapterCallableReturnsNullWhenResolverReturnsNull(): void
    {
        $resolver = $this->makeResolver(returnId: false);
        $adapter  = new AttachmentMetaAssetStorageResolverAdapter($resolver);

        $callable = $adapter->resolver();

        $this->assertNull($callable('/protected-uploads/file.pdf'));
    }

    public function testAdapterCallablePassesPathThrough(): void
    {
        $capturedPath = null;

        $resolver = new WordPressAttachmentIdResolver(
            function (string $url) use (&$capturedPath): int|false {
                $capturedPath = $url;
                return 1;
            },
            fn(): string => 'https://example.com/wp-content/uploads',
        );

        $callable = (new AttachmentMetaAssetStorageResolverAdapter($resolver))->resolver();
        $callable('/protected-uploads/target.pdf');

        $this->assertSame('https://example.com/wp-content/uploads/protected-uploads/target.pdf', $capturedPath);
    }

    public function testAdapterEachCallReturnsIndependentCallable(): void
    {
        $adapter = new AttachmentMetaAssetStorageResolverAdapter($this->makeResolver(returnId: 3));

        $c1 = $adapter->resolver();
        $c2 = $adapter->resolver();

        $this->assertSame(3, $c1('/a.pdf'));
        $this->assertSame(3, $c2('/b.pdf'));
    }
}
