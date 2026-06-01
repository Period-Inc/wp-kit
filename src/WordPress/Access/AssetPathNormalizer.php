<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetPathNormalizer
{
    public function normalize(string $path): string
    {
        // Collapse duplicate slashes
        $path = (string) preg_replace('#/+#', '/', $path);

        // Ensure leading slash
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // Resolve . and .. segments
        $segments   = explode('/', $path);
        $normalized = [];

        foreach ($segments as $segment) {
            if ($segment === '..') {
                if (!empty($normalized)) {
                    array_pop($normalized);
                }
            } elseif ($segment !== '.' && $segment !== '') {
                $normalized[] = $segment;
            }
        }

        return '/' . implode('/', $normalized);
    }
}
