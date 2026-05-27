<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessHealthReporter;
use Period\WpFramework\WordPress\Access\AssetAccessHealthSettingsSection;
use Period\WpFramework\WordPress\Access\AssetAccessHealthStatus;
use Period\WpFramework\WordPress\Access\AssetAccessManager;
use Period\WpFramework\WordPress\Access\AssetAccessPolicyFactory;
use Period\WpFramework\WordPress\Access\AssetAccessSettingsRepositoryInterface;
use Period\WpFramework\WordPress\Access\AssetDeliveryInterface;
use Period\WpFramework\WordPress\Access\AssetDeliveryResult;
use Period\WpFramework\WordPress\Access\AssetEmitResult;
use Period\WpFramework\WordPress\Access\AssetRequestContext;
use Period\WpFramework\WordPress\Access\AssetRequestMatcher;
use Period\WpFramework\WordPress\Access\AssetResponseEmitterInterface;
use Period\WpFramework\WordPress\Access\AssetStorageInterface;
use Period\WpFramework\WordPress\Access\AssetStorageItem;
use Period\WpFramework\WordPress\Access\CallableAssetAccessSettingsRepository;
use Period\WpFramework\WordPress\Access\DirectAccessProtectionStrategy;
use Period\WpFramework\WordPress\Access\FilesystemInspectorInterface;
use Period\WpFramework\WordPress\Access\OutsideWebrootAssetPathStrategy;
use Period\WpFramework\WordPress\Access\ProtectedAssetPathStrategyInterface;
use Period\WpFramework\WordPress\Access\RequestContextFactoryInterface;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessApplicationFactory;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessBootstrap;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessController;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessKernel;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeInstaller;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsMenuRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessSettingsSaveHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentDerivedFilterHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentEditFieldHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentMetaBridgeHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentUrlFilterHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressAssetUploadPipelineHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressMediaLibraryBulkActionHookRegistrar;
use Period\WpFramework\WordPress\Access\WordPressMediaLibraryProtectedColumnHookRegistrar;

final class WordPressAssetAccessApplicationFactoryTest extends TestCase
{
    private function makeRepository(int &$getCalls = 0): AssetAccessSettingsRepositoryInterface
    {
        $stored = [
            'enabled'            => true,
            'protected_roles'    => ['subscriber'],
            'default_visibility' => 'private',
        ];

        return new CallableAssetAccessSettingsRepository(
            function (string $key, mixed $default) use (&$getCalls, $stored): mixed {
                $getCalls++;
                return $key === 'period_asset_access_settings' ? $stored : $default;
            },
            fn(string $key, mixed $value): null => null,
        );
    }

    private function makeStorage(): AssetStorageInterface
    {
        return new class implements AssetStorageInterface {
            public function find(string $assetPath): ?AssetStorageItem
            {
                if ($assetPath !== '/wp-content/uploads/file.pdf') {
                    return null;
                }

                return new AssetStorageItem($assetPath, null, null, null, null);
            }
        };
    }

    private function makeDelivery(int &$deliverCalls = 0): AssetDeliveryInterface
    {
        return new class($deliverCalls) implements AssetDeliveryInterface {
            public function __construct(private int &$deliverCalls) {}

            public function deliver(AssetRequestContext $context): AssetDeliveryResult
            {
                $this->deliverCalls++;

                return AssetDeliveryResult::ok(200);
            }
        };
    }

    private function makeRequestContextFactory(int &$createCalls = 0): RequestContextFactoryInterface
    {
        return new class($createCalls) implements RequestContextFactoryInterface {
            public function __construct(private int &$createCalls) {}

            public function create(string $requestUri): AssetRequestContext
            {
                $this->createCalls++;
                $path = explode('?', $requestUri, 2)[0];

                return new AssetRequestContext(
                    assetPath: $path,
                    assetUrl: 'https://example.test' . $path,
                    currentUserId: 1,
                    currentUserRoles: ['subscriber'],
                    requestTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
                );
            }
        };
    }

    private function makeEmitter(int &$emitCalls = 0): AssetResponseEmitterInterface
    {
        return new class($emitCalls) implements AssetResponseEmitterInterface {
            public function __construct(private int &$emitCalls) {}

            public function emit(AssetDeliveryResult $result): AssetEmitResult
            {
                $this->emitCalls++;

                return new AssetEmitResult(true, $result->statusCode(), [], null, null);
            }
        };
    }

