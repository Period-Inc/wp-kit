<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NativeHtaccessWriter implements HtaccessWriterInterface
{
    private const MARKER_BEGIN = '# BEGIN PeriodAssetAccess';
    private const MARKER_END   = '# END PeriodAssetAccess';

    public function write(string $path, string $rules): HtaccessWriteResult
    {
        if (file_exists($path) && !is_writable($path)) {
            return HtaccessWriteResult::failure($path, 'File is not writable: ' . $path);
        }

        $existing      = file_exists($path) ? (string) file_get_contents($path) : '';
        $backupCreated = false;

        if (file_exists($path)) {
            $backupPath = $path . '.bak';
            if (file_put_contents($backupPath, $existing) === false) {
                return HtaccessWriteResult::failure($path, 'Failed to create backup: ' . $backupPath);
            }
            $backupCreated = true;
        }

        $block      = self::MARKER_BEGIN . "\n" . $rules . "\n" . self::MARKER_END;
        $newContent = $this->integrate($existing, $block);

        if (file_put_contents($path, $newContent) === false) {
            return HtaccessWriteResult::failure($path, 'Failed to write: ' . $path);
        }

        return HtaccessWriteResult::success($path, true, $backupCreated);
    }

    private function integrate(string $existing, string $block): string
    {
        if (str_contains($existing, self::MARKER_BEGIN)) {
            $pattern  = '/' . preg_quote(self::MARKER_BEGIN, '/') . '.*?' . preg_quote(self::MARKER_END, '/') . '/s';
            $replaced = preg_replace($pattern, $block, $existing);
            return $replaced ?? $existing;
        }

        if ($existing === '') {
            return $block . "\n";
        }

        return rtrim($existing) . "\n\n" . $block . "\n";
    }
}
