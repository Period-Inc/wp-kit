<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAttachmentEditFieldHookRegistrar
{
    /** @var callable */
    private readonly mixed $addFilter;

    /** @var callable */
    private readonly mixed $addAction;

    public function __construct(
        private readonly AssetAttachmentEditFieldRenderer $renderer,
        private readonly AssetAttachmentEditFieldSaver $saver,
        callable $addFilter,
        callable $addAction,
    ) {
        $this->addFilter = $addFilter;
        $this->addAction = $addAction;
    }

    public function register(): void
    {
        ($this->addFilter)(
            'attachment_fields_to_edit',
            function (array $formFields, object $post): array {
                $formFields['period_asset_access'] = [
                    'label' => 'Asset Access',
                    'input' => 'html',
                    'html'  => $this->renderer->render((int) $post->ID),
                ];
                return $formFields;
            },
            10,
            2,
        );

        ($this->addFilter)(
            'attachment_fields_to_save',
            function (array $post, array $attachment): array {
                $this->saver->save((int) $post['ID'], $attachment);
                return $post;
            },
            10,
            2,
        );
    }
}