    private function makeFactory(
        int &$getCalls = 0,
        int &$createContextCalls = 0,
        int &$deliverCalls = 0,
        int &$emitCalls = 0,
        ?callable $addAction = null,
        ?callable $addFilter = null,
        ?callable $addOptionsPage = null,
        ?callable $currentUserCan = null,
        ?ProtectedAssetPathStrategyInterface $protectedPathStrategy = null,
        ?DirectAccessProtectionStrategy $directAccessProtectionStrategy = null,
        ?FilesystemInspectorInterface $filesystemInspector = null,
        ?string $privateAssetRoot = null,
    ): WordPressAssetAccessApplicationFactory {
        return new WordPressAssetAccessApplicationFactory(
            $this->makeRepository($getCalls),
            new AssetAccessPolicyFactory(),
            $this->makeStorage(),
            $this->makeDelivery($deliverCalls),
            new AssetRequestMatcher(['/wp-content/uploads/']),
            $this->makeRequestContextFactory($createContextCalls),
            $this->makeEmitter($emitCalls),
            $addAction,
            $addFilter,
            $addOptionsPage,
            currentUserCan: $currentUserCan,
            protectedPathStrategy: $protectedPathStrategy,
            directAccessProtectionStrategy: $directAccessProtectionStrategy,
            filesystemInspector: $filesystemInspector,
            privateAssetRoot: $privateAssetRoot,
        );
    }

    private function makeFilesystemInspector(bool $exists = true, bool $readable = true): FilesystemInspectorInterface
    {
        return new class($exists, $readable) implements FilesystemInspectorInterface {
            public function __construct(
                private readonly bool $exists,
                private readonly bool $readable,
            ) {}

            public function exists(string $path): bool
            {
                return $this->exists;
            }

            public function isReadable(string $path): bool
            {
                return $this->readable;
            }

            public function isWritable(string $path): bool
            {
                return true;
            }
        };
    }

    public function testCreateManagerReturnsAssetAccessManager(): void
    {
        $manager = $this->makeFactory()->createManager();

        $this->assertInstanceOf(AssetAccessManager::class, $manager);
    }

    public function testCreateKernelReturnsWordPressAssetAccessKernel(): void
    {
        $kernel = $this->makeFactory()->createKernel();

        $this->assertInstanceOf(WordPressAssetAccessKernel::class, $kernel);
    }

    public function testCreateBootstrapReturnsWordPressAssetAccessBootstrap(): void
    {
        $bootstrap = $this->makeFactory()->createBootstrap();

        $this->assertInstanceOf(WordPressAssetAccessBootstrap::class, $bootstrap);
    }

    public function testCreateControllerReturnsWordPressAssetAccessController(): void
    {
        $controller = $this->makeFactory()->createController(
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $this->assertInstanceOf(WordPressAssetAccessController::class, $controller);
    }

    public function testCreateRuntimeInstallerReturnsWordPressAssetAccessRuntimeInstaller(): void
    {
        $installer = $this->makeFactory()->createRuntimeInstaller(
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $this->assertInstanceOf(WordPressAssetAccessRuntimeInstaller::class, $installer);
    }

    public function testCreateHealthReporterReturnsReporter(): void
    {
        $reporter = $this->makeFactory()->createHealthReporter();

        $this->assertInstanceOf(AssetAccessHealthReporter::class, $reporter);
    }

    public function testCreateHealthSettingsSectionReturnsSection(): void
    {
        $section = $this->makeFactory()->createHealthSettingsSection();

        $this->assertInstanceOf(AssetAccessHealthSettingsSection::class, $section);
    }

    public function testHealthReporterContainsExpectedChecks(): void
    {
        $reporter = $this->makeFactory(
            protectedPathStrategy: new OutsideWebrootAssetPathStrategy('/var/private-assets'),
            directAccessProtectionStrategy: DirectAccessProtectionStrategy::deny(),
            filesystemInspector: $this->makeFilesystemInspector(),
            privateAssetRoot: '/var/private-assets',
        )->createHealthReporter();

        $codes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $reporter->report(),
        );

        $this->assertSame([
            'direct_access_deny_enabled',
            'outside_webroot_active',
            'private_asset_root_exists',
        ], $codes);
    }

    public function testHealthReporterOrderIsDeterministic(): void
    {
        $reporter = $this->makeFactory(
            filesystemInspector: $this->makeFilesystemInspector(exists: true, readable: false),
            privateAssetRoot: '/var/private-assets',
        )->createHealthReporter();

        $codes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $reporter->report(),
        );

        $this->assertSame([
            'direct_access_rewrite_only',
            'outside_webroot_not_enabled',
            'private_asset_root_exists',
            'private_asset_root_not_readable',
        ], $codes);
    }

