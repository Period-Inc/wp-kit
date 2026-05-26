<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetUploadInterceptor
{
    /** @var callable(array<string,mixed>): AssetRequestContext */
    private readonly mixed $contextFactory;

    /**
     * @param callable(array<string,mixed>): AssetRequestContext $contextFactory
     */
    public function __construct(
        private readonly AssetUploadPolicyInterface $policy,
        private readonly AssetUploadPathResolver $pathResolver,
        callable $contextFactory,
    ) {
        $this->contextFactory = $contextFactory;
    }

    /**
     * @param array<string,mixed> $upload
     * @return array<string,mixed>
     */
    public function intercept(array $upload): array
    {
        $context  = ($this->contextFactory)($upload);
        $decision = $this->policy->decide($context);

        if (!$decision->isProtected()) {
            return $upload;
        }

        $originalPath   = $upload['file'] ?? '';
        $upload['file'] = $this->pathResolver->resolve((string) $originalPath, true);

        // url is intentionally left unchanged — no file move is performed here.
        // URL rewriting is the responsibility of the layer that executes the actual move.

        return $upload;
    }
}
