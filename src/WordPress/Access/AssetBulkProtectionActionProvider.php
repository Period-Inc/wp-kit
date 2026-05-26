<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetBulkProtectionActionProvider
{
    private const ACTION_PROTECT   = 'period_protect_assets';
    private const ACTION_UNPROTECT = 'period_unprotect_assets';

    public function __construct(private readonly AssetBulkProtectionProcessor $processor) {}

    /** @param array<string,string> $actions */
    public function registerActions(array $actions): array
    {
        $actions[self::ACTION_PROTECT]   = 'Protect Assets';
        $actions[self::ACTION_UNPROTECT] = 'Unprotect Assets';
        return $actions;
    }

    /** @param array<mixed> $attachmentIds */
    public function handleAction(string $action, array $attachmentIds): AssetBulkProtectionResult
    {
        return match ($action) {
            self::ACTION_PROTECT   => $this->processor->process($attachmentIds, true),
            self::ACTION_UNPROTECT => $this->processor->process($attachmentIds, false),
            default                => AssetBulkProtectionResult::noop($action),
        };
    }
}