    public function testHealthReporterContainsFilesystemCheckWhenDependenciesExist(): void
    {
        $reporter = $this->makeFactory(
            filesystemInspector: $this->makeFilesystemInspector(exists: false),
            privateAssetRoot: '/var/private-assets',
        )->createHealthReporter();

        $codes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $reporter->report(),
        );

        $this->assertSame([
            'direct_access_rewrite_only',
            'outside_webroot_not_enabled',
            'private_asset_root_missing',
        ], $codes);
    }

    public function testHealthReporterOmitsFilesystemCheckWhenDependenciesAreMissing(): void
    {
        $withoutInspector = $this->makeFactory(
            privateAssetRoot: '/var/private-assets',
        )->createHealthReporter();
        $withoutRoot = $this->makeFactory(
            filesystemInspector: $this->makeFilesystemInspector(),
        )->createHealthReporter();

        $withoutInspectorCodes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $withoutInspector->report(),
        );
        $withoutRootCodes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $withoutRoot->report(),
        );

        $this->assertSame([
            'direct_access_rewrite_only',
            'outside_webroot_not_enabled',
        ], $withoutInspectorCodes);
        $this->assertSame($withoutInspectorCodes, $withoutRootCodes);
    }

    public function testEachRegistrarFactoryReturnsExpectedClass(): void
    {
        $factory = $this->makeFactory();

        $this->assertInstanceOf(
            WordPressAssetUploadPipelineHookRegistrar::class,
            $factory->createUploadPipelineHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAttachmentMetaBridgeHookRegistrar::class,
            $factory->createAttachmentMetaBridgeHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAttachmentUrlFilterHookRegistrar::class,
            $factory->createAttachmentUrlFilterHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAttachmentDerivedFilterHookRegistrar::class,
            $factory->createAttachmentDerivedFilterHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressMediaLibraryProtectedColumnHookRegistrar::class,
            $factory->createMediaLibraryProtectedColumnHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressMediaLibraryBulkActionHookRegistrar::class,
            $factory->createMediaLibraryBulkActionHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAttachmentEditFieldHookRegistrar::class,
            $factory->createAttachmentEditFieldHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAccessSettingsMenuRegistrar::class,
            $factory->createSettingsMenuRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAccessSettingsSaveHookRegistrar::class,
            $factory->createSettingsSaveHookRegistrar(),
        );
    }

    public function testFactoryReturnsNewInstancesEachTime(): void
    {
        $factory = $this->makeFactory();

        $this->assertNotSame($factory->createManager(), $factory->createManager());
        $this->assertNotSame($factory->createKernel(), $factory->createKernel());
        $this->assertNotSame($factory->createBootstrap(), $factory->createBootstrap());
        $this->assertNotSame(
            $factory->createController(fn(): string => '/wp-content/uploads/file.pdf'),
            $factory->createController(fn(): string => '/wp-content/uploads/file.pdf'),
        );
        $this->assertNotSame(
            $factory->createRuntimeInstaller(fn(): string => '/wp-content/uploads/file.pdf'),
            $factory->createRuntimeInstaller(fn(): string => '/wp-content/uploads/file.pdf'),
        );
        $this->assertNotSame(
            $factory->createHealthReporter(),
            $factory->createHealthReporter(),
        );
        $this->assertNotSame(
            $factory->createUploadPipelineHookRegistrar(),
            $factory->createUploadPipelineHookRegistrar(),
        );
        $this->assertNotSame(
            $factory->createSettingsSaveHookRegistrar(),
            $factory->createSettingsSaveHookRegistrar(),
        );
    }

    public function testSettingsRepositoryAndPolicyFactoryAreUsed(): void
    {
        $getCalls = 0;
        $manager = $this->makeFactory(getCalls: $getCalls)->createManager();

        $allowed = $manager->authorize(new AssetRequestContext(
            assetPath: '/wp-content/uploads/file.pdf',
            assetUrl: 'https://example.test/wp-content/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: ['subscriber'],
            requestTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
        ));
        $denied = $manager->authorize(new AssetRequestContext(
            assetPath: '/wp-content/uploads/file.pdf',
            assetUrl: 'https://example.test/wp-content/uploads/file.pdf',
            currentUserId: 1,
            currentUserRoles: ['editor'],
            requestTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
        ));

        $this->assertGreaterThanOrEqual(1, $getCalls);
        $this->assertTrue($allowed->allowed());
        $this->assertFalse($denied->allowed());
    }

    public function testCreatedControllerConnectsRuntimeParts(): void
    {
        $createContextCalls = 0;
        $deliverCalls = 0;
        $emitCalls = 0;
        $controller = $this->makeFactory(
            createContextCalls: $createContextCalls,
            deliverCalls: $deliverCalls,
            emitCalls: $emitCalls,
        )->createController(fn(): string => '/wp-content/uploads/file.pdf?size=large');

        $result = $controller->handle();

        $this->assertInstanceOf(AssetEmitResult::class, $result);
        $this->assertSame(1, $createContextCalls);
        $this->assertSame(1, $deliverCalls);
        $this->assertSame(1, $emitCalls);
    }

    public function testWordPressFunctionsAreNotCalledDirectly(): void
    {
        $controller = $this->makeFactory()->createController(
            fn(): string => '/wp-content/uploads/file.pdf',
        );

        $this->assertInstanceOf(WordPressAssetAccessController::class, $controller);
    }

    public function testCreateRuntimeInstallerIncludesNonNullRegistrarsWhenDependenciesExist(): void
    {
        $actions = [];
        $filters = [];
        $optionsPages = [];
        $factory = $this->makeFactory(
            addAction: function (string $hook, callable $callback, int $priority) use (&$actions): void {
                $actions[] = [$hook, $priority];
            },
            addFilter: function (string $hook, callable $callback, int $priority) use (&$filters): void {
                $filters[] = [$hook, $priority];
            },
            addOptionsPage: function () use (&$optionsPages): void {
                $optionsPages[] = func_get_args();
            },
        );

        $installer = $factory->createRuntimeInstaller(
            fn(): string => '/wp-content/uploads/file.pdf',
        );
        $installer->install();

        $actionHooks = array_column($actions, 0);
        $filterHooks = array_column($filters, 0);

        $this->assertContains('init', $actionHooks);
        $this->assertContains('add_attachment', $actionHooks);
        $this->assertContains('manage_media_custom_column', $actionHooks);
        $this->assertContains('admin_post_period_asset_access_save', $actionHooks);
        $this->assertContains('wp_handle_upload', $filterHooks);
        $this->assertContains('wp_get_attachment_url', $filterHooks);
        $this->assertContains('wp_get_attachment_image_src', $filterHooks);
        $this->assertContains('manage_upload_columns', $filterHooks);
        $this->assertContains('bulk_actions-upload', $filterHooks);
        $this->assertContains('attachment_fields_to_edit', $filterHooks);
        $this->assertCount(1, $optionsPages);
    }

    public function testFactoryWiresHealthSectionIntoSettingsPageMenuPath(): void
    {
        $calls = [];
        $factory = $this->makeFactory(
            addOptionsPage: function () use (&$calls): void {
                $calls[] = func_get_args();
            },
            currentUserCan: fn(string $cap): bool => $cap === 'manage_options',
        );
        $registrar = $factory->createSettingsMenuRegistrar();

        $registrar->register();

        $callback = $calls[0][4];
        ob_start();
        $callback();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('direct_access_rewrite_only', $output);
        $this->assertStringContainsString('outside_webroot_not_enabled', $output);
    }

    public function testMissingOptionalDependenciesDoNotBreakFactory(): void
    {
        $factory = $this->makeFactory();

        $this->assertInstanceOf(
            WordPressAssetAccessRuntimeInstaller::class,
            $factory->createRuntimeInstaller(fn(): string => '/wp-content/uploads/file.pdf'),
        );
        $this->assertInstanceOf(
            WordPressAssetUploadPipelineHookRegistrar::class,
            $factory->createUploadPipelineHookRegistrar(),
        );
        $this->assertInstanceOf(
            WordPressAssetAccessSettingsMenuRegistrar::class,
            $factory->createSettingsMenuRegistrar(),
        );
    }
}
