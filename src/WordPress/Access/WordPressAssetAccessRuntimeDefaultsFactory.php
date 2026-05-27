<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessRuntimeDefaultsFactory
{
    /** @var callable */
    private readonly mixed $uploadsDirResolver;

    /** @var callable */
    private readonly mixed $wpContentDirResolver;

    /** @var callable */
    private readonly mixed $getOption;

    /** @var callable */
    private readonly mixed $updateOption;

    /** @var callable */
    private readonly mixed $getMeta;

    /** @var callable */
    private readonly mixed $updateMeta;

    /** @var callable */
    private readonly mixed $addAction;

    /** @var callable */
    private readonly mixed $addFilter;

    public function __construct(
        callable $uploadsDirResolver,
        callable $wpContentDirResolver,
        ?callable $getOption = null,
        ?callable $updateOption = null,
        ?callable $getMeta = null,
        ?callable $updateMeta = null,
        ?callable $addAction = null,
        ?callable $addFilter = null,
    ) {
        $this->uploadsDirResolver   = $uploadsDirResolver;
        $this->wpContentDirResolver = $wpContentDirResolver;
        $this->getOption            = $getOption    ?? static fn(string $key, mixed $default): mixed => $default;
        $this->updateOption         = $updateOption ?? static fn(string $key, mixed $value): null => null;
        $this->getMeta              = $getMeta      ?? static fn(int $id, string $key, bool $single): mixed => '';
        $this->updateMeta           = $updateMeta   ?? static fn(int $id, string $key, mixed $value): null => null;
        $this->addAction            = $addAction    ?? static fn(mixed ...$args): null => null;
        $this->addFilter            = $addFilter    ?? static fn(mixed ...$args): null => null;
    }

    public function create(): WordPressAssetAccessRuntimeDefaults
    {
        return new WordPressAssetAccessRuntimeDefaults(
            $this->defaultPrivateAssetRoot((string) ($this->wpContentDirResolver)()),
            new NativeFilesystemInspector(),
            $this->getOption,
            $this->updateOption,
            $this->getMeta,
            $this->updateMeta,
            $this->addAction,
            $this->addFilter,
        );
    }

    private function defaultPrivateAssetRoot(string $wpContentDir): ?string
    {
        $normalized = $this->normalizePath($wpContentDir);

        if ($normalized === '') {
            return null;
        }

        return $this->normalizePath(dirname($normalized) . '/private-assets');
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = (string) preg_replace('#/+#', '/', $path);

        return rtrim($path, '/');
    }
}
