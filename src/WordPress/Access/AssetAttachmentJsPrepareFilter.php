<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentJsPrepareFilter
{
    public function __construct(
        private readonly AssetAttachmentMetaReader $reader,
        private readonly AssetUrlRewriteStrategyInterface $strategy,
    ) {}

    /**
     * @param array<string,mixed>  $response
     * @param array<string,mixed>  $meta
     * @return array<string,mixed>
     */
    public function filter(array $response, object $attachment, array $meta): array
    {
        $attachmentId = (int) $attachment->ID;
        $assetMeta    = $this->reader->read($attachmentId);

        if (!$assetMeta->isProtected()) {
            return $response;
        }

        $protectedPath = $assetMeta->protectedPath();

        if ($protectedPath === null || $protectedPath === '') {
            return $response;
        }

        if (isset($response['url'])) {
            $response['url'] = $this->strategy->rewrite((string) $response['url'], $protectedPath);
        }

        if (isset($response['sizes']) && is_array($response['sizes'])) {
            foreach ($response['sizes'] as $sizeName => $sizeData) {
                if (is_array($sizeData) && isset($sizeData['url'])) {
                    $response['sizes'][$sizeName]['url'] = $this->strategy->rewrite(
                        (string) $sizeData['url'],
                        $protectedPath,
                    );
                }
            }
        }

        return $response;
    }
}
