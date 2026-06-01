<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAttachmentMetaBridgeHookRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addFilter;

    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /**
     * @param callable(string, callable, int): void $addFilter
     * @param callable(string, callable, int): void $addAction
     */
    public function __construct(
        private readonly AssetAttachmentMetaBridge $bridge,
        callable $addFilter,
        callable $addAction,
    ) {
        $this->addFilter = $addFilter;
        $this->addAction = $addAction;
    }

    public function register(
        string $uploadHook     = 'wp_handle_upload',
        string $attachmentHook = 'add_attachment',
        int    $priority       = 10,
    ): void {
        ($this->addFilter)($uploadHook,     [$this->bridge, 'rememberUpload'],    $priority);
        ($this->addAction)($attachmentHook, [$this->bridge, 'updateAttachment'],  $priority);
    }
}
