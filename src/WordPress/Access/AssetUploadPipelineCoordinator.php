<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetUploadPipelineCoordinator
{
    public function __construct(
        private readonly AssetUploadInterceptor $interceptor,
        private readonly AssetUploadMoveProcessor $moveProcessor,
        private readonly AssetUploadUrlRewriteProcessor $urlRewriteProcessor,
    ) {}

    /**
     * @param array<string,mixed> $upload
     * @return array<string,mixed>
     */
    public function process(array $upload): array
    {
        if (!empty($upload['error'])) {
            return $upload;
        }

        $originalPath = (string) ($upload['file'] ?? '');

        $upload = $this->interceptor->intercept($upload);

        if (($upload['file'] ?? '') === $originalPath) {
            return $upload;
        }

        $upload = $this->moveProcessor->process($upload, $originalPath);

        if (isset($upload['error'])) {
            return $upload;
        }

        return $this->urlRewriteProcessor->process($upload);
    }
}
