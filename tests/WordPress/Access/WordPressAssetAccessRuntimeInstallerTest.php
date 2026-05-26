<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessPolicyFactory;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsFormHandler;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsPageRenderer;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpFramework\WordPress\Access\AssetAttachmentEditFieldRenderer;
use Period\WpFramework\WordPress\Access\AssetAttachmentEditFieldSaver;
use Period\WpFramework\WordPress\Access\AssetAttachmentImageSrcFilter;
use Period\WpFramework\WordPress\Access\AssetAttachmentJsPrepareFilter;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaBridge;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaReader;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaUpdater;
use Period\WpFramework\WordPress\Access\AssetAttachmentUrlFilter;
use Period\WpFramework\WordPress\Access\AssetBulkProtectionActionProvider;
use Period\WpFramework\WordPress\Access\AssetBulkProtectionProcessor;
use Period\WpFramework\WordPress\Access\AssetDeliveryInterface;
use Period\WpFramework\WordPress\Access\AssetDeliveryResult;
use Period\WpFramework\WordPress\Access\AssetEmitResult;
use Period\WpFramework\WordPress\Access\AssetFileMoveResult;
use Period\WpFramework\WordPress\Access\AssetFileMoverInterface;
use Period\WpFramework\WordPress\Access\AssetProtectedStateBadgeRenderer;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\AssetRequestMatcher;
use Period\WpFramework\WordPress\Access\AssetResponseEmitterInterface;
use Period\WpFramework\WordPress\Access\AssetStorageInterface;
use Period\WpFramework\WordPress\Access\AssetStorageItem;
use Period\WpFramework\WordPress\Access\AssetUploadDecision;
use Period\WpFramework\WordPress\Access\AssetUploadFingerprintGenerator;
use Period\WpFramework\WordPress\Access\AssetUploadInterceptor;
use Period\WpFramework\WordPress\Access\AssetUploadMoveProcessor;
use Period\WpFramework\WordPress\Access\AssetUploadPathResolver;
use Period\WpFramework\WordPress\Access\AssetUploadPipelineCoordinator;
use Period\WpFramework\WordPress\Access\AssetUploadPolicyInterface;
use Period\WpFramework\WordPress\Access\AssetUploadUrlRewriteProcessor;
use Period\WpFramework\WordPress\Access\AssetUrlRewriteStrategyInterface;
use Period\WpFramework\WordPress\Access\AttachmentFingerprintResolverInterface;
use Period\WpFramework\WordPress\Access\CallableAssetAccessSettingsRepository;
use Period\WpFramework\WordPress\Access\MediaLibraryProtectedColumnProvider;
use Period\WpFramework\WordPress\Access\ProtectedAssetPathStrategyInterface;
use Period\WpFramework\WordPress\Access\RequestContextFactoryInterface;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessApplicationFactory;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessController;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeInstaller;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsMenuRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsPage;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsSaveController;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsSaveHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentDerivedFilterHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentEditFieldHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentMetaBridgeHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentUrlFilterHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetUploadPipelineHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressMediaLibraryBulkActionHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressMediaLibraryProtectedColumnHookRegistrar;

final class WordPressAssetAccessRuntimeInstallerTest extends TestCase
{
    private function makeRepository(): AssetAccessSettingsRepositoryInterface
    {
        return new CallableAssetAccessSettingsRepository(
            fn(string $key, mixed $default): mixed => [
                'enabled'            => false,
                'protected_roles'    => [],
                'default_visibility' => 'public',
            ],
            fn(string $key, mixed $value): null => null,
        );
    }

    private function makeStorage(): AssetStorageInterface
    {
        return new class implements AssetStorageInterface {
            public function find(string $assetPath): ?AssetStorageItem
            {
                return new AssetStorageItem($assetPath, null, null, null, null);
            }
        };
    }

    private function makeDelivery(): AssetDeliveryInterface
    {
        return new class implements AssetDeliveryInterface {
            public function deliver(AssetRequestContext $context): AssetDeliveryResult
            {
                return AssetDeliveryResult::ok(200);
            }
        };
    }

