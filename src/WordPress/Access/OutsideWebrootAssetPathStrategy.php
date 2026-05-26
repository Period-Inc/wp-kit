<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class OutsideWebrootAssetPathStrategy implements ProtectedAssetPathStrategyInterface
{
    private readonly string $privateRootPath;
    private readonly string $publicUploadsPrefix;
    private readonly AssetPathNormalizer $normalizer;

    public function __construct(string $privateRootPath, string $publicUploadsPrefix = 'uploads')
    {
        $this->normalizer           = new AssetPathNormalizer();
        $this->privateRootPath      = rtrim($this->normalizer->normalize($privateRootPath), '/');
        $this->publicUploadsPrefix  = trim($this->normalizer->normalize($publicUploadsPrefix), '/');
    }

    public function publicPath(string $assetPath): string
    {
        $relativePath = $this->relativePathOutsidePrivateRoot($assetPath);

        if ($relativePath === '') {
            return $this->publicUploadsPrefix;
        }

        return $this->publicUploadsPrefix . '/' . $relativePath;
    }

    public function protectedPath(string $assetPath): string
    {
        $relativePath = $this->relativePathOutsidePublicPrefix($assetPath);

        if ($relativePath === '') {
            return $this->privateRootPath;
        }

        return $this->privateRootPath . '/' . $relativePath;
    }

    public function isProtected(string $path): bool
    {
        $normalized = rtrim($this->normalizer->normalize($path), '/');

        return $normalized === $this->privateRootPath
            || str_starts_with($normalized, $this->privateRootPath . '/');
    }

    private function relativePathOutsidePrivateRoot(string $assetPath): string
    {
        $normalized = $this->normalizer->normalize($assetPath);

        if ($normalized === $this->privateRootPath) {
            return '';
        }

        if (str_starts_with($normalized, $this->privateRootPath . '/')) {
            return substr($normalized, strlen($this->privateRootPath) + 1);
        }

        return $this->relativePathOutsidePublicPrefix($assetPath);
    }

    private function relativePathOutsidePublicPrefix(string $assetPath): string
    {
        $relative = ltrim($this->normalizer->normalize($assetPath), '/');

        if ($relative === $this->publicUploadsPrefix) {
            return '';
        }

        $prefix = $this->publicUploadsPrefix . '/';
        if (str_starts_with($relative, $prefix)) {
            return substr($relative, strlen($prefix));
        }

        return $relative;
    }
}
