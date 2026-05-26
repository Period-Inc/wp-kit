<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressMediaLibraryProtectedColumnHookRegistrar
{
    /** @var callable */
    private readonly mixed $addFilter;

    /** @var callable */
    private readonly mixed $addAction;

    public function __construct(
        private readonly MediaLibraryProtectedColumnProvider $provider,
        callable $addFilter,
        callable $addAction,
    ) {
        $this->addFilter = $addFilter;
        $this->addAction = $addAction;
    }

    public function register(): void
    {
        ($this->addFilter)('manage_upload_columns', [$this->provider, 'addColumn'], 10);

        ($this->addAction)(
            'manage_media_custom_column',
            function (string $column, int $attachmentId): void {
                echo $this->provider->renderColumn($column, $attachmentId);
            },
            10,
            2,
        );
    }
}