    private function makeRequestContextFactory(): RequestContextFactoryInterface
    {
        return new class implements RequestContextFactoryInterface {
            public function create(string $requestUri): AssetRequestContext
            {
                $path = explode('?', $requestUri, 2)[0];

                return new AssetRequestContext(
                    assetPath: $path,
                    assetUrl: 'https://example.test' . $path,
                    currentUserId: 0,
                    currentUserRoles: [],
                    requestTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
                );
            }
        };
    }

    private function makeEmitter(): AssetResponseEmitterInterface
    {
        return new class implements AssetResponseEmitterInterface {
            public function emit(AssetDeliveryResult $result): AssetEmitResult
            {
                return new AssetEmitResult(true, $result->statusCode(), [], null, null);
            }
        };
    }

    private function makeFactory(): WordPressAssetAccessApplicationFactory
    {
        return new WordPressAssetAccessApplicationFactory(
            $this->makeRepository(),
            new AssetAccessPolicyFactory(),
            $this->makeStorage(),
            $this->makeDelivery(),
            new AssetRequestMatcher(['/wp-content/uploads/']),
            $this->makeRequestContextFactory(),
            $this->makeEmitter(),
        );
    }

    private function makeRewriteStrategy(): AssetUrlRewriteStrategyInterface
    {
        return new class implements AssetUrlRewriteStrategyInterface {
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                return $originalUrl . '?protected=' . rawurlencode($protectedPath);
            }
        };
    }

    private function makePathStrategy(): ProtectedAssetPathStrategyInterface
    {
        return new class implements ProtectedAssetPathStrategyInterface {
            public function publicPath(string $assetPath): string
            {
                return $assetPath;
            }

            public function protectedPath(string $assetPath): string
            {
                return '/protected' . $assetPath;
            }

            public function isProtected(string $path): bool
            {
                return str_starts_with($path, '/protected/');
            }
        };
    }

    /** @return array<string,mixed> */
    private function makeOptionalRegistrars(array &$order): array
    {
        $metaReader      = new AssetAttachmentMetaReader(fn(int $id, string $key, bool $single): mixed => '');
        $rewriteStrategy = $this->makeRewriteStrategy();
        $settingsRepo    = $this->makeRepository();

        $uploadPipeline = new WordPressAssetUploadPipelineHookRegistrar(
            new AssetUploadPipelineCoordinator(
                new AssetUploadInterceptor(
                    new class implements AssetUploadPolicyInterface {
                        public function decide(AssetRequestContext $context): AssetUploadDecision
                        {
                            return AssetUploadDecision::asPublic($context->assetPath());
                        }
                    },
                    new AssetUploadPathResolver($this->makePathStrategy()),
                    fn(array $upload): AssetRequestContext => new AssetRequestContext(
                        assetPath: (string) ($upload['file'] ?? ''),
                        assetUrl: (string) ($upload['url'] ?? ''),
                        currentUserId: 0,
                        currentUserRoles: [],
                        requestTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
                    ),
                ),
                new AssetUploadMoveProcessor(new class implements AssetFileMoverInterface {
                    public function move(string $from, string $to): AssetFileMoveResult
                    {
                        return AssetFileMoveResult::success($from, $to);
                    }
                }),
                new AssetUploadUrlRewriteProcessor($rewriteStrategy),
            ),
            function () use (&$order): void {
                $order[] = 'upload-pipeline';
            },
        );

        $metaBridge = new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            new AssetAttachmentMetaBridge(
                new AssetAttachmentMetaUpdater(fn(int $id, string $key, mixed $value): null => null),
                new class implements AttachmentFingerprintResolverInterface {
                    public function resolve(int $attachmentId): ?string
                    {
                        return null;
                    }
                },
                new AssetUploadFingerprintGenerator(),
            ),
            function () use (&$order): void {
                $order[] = 'meta-bridge-filter';
            },
            function () use (&$order): void {
                $order[] = 'meta-bridge-action';
            },
        );

        $urlFilter = new WordPressAssetAttachmentUrlFilterHookRegistrar(
            new AssetAttachmentUrlFilter(fn(int $id, string $key, bool $single): mixed => '', $rewriteStrategy),
            function () use (&$order): void {
                $order[] = 'url-filter';
            },
        );

        $derivedFilter = new WordPressAssetAttachmentDerivedFilterHookRegistrar(
            new AssetAttachmentImageSrcFilter($metaReader, $rewriteStrategy),
            new AssetAttachmentJsPrepareFilter($metaReader, $rewriteStrategy),
            function () use (&$order): void {
                $order[] = 'derived-filter';
            },
        );

        $protectedColumn = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            new MediaLibraryProtectedColumnProvider(new AssetProtectedStateBadgeRenderer($metaReader)),
            function () use (&$order): void {
                $order[] = 'protected-column-filter';
            },
            function () use (&$order): void {
                $order[] = 'protected-column-action';
            },
        );

        $bulkAction = new WordPressMediaLibraryBulkActionHookRegistrar(
            new AssetBulkProtectionActionProvider(
                new AssetBulkProtectionProcessor(fn(int $id, string $key, mixed $value): null => null),
            ),
            function () use (&$order): void {
                $order[] = 'bulk-action-filter';
            },
            function () use (&$order): void {
                $order[] = 'bulk-action-action';
            },
        );

        $editField = new WordPressAssetAttachmentEditFieldHookRegistrar(
            new AssetAttachmentEditFieldRenderer($metaReader),
            new AssetAttachmentEditFieldSaver(fn(int $id, string $key, mixed $value): null => null),
            function () use (&$order): void {
                $order[] = 'edit-field-filter';
            },
            function () use (&$order): void {
                $order[] = 'edit-field-action';
            },
        );

        $settingsPage = new WordPressAssetAccessSettingsPage(
            $settingsRepo,
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($settingsRepo),
            fn(string $cap): bool => $cap === 'manage_options',
            fn(): array => [],
        );

        $settingsMenu = new WordPressAssetAccessSettingsMenuRegistrar(
            $settingsPage,
            function () use (&$order): void {
                $order[] = 'settings-menu';
            },
        );

        $settingsSave = new WordPressAssetAccessSettingsSaveHookRegistrar(
            new WordPressAssetAccessSettingsSaveController(
                $settingsPage,
                fn(string $url): null => null,
                fn(string $path): string => $path,
            ),
            function () use (&$order): void {
                $order[] = 'settings-save';
            },
        );

        return [
            'uploadPipelineHookRegistrar' => $uploadPipeline,
            'attachmentMetaBridgeHookRegistrar' => $metaBridge,
            'attachmentUrlFilterHookRegistrar' => $urlFilter,
            'attachmentDerivedFilterHookRegistrar' => $derivedFilter,
            'mediaLibraryProtectedColumnHookRegistrar' => $protectedColumn,
            'mediaLibraryBulkActionHookRegistrar' => $bulkAction,
            'attachmentEditFieldHookRegistrar' => $editField,
            'settingsMenuRegistrar' => $settingsMenu,
            'settingsSaveHookRegistrar' => $settingsSave,
        ];
    }

    public function testInstallerCreatesController(): void
    {
        $callback = null;
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $hook, callable $cb, int $priority) use (&$callback): void {
                $callback = $cb;
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $installer->install();

        $this->assertIsArray($callback);
        $this->assertInstanceOf(WordPressAssetAccessController::class, $callback[0]);
        $this->assertSame('handle', $callback[1]);
    }

    public function testInstallerRegistersInitHook(): void
    {
        $hook = null;
        $priority = null;
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $registeredHook, callable $callback, int $registeredPriority) use (&$hook, &$priority): void {
                $hook = $registeredHook;
                $priority = $registeredPriority;
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $installer->install();

        $this->assertSame('init', $hook);
        $this->assertSame(0, $priority);
    }

    public function testInjectedAddActionIsUsed(): void
    {
        $calls = [];
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = [$hook, $callback, $priority];
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $installer->install();

        $this->assertCount(1, $calls);
    }

    public function testAddActionIsNotCalledDirectly(): void
    {
        $called = false;
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function () use (&$called): void {
                $called = true;
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $installer->install();

        $this->assertTrue($called, 'Injected addAction callable must be used.');
    }

    public function testInstallCanBeCalledWithoutSideEffectsExceptHookRegistration(): void
    {
        $actionCalls = 0;
        $filterCalls = 0;
        $requestUriCalls = 0;
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $hook, callable $callback, int $priority) use (&$actionCalls): void {
                $actionCalls++;
            },
            function () use (&$filterCalls): void {
                $filterCalls++;
            },
            function () use (&$requestUriCalls): string {
                $requestUriCalls++;
                return '/wp-content/uploads/file.pdf';
            },
        );

        $installer->install();

        $this->assertSame(1, $actionCalls);
        $this->assertSame(0, $filterCalls);
        $this->assertSame(0, $requestUriCalls);
    }

    public function testNullOptionalRegistrarsAreIgnored(): void
    {
        $order = [];
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $hook, callable $callback, int $priority) use (&$order): void {
                $order[] = $hook . ':' . $priority;
            },
            function () use (&$order): void {
                $order[] = 'filter';
            },
            fn(): string => '/wp-content/uploads/file.pdf',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $installer->install();

        $this->assertSame(['init:0'], $order);
    }

    public function testEachProvidedOptionalRegistrarIsRegistered(): void
    {
        $order = [];
        $optional = $this->makeOptionalRegistrars($order);
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function (string $hook, callable $callback, int $priority) use (&$order): void {
                $order[] = 'request-access';
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
            $optional['uploadPipelineHookRegistrar'],
            $optional['attachmentMetaBridgeHookRegistrar'],
            $optional['attachmentUrlFilterHookRegistrar'],
            $optional['attachmentDerivedFilterHookRegistrar'],
            $optional['mediaLibraryProtectedColumnHookRegistrar'],
            $optional['mediaLibraryBulkActionHookRegistrar'],
            $optional['attachmentEditFieldHookRegistrar'],
            $optional['settingsMenuRegistrar'],
            $optional['settingsSaveHookRegistrar'],
        );

        $installer->install();

        $this->assertContains('upload-pipeline', $order);
        $this->assertContains('meta-bridge-filter', $order);
        $this->assertContains('meta-bridge-action', $order);
        $this->assertContains('url-filter', $order);
        $this->assertContains('derived-filter', $order);
        $this->assertContains('protected-column-filter', $order);
        $this->assertContains('protected-column-action', $order);
        $this->assertContains('bulk-action-filter', $order);
        $this->assertContains('edit-field-filter', $order);
        $this->assertContains('settings-menu', $order);
        $this->assertContains('settings-save', $order);
    }

    public function testRegisterOrderIsDeterministic(): void
    {
        $order = [];
        $optional = $this->makeOptionalRegistrars($order);
        $installer = new WordPressAssetAccessRuntimeInstaller(
            $this->makeFactory(),
            function () use (&$order): void {
                $order[] = 'request-access';
            },
            fn(): null => null,
            fn(): string => '/wp-content/uploads/file.pdf',
            $optional['uploadPipelineHookRegistrar'],
            $optional['attachmentMetaBridgeHookRegistrar'],
            $optional['attachmentUrlFilterHookRegistrar'],
            $optional['attachmentDerivedFilterHookRegistrar'],
            $optional['mediaLibraryProtectedColumnHookRegistrar'],
            $optional['mediaLibraryBulkActionHookRegistrar'],
            $optional['attachmentEditFieldHookRegistrar'],
            $optional['settingsMenuRegistrar'],
            $optional['settingsSaveHookRegistrar'],
        );

        $installer->install();

        $this->assertSame([
            'request-access',
            'upload-pipeline',
            'meta-bridge-filter',
            'meta-bridge-action',
            'url-filter',
            'derived-filter',
            'derived-filter',
            'protected-column-filter',
            'protected-column-action',
            'bulk-action-filter',
            'bulk-action-filter',
            'edit-field-filter',
            'edit-field-filter',
            'settings-menu',
            'settings-save',
        ], $order);
    }
}
