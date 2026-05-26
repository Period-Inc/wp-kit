<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessApplicationFactory
{
    /** @var callable */
    private readonly mixed $addAction;

    /** @var callable */
    private readonly mixed $addFilter;

    /** @var callable */
    private readonly mixed $addOptionsPage;

    /** @var callable */
    private readonly mixed $redirect;

    /** @var callable */
    private readonly mixed $adminUrl;

    /** @var callable */
    private readonly mixed $getMeta;

    /** @var callable */
    private readonly mixed $updateMeta;

    /** @var callable */
    private readonly mixed $currentUserCan;

    /** @var callable */
    private readonly mixed $getRoles;

    public function __construct(
        private readonly AssetAccessSettingsRepositoryInterface $settingsRepository,
        private readonly AssetAccessPolicyFactory $policyFactory,
        private readonly AssetStorageInterface $storage,
        private readonly AssetDeliveryInterface $delivery,
        private readonly AssetRequestMatcher $matcher,
        private readonly RequestContextFactoryInterface $requestContextFactory,
        private readonly AssetResponseEmitterInterface $emitter,
        ?callable $addAction = null,
        ?callable $addFilter = null,
        ?callable $addOptionsPage = null,
        ?callable $redirect = null,
        ?callable $adminUrl = null,
        ?callable $getMeta = null,
        ?callable $updateMeta = null,
        ?callable $currentUserCan = null,
        ?callable $getRoles = null,
        private readonly ?AssetUploadPolicyInterface $uploadPolicy = null,
        private readonly ?ProtectedAssetPathStrategyInterface $protectedPathStrategy = null,
        private readonly ?AssetFileMoverInterface $fileMover = null,
        private readonly ?AssetUrlRewriteStrategyInterface $urlRewriteStrategy = null,
        private readonly ?AttachmentFingerprintResolverInterface $fingerprintResolver = null,
    ) {
        $this->addAction      = $addAction      ?? static fn(mixed ...$args): null => null;
        $this->addFilter      = $addFilter      ?? static fn(mixed ...$args): null => null;
        $this->addOptionsPage = $addOptionsPage ?? static fn(mixed ...$args): null => null;
        $this->redirect       = $redirect       ?? static fn(string $url): null => null;
        $this->adminUrl       = $adminUrl       ?? static fn(string $path): string => $path;
        $this->getMeta        = $getMeta        ?? static fn(int $id, string $key, bool $single): mixed => '';
        $this->updateMeta     = $updateMeta     ?? static fn(int $id, string $key, mixed $value): null => null;
        $this->currentUserCan = $currentUserCan ?? static fn(string $capability): bool => false;
        $this->getRoles       = $getRoles       ?? static fn(): array => [];
    }

    public function createManager(): AssetAccessManager
    {
        return (new AssetAccessManagerFactory(
            $this->settingsRepository,
            $this->policyFactory,
        ))->create();
    }

    public function createKernel(): WordPressAssetAccessKernel
    {
        return new WordPressAssetAccessKernel(
            $this->matcher,
            $this->createManager(),
            $this->delivery,
            $this->storage,
        );
    }

    public function createBootstrap(): WordPressAssetAccessBootstrap
    {
        return new WordPressAssetAccessBootstrap(
            $this->createKernel(),
            [$this->requestContextFactory, 'create'],
        );
    }

    /** @param callable(): string $requestUriResolver */
    public function createController(callable $requestUriResolver): WordPressAssetAccessController
    {
        return new WordPressAssetAccessController(
            $this->createBootstrap(),
            $this->emitter,
            $requestUriResolver,
        );
    }

    /** @param callable(): string $requestUriResolver */
    public function createRuntimeInstaller(callable $requestUriResolver): WordPressAssetAccessRuntimeInstaller
    {
        return new WordPressAssetAccessRuntimeInstaller(
            $this,
            $this->addAction,
            $this->addFilter,
            $requestUriResolver,
            $this->createUploadPipelineHookRegistrar(),
            $this->createAttachmentMetaBridgeHookRegistrar(),
            $this->createAttachmentUrlFilterHookRegistrar(),
            $this->createAttachmentDerivedFilterHookRegistrar(),
            $this->createMediaLibraryProtectedColumnHookRegistrar(),
            $this->createMediaLibraryBulkActionHookRegistrar(),
            $this->createAttachmentEditFieldHookRegistrar(),
            $this->createSettingsMenuRegistrar(),
            $this->createSettingsSaveHookRegistrar(),
        );
    }

    public function createUploadPipelineHookRegistrar(): WordPressAssetUploadPipelineHookRegistrar
    {
        return new WordPressAssetUploadPipelineHookRegistrar(
            new AssetUploadPipelineCoordinator(
                new AssetUploadInterceptor(
                    $this->uploadPolicy ?? $this->createPublicUploadPolicy(),
                    new AssetUploadPathResolver($this->protectedPathStrategy ?? $this->createNeutralPathStrategy()),
                    static fn(array $upload): AssetRequestContext => new AssetRequestContext(
                        assetPath: (string) ($upload['file'] ?? ''),
                        assetUrl: (string) ($upload['url'] ?? ''),
                        currentUserId: 0,
                        currentUserRoles: [],
                        requestTime: new \DateTimeImmutable(),
                    ),
                ),
                new AssetUploadMoveProcessor($this->fileMover ?? $this->createNoopFileMover()),
                new AssetUploadUrlRewriteProcessor($this->createUrlRewriteStrategy()),
            ),
            $this->addFilter,
        );
    }

    public function createAttachmentMetaBridgeHookRegistrar(): WordPressAssetAttachmentMetaBridgeHookRegistrar
    {
        return new WordPressAssetAttachmentMetaBridgeHookRegistrar(
            new AssetAttachmentMetaBridge(
                new AssetAttachmentMetaUpdater($this->updateMeta),
                $this->fingerprintResolver ?? $this->createNullFingerprintResolver(),
                new AssetUploadFingerprintGenerator(),
            ),
            $this->addFilter,
            $this->addAction,
        );
    }

    public function createAttachmentUrlFilterHookRegistrar(): WordPressAssetAttachmentUrlFilterHookRegistrar
    {
        return new WordPressAssetAttachmentUrlFilterHookRegistrar(
            new AssetAttachmentUrlFilter($this->getMeta, $this->createUrlRewriteStrategy()),
            $this->addFilter,
        );
    }

    public function createAttachmentDerivedFilterHookRegistrar(): WordPressAssetAttachmentDerivedFilterHookRegistrar
    {
        $reader = $this->createMetaReader();
        $strategy = $this->createUrlRewriteStrategy();

        return new WordPressAssetAttachmentDerivedFilterHookRegistrar(
            new AssetAttachmentImageSrcFilter($reader, $strategy),
            new AssetAttachmentJsPrepareFilter($reader, $strategy),
            $this->addFilter,
        );
    }

    public function createMediaLibraryProtectedColumnHookRegistrar(): WordPressMediaLibraryProtectedColumnHookRegistrar
    {
        return new WordPressMediaLibraryProtectedColumnHookRegistrar(
            new MediaLibraryProtectedColumnProvider(
                new AssetProtectedStateBadgeRenderer($this->createMetaReader()),
            ),
            $this->addFilter,
            $this->addAction,
        );
    }

    public function createMediaLibraryBulkActionHookRegistrar(): WordPressMediaLibraryBulkActionHookRegistrar
    {
        return new WordPressMediaLibraryBulkActionHookRegistrar(
            new AssetBulkProtectionActionProvider(
                new AssetBulkProtectionProcessor($this->updateMeta),
            ),
            $this->addFilter,
            $this->addAction,
        );
    }

    public function createAttachmentEditFieldHookRegistrar(): WordPressAssetAttachmentEditFieldHookRegistrar
    {
        return new WordPressAssetAttachmentEditFieldHookRegistrar(
            new AssetAttachmentEditFieldRenderer($this->createMetaReader()),
            new AssetAttachmentEditFieldSaver($this->updateMeta),
            $this->addFilter,
            $this->addAction,
        );
    }

    public function createSettingsMenuRegistrar(): WordPressAssetAccessSettingsMenuRegistrar
    {
        return new WordPressAssetAccessSettingsMenuRegistrar(
            $this->createSettingsPage(),
            $this->addOptionsPage,
        );
    }

    public function createSettingsSaveHookRegistrar(): WordPressAssetAccessSettingsSaveHookRegistrar
    {
        return new WordPressAssetAccessSettingsSaveHookRegistrar(
            new WordPressAssetAccessSettingsSaveController(
                $this->createSettingsPage(),
                $this->redirect,
                $this->adminUrl,
            ),
            $this->addAction,
        );
    }

    private function createSettingsPage(): WordPressAssetAccessSettingsPage
    {
        return new WordPressAssetAccessSettingsPage(
            $this->settingsRepository,
            new AssetAccessSettingsPageRenderer(),
            new AssetAccessSettingsFormHandler($this->settingsRepository),
            $this->currentUserCan,
            $this->getRoles,
        );
    }

    private function createMetaReader(): AssetAttachmentMetaReader
    {
        return new AssetAttachmentMetaReader($this->getMeta);
    }

    private function createUrlRewriteStrategy(): AssetUrlRewriteStrategyInterface
    {
        return $this->urlRewriteStrategy ?? new class implements AssetUrlRewriteStrategyInterface {
            public function rewrite(string $originalUrl, string $protectedPath): string
            {
                return $originalUrl;
            }
        };
    }

    private function createPublicUploadPolicy(): AssetUploadPolicyInterface
    {
        return new class implements AssetUploadPolicyInterface {
            public function decide(AssetRequestContext $context): AssetUploadDecision
            {
                return AssetUploadDecision::asPublic($context->assetPath());
            }
        };
    }

    private function createNeutralPathStrategy(): ProtectedAssetPathStrategyInterface
    {
        return new class implements ProtectedAssetPathStrategyInterface {
            public function publicPath(string $assetPath): string
            {
                return $assetPath;
            }

            public function protectedPath(string $assetPath): string
            {
                return $assetPath;
            }

            public function isProtected(string $path): bool
            {
                return false;
            }
        };
    }

    private function createNoopFileMover(): AssetFileMoverInterface
    {
        return new class implements AssetFileMoverInterface {
            public function move(string $from, string $to): AssetFileMoveResult
            {
                return AssetFileMoveResult::success($from, $to);
            }
        };
    }

    private function createNullFingerprintResolver(): AttachmentFingerprintResolverInterface
    {
        return new class implements AttachmentFingerprintResolverInterface {
            public function resolve(int $attachmentId): ?string
            {
                return null;
            }
        };
    }
}
