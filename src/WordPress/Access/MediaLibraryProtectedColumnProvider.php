<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class MediaLibraryProtectedColumnProvider
{
    private const COLUMN_KEY   = 'period_asset_access';
    private const COLUMN_LABEL = 'Asset Access';

    public function __construct(private readonly AssetProtectedStateBadgeRenderer $renderer) {}

    /** @param array<string,string> $columns */
    public function addColumn(array $columns): array
    {
        $columns[self::COLUMN_KEY] = self::COLUMN_LABEL;
        return $columns;
    }

    public function renderColumn(string $column, int $attachmentId): string
    {
        if ($column !== self::COLUMN_KEY) {
            return '';
        }

        return $this->renderer->render($attachmentId);
    }
}
