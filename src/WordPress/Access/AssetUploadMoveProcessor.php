<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetUploadMoveProcessor
{
    public function __construct(
        private readonly AssetFileMoverInterface $mover,
    ) {}

    /**
     * @param array<string,mixed> $upload  Upload array with 'file' already rewritten to protected path.
     * @param string              $originalPath  Filesystem path of the file before interception.
     * @return array<string,mixed>
     */
    public function process(array $upload, string $originalPath): array
    {
        $protectedPath = (string) ($upload['file'] ?? '');

        $result = $this->mover->move($originalPath, $protectedPath);

        $upload['asset_move_result'] = $result;

        if (!$result->isSuccess()) {
            $upload['error'] = $result->error();
        }

        // url and attachment meta are intentionally left unchanged here
        return $upload;
    }
}
