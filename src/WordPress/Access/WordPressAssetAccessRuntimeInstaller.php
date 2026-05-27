<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessRuntimeInstaller
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /** @var callable */
    private readonly mixed $addFilter;

    /** @var callable(): string */
    private readonly mixed $requestUriResolver;

    /**
     * @param callable(string, callable, int): void $addAction
     * @param callable                             $addFilter
     * @param callable(): string                   $requestUriResolver
     */
    public function __construct(
        private readonly WordPressAssetAccessApplicationFactory $factory,
        callable $addAction,
        callable $addFilter,
        callable $requestUriResolver,
        private readonly ?WordPressAssetUploadPipelineHookRegistrar $uploadPipelineHookRegistrar = null,
        private readonly ?WordPressAssetAttachmentMetaBridgeHookRegistrar $attachmentMetaBridgeHookRegistrar = null,
        private readonly ?WordPressAssetAttachmentUrlFilterHookRegistrar $attachmentUrlFilterHookRegistrar = null,
        private readonly ?WordPressAssetAttachmentDerivedFilterHookRegistrar $attachmentDerivedFilterHookRegistrar = null,
        private readonly ?WordPressMediaLibraryProtectedColumnHookRegistrar $mediaLibraryProtectedColumnHookRegistrar = null,
        private readonly ?WordPressMediaLibraryBulkActionHookRegistrar $mediaLibraryBulkActionHookRegistrar = null,
        private readonly ?WordPressAssetAttachmentEditFieldHookRegistrar $attachmentEditFieldHookRegistrar = null,
        private readonly ?WordPressAssetAccessSettingsMenuRegistrar $settingsMenuRegistrar = null,
        private readonly ?WordPressAssetAccessSettingsSaveHookRegistrar $settingsSaveHookRegistrar = null,
        private readonly ?AssetAccessHealthReporter $healthReporter = null,
    ) {
        $this->addAction          = $addAction;
        $this->addFilter          = $addFilter;
        $this->requestUriResolver = $requestUriResolver;
    }

    public function install(): void
    {
        $controller = $this->factory->createController($this->requestUriResolver);

        (new WordPressAssetAccessHookRegistrar(
            $controller,
            $this->addAction,
        ))->register('init', 0);

        $this->uploadPipelineHookRegistrar?->register();
        $this->attachmentMetaBridgeHookRegistrar?->register();
        $this->attachmentUrlFilterHookRegistrar?->register();
        $this->attachmentDerivedFilterHookRegistrar?->register();
        $this->mediaLibraryProtectedColumnHookRegistrar?->register();
        $this->mediaLibraryBulkActionHookRegistrar?->register();
        $this->attachmentEditFieldHookRegistrar?->register();
        $this->settingsMenuRegistrar?->register();
        $this->settingsSaveHookRegistrar?->register();
    }

    public function healthReport(): array
    {
        return $this->healthReporter?->report() ?? [];
    }
}
