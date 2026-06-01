<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetUploadPolicyInterface
{
    public function decide(AssetRequestContext $context): AssetUploadDecision;
}
