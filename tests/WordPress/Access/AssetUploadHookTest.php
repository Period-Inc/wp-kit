<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\AssetUploadDecision;
use Period\WpFramework\WordPress\Access\AssetUploadInterceptor;
use Period\WpFramework\WordPress\Access\AssetUploadPathResolver;
use Period\WpFramework\WordPress\Access\AssetUploadPolicyInterface;
use Period\WpFramework\WordPress\Access\DefaultProtectedAssetPathStrategy;
use Period\WpFramework\WordPress\Access\WordPressAssetUploadHookRegistrar;

final class AssetUploadHookTest extends TestCase
{
    private function makeContext(string $path = '/uploads/file.pdf'): AssetRequestContext
    {
        return new AssetRequestContext(
            assetPath: $path,
            assetUrl: 'https://example.com' . $path,
            currentUserId: 1,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable(),
        );
    }

    private function makeResolver(): AssetUploadPathResolver
    {
        return new AssetUploadPathResolver(new DefaultProtectedAssetPathStrategy());
    }

    private function makePublicPolicy(): AssetUploadPolicyInterface
    {
        return new class implements AssetUploadPolicyInterface {
            public function decide(AssetRequestContext $context): AssetUploadDecision
            {
                return AssetUploadDecision::asPublic($context->assetPath());
            }
        };
    }

    private function makeProtectedPolicy(): AssetUploadPolicyInterface
    {
        return new class implements AssetUploadPolicyInterface {
            public function decide(AssetRequestContext $context): AssetUploadDecision
            {
                return AssetUploadDecision::asProtected($context->assetPath());
            }
        };
    }

    // --- public decision ---

    public function testPublicDecisionReturnsOriginalUpload(): void
    {
        $upload = [
            'file' => '/uploads/photo.jpg',
            'url'  => 'https://example.com/uploads/photo.jpg',
            'type' => 'image/jpeg',
        ];

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $this->assertSame($upload, $interceptor->intercept($upload));
    }

    public function testPublicDecisionPreservesAllKeys(): void
    {
        $upload = ['file' => '/uploads/doc.pdf', 'url' => 'https://example.com/uploads/doc.pdf', 'type' => 'application/pdf'];

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertSame('/uploads/doc.pdf', $result['file']);
    }

    // --- protected decision rewrites path only ---

    public function testProtectedDecisionRewritesFilePath(): void
    {
        $upload = [
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ];

        $interceptor = new AssetUploadInterceptor(
            $this->makeProtectedPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertSame('/protected-uploads/secret.pdf', $result['file']);
    }

    public function testProtectedDecisionLeavesUrlUnchanged(): void
    {
        $upload = [
            'file' => '/uploads/secret.pdf',
            'url'  => 'https://example.com/uploads/secret.pdf',
            'type' => 'application/pdf',
        ];

        $interceptor = new AssetUploadInterceptor(
            $this->makeProtectedPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertSame('https://example.com/uploads/secret.pdf', $result['url']);
    }

    public function testProtectedDecisionLeavesMimeTypeUnchanged(): void
    {
        $upload = ['file' => '/uploads/report.pdf', 'url' => '', 'type' => 'application/pdf'];

        $interceptor = new AssetUploadInterceptor(
            $this->makeProtectedPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertSame('application/pdf', $result['type']);
    }

    public function testProtectedDecisionWithSubdirectory(): void
    {
        $upload = ['file' => '/uploads/2026/05/report.pdf', 'url' => '', 'type' => 'application/pdf'];

        $interceptor = new AssetUploadInterceptor(
            $this->makeProtectedPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertSame('/protected-uploads/2026/05/report.pdf', $result['file']);
    }

    // --- contextFactory receives original upload ---

    public function testContextFactoryReceivesOriginalUpload(): void
    {
        $upload = ['file' => '/uploads/photo.jpg', 'url' => 'https://example.com/uploads/photo.jpg', 'type' => 'image/jpeg'];

        $received = null;

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            function (array $u) use (&$received): AssetRequestContext {
                $received = $u;
                return $this->makeContext($u['file'] ?? '');
            },
        );

        $interceptor->intercept($upload);

        $this->assertSame($upload, $received);
    }

    public function testContextFactoryCalledOnce(): void
    {
        $upload = ['file' => '/uploads/a.pdf', 'url' => '', 'type' => 'application/pdf'];

        $callCount = 0;

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            function (array $u) use (&$callCount): AssetRequestContext {
                $callCount++;
                return $this->makeContext($u['file'] ?? '');
            },
        );

        $interceptor->intercept($upload);

        $this->assertSame(1, $callCount);
    }

    // --- no file move behavior ---

    public function testInterceptDoesNotTouchFilesystem(): void
    {
        $upload = ['file' => '/uploads/nonexistent-file-xyz.pdf', 'url' => '', 'type' => 'application/pdf'];

        $interceptor = new AssetUploadInterceptor(
            $this->makeProtectedPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $result = $interceptor->intercept($upload);

        $this->assertIsString($result['file']);
    }

    // --- WordPressAssetUploadHookRegistrar ---

    public function testRegistrarCallsAddFilterWithDefaultHookAndPriority(): void
    {
        $calls = [];

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $registrar = new WordPressAssetUploadHookRegistrar(
            $interceptor,
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = [$hook, $callback, $priority];
            },
        );

        $registrar->register();

        $this->assertCount(1, $calls);
        $this->assertSame('wp_handle_upload', $calls[0][0]);
        $this->assertSame(10, $calls[0][2]);
    }

    public function testRegistrarPassesInterceptorAsCallback(): void
    {
        $capturedCallback = null;

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $registrar = new WordPressAssetUploadHookRegistrar(
            $interceptor,
            function (string $hook, callable $callback, int $priority) use (&$capturedCallback): void {
                $capturedCallback = $callback;
            },
        );

        $registrar->register();

        $this->assertSame([$interceptor, 'intercept'], $capturedCallback);
    }

    public function testRegistrarSupportsCustomHook(): void
    {
        $calls = [];

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $registrar = new WordPressAssetUploadHookRegistrar(
            $interceptor,
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = $hook;
            },
        );

        $registrar->register('wp_handle_sideload');

        $this->assertSame('wp_handle_sideload', $calls[0]);
    }

    public function testRegistrarSupportsCustomPriority(): void
    {
        $calls = [];

        $interceptor = new AssetUploadInterceptor(
            $this->makePublicPolicy(),
            $this->makeResolver(),
            fn(array $u) => $this->makeContext($u['file'] ?? ''),
        );

        $registrar = new WordPressAssetUploadHookRegistrar(
            $interceptor,
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = $priority;
            },
        );

        $registrar->register('wp_handle_upload', 5);

        $this->assertSame(5, $calls[0]);
    }
}
