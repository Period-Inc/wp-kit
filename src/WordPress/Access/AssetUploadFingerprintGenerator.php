<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetUploadFingerprintGenerator
{
    /** @param array<string,mixed> $upload */
    public function generate(array $upload): string
    {
        $parts = [
            (string) ($upload['file'] ?? ''),
            (string) ($upload['url']  ?? ''),
            (string) ($upload['type'] ?? ''),
        ];

        if (array_key_exists('filesize', $upload)) {
            $parts[] = (string) $upload['filesize'];
        }

        return hash('sha256', implode("\0", $parts));
    }
}
