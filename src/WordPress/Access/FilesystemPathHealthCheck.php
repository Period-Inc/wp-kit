<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class FilesystemPathHealthCheck implements AssetAccessHealthCheckInterface
{
    public function __construct(
        private readonly FilesystemInspectorInterface $inspector,
        private readonly string $path,
        private readonly string $label,
    ) {}

    public function check(): array
    {
        $codePrefix = $this->codePrefix();

        if (!$this->inspector->exists($this->path)) {
            return [
                AssetAccessHealthStatus::error(
                    $codePrefix . '_missing',
                    $this->label . ' missing',
                ),
            ];
        }

        $statuses = [
            AssetAccessHealthStatus::info(
                $codePrefix . '_exists',
                $this->label . ' exists',
            ),
        ];

        if (!$this->inspector->isReadable($this->path)) {
            $statuses[] = AssetAccessHealthStatus::warning(
                $codePrefix . '_not_readable',
                $this->label . ' is not readable',
            );
        }

        return $statuses;
    }

    private function codePrefix(): string
    {
        $normalized = strtolower(trim($this->label));
        $normalized = (string) preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'path';
    }
}
