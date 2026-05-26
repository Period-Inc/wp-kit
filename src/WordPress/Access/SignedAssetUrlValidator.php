<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class SignedAssetUrlValidator
{
    public function __construct(
        private readonly string $secretKey,
    ) {}

    public function validate(string $url, \DateTimeImmutable $now): AssetAccessResult
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (!is_string($query)) {
            return AssetAccessResult::deny('Invalid signature');
        }

        parse_str($query, $params);

        $assetPath = isset($params['asset'])     ? (string) $params['asset']     : null;
        $expires   = isset($params['expires'])   ? (int)    $params['expires']   : null;
        $signature = isset($params['signature']) ? (string) $params['signature'] : null;

        if ($assetPath === null || $expires === null || $signature === null) {
            return AssetAccessResult::deny('Invalid signature');
        }

        if ($now->getTimestamp() > $expires) {
            return AssetAccessResult::deny('Expired');
        }

        $expected = hash_hmac('sha256', $assetPath . '|' . $expires, $this->secretKey);

        if (!hash_equals($expected, $signature)) {
            return AssetAccessResult::deny('Invalid signature');
        }

        return AssetAccessResult::allow();
    }
}
