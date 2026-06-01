<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAccessRuntimeDefaults
{
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
        private readonly ?string $privateAssetRoot,
        private readonly ?FilesystemInspectorInterface $filesystemInspector,
        callable $getOption,
        callable $updateOption,
        callable $getMeta,
        callable $updateMeta,
        callable $addAction,
        callable $addFilter,
    ) {
        $this->getOption    = $getOption;
        $this->updateOption = $updateOption;
        $this->getMeta      = $getMeta;
        $this->updateMeta   = $updateMeta;
        $this->addAction    = $addAction;
        $this->addFilter    = $addFilter;
    }

    public function privateAssetRoot(): ?string
    {
        return $this->privateAssetRoot;
    }

    public function filesystemInspector(): ?FilesystemInspectorInterface
    {
        return $this->filesystemInspector;
    }

    public function getOption(): callable
    {
        return $this->getOption;
    }

    public function updateOption(): callable
    {
        return $this->updateOption;
    }

    public function getMeta(): callable
    {
        return $this->getMeta;
    }

    public function updateMeta(): callable
    {
        return $this->updateMeta;
    }

    public function addAction(): callable
    {
        return $this->addAction;
    }

    public function addFilter(): callable
    {
        return $this->addFilter;
    }
}
