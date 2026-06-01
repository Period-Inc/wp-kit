<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class SignedAssetUrlGenerator
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $baseUrl,
    ) {}

    public function generate(string $assetPath, \DateTimeImmutable $expiresAt): string
    {
        $expires   = $expiresAt->getTimestamp();
        $signature = $this->sign($assetPath, $expires);

        return $this->baseUrl
            . '?asset='     . rawurlencode($assetPath)
            . '&expires='   . $expires
            . '&signature=' . rawurlencode($signature);
    }

    private function sign(string $assetPath, int $expires): string
    {
        return hash_hmac('sha256', $assetPath . '|' . $expires, $this->secretKey);
    }
}
