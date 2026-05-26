<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAttachmentDerivedFilterHookRegistrar
{
    /** @var callable */
    private readonly mixed $addFilter;

    public function __construct(
        private readonly AssetAttachmentImageSrcFilter $imageSrcFilter,
        private readonly AssetAttachmentJsPrepareFilter $jsPrepareFilter,
        callable $addFilter,
    ) {
        $this->addFilter = $addFilter;
    }

    public function register(): void
    {
        ($this->addFilter)('wp_get_attachment_image_src', [$this->imageSrcFilter, 'filter'], 10, 4);
        ($this->addFilter)('wp_prepare_attachment_for_js', [$this->jsPrepareFilter, 'filter'], 10, 3);
    }
}
