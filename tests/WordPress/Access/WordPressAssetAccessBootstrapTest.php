<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessManager;
use Period\WpFramework\WordPress\Access\AssetDeliveryResult;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\AssetRequestMatcher;
use Period\WpFramework\WordPress\Access\DefaultRequestContextFactory;
use Period\WpFramework\WordPress\Access\InMemoryAssetStorage;
use Period\WpFramework\WordPress\Access\NullAssetDelivery;
use Period\WpFramework\WordPress\Access\PublicAssetAccessPolicy;
use Period\WpFramework\WordPress\Access\RequestContextFactoryInterface;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessBootstrap;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessKernel;

final class WordPressAssetAccessBootstrapTest extends TestCase
{
    private function makeKernel(
        bool $matchAll = true,
        bool $storageEmpty = true,
    ): WordPressAssetAccessKernel {
        $matcher = $matchAll
            ? new AssetRequestMatcher(['/wp-content/uploads/'])
            : new AssetRequestMatcher(['/never-matches/']);

        return new WordPressAssetAccessKernel(
            $matcher,
            new AssetAccessManager(new PublicAssetAccessPolicy()),
            new NullAssetDelivery(),
            new InMemoryAssetStorage([]),
        );
    }

    private function makeFactory(string $resolvedUrl = 'https://example.com/wp-content/uploads/file.pdf'): DefaultRequestContextFactory
    {
        return new DefaultRequestContextFactory(
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            assetUrlResolver: fn(string $uri) => $resolvedUrl,
        );
    }

    // --- WordPressAssetAccessBootstrap ---

    public function testBootPassesContextToKernel(): void
    {
        $capturedContext = null;

        $factory = fn(string $uri) => new AssetRequestContext(
            assetPath: '/wp-content/uploads/file.pdf',
            assetUrl: 'https://example.com/wp-content/uploads/file.pdf',
            currentUserId: 42,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );

        $kernel = $this->makeKernel();
        $bootstrap = new WordPressAssetAccessBootstrap($kernel, $factory);

        $result = $bootstrap->boot('/wp-content/uploads/file.pdf');

        // Kernel returns 404 (storage empty) but not null — confirms match occurred
        $this->assertNotNull($result);
        $this->assertSame(404, $result->statusCode());
    }

    public function testBootReturnsNullWhenKernelReturnsNull(): void
    {
        $kernel = $this->makeKernel(matchAll: false);
        $factory = fn(string $uri) => new AssetRequestContext(
            assetPath: $uri,
            assetUrl: 'https://example.com' . $uri,
            currentUserId: 1,
            currentUserRoles: [],
            requestTime: new DateTimeImmutable(),
        );

        $bootstrap = new WordPressAssetAccessBootstrap($kernel, $factory);

        $this->assertNull($bootstrap->boot('/wp-content/uploads/file.pdf'));
    }

    public function testBootPassesRequestUriToFactory(): void
    {
        $receivedUri = null;
        $factory = function (string $uri) use (&$receivedUri): AssetRequestContext {
            $receivedUri = $uri;
            return new AssetRequestContext(
                assetPath: $uri,
                assetUrl: 'https://example.com' . $uri,
                currentUserId: 0,
                currentUserRoles: [],
                requestTime: new DateTimeImmutable(),
            );
        };

        $kernel = $this->makeKernel(matchAll: false);
        $bootstrap = new WordPressAssetAccessBootstrap($kernel, $factory);

        $bootstrap->boot('/wp-content/uploads/secret.pdf');

        $this->assertSame('/wp-content/uploads/secret.pdf', $receivedUri);
    }

    // --- DefaultRequestContextFactory ---

    public function testFactoryImplementsInterface(): void
    {
        $this->assertInstanceOf(
            RequestContextFactoryInterface::class,
            $this->makeFactory(),
        );
    }

    public function testFactoryCreatesContextWithCurrentUser(): void
    {
        $factory = new DefaultRequestContextFactory(
            currentUserId: 7,
            currentUserRoles: ['editor', 'author'],
            assetUrlResolver: fn($uri) => 'https://example.com' . $uri,
        );

        $context = $factory->create('/wp-content/uploads/file.pdf');

        $this->assertSame(7, $context->currentUserId());
        $this->assertSame(['editor', 'author'], $context->currentUserRoles());
    }

    public function testFactoryResolvesAssetUrl(): void
    {
        $factory = new DefaultRequestContextFactory(
            currentUserId: 1,
            currentUserRoles: [],
            assetUrlResolver: fn($uri) => 'https://cdn.example.com' . $uri,
        );

        $context = $factory->create('/wp-content/uploads/photo.jpg');

        $this->assertSame('https://cdn.example.com/wp-content/uploads/photo.jpg', $context->assetUrl());
    }

    public function testFactoryStripsQueryStringFromAssetPath(): void
    {
        $context = $this->makeFactory()->create('/wp-content/uploads/file.pdf?v=42&foo=bar');

        $this->assertSame('/wp-content/uploads/file.pdf', $context->assetPath());
    }

    public function testFactoryPassesRawUriToUrlResolver(): void
    {
        $receivedUri = null;
        $factory = new DefaultRequestContextFactory(
            currentUserId: 1,
            currentUserRoles: [],
            assetUrlResolver: function (string $uri) use (&$receivedUri): string {
                $receivedUri = $uri;
                return 'https://example.com' . $uri;
            },
        );

        $factory->create('/wp-content/uploads/file.pdf?v=1');

        $this->assertSame('/wp-content/uploads/file.pdf?v=1', $receivedUri);
    }

    public function testFactoryRequestTimeIsDateTimeImmutable(): void
    {
        $context = $this->makeFactory()->create('/wp-content/uploads/file.pdf');

        $this->assertInstanceOf(DateTimeImmutable::class, $context->requestTime());
    }
}
