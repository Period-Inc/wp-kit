<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessManagerFactory
{
    public function __construct(
        private readonly AssetAccessSettingsRepositoryInterface $repository,
        private readonly AssetAccessPolicyFactory $policyFactory,
    ) {}

    public function create(): AssetAccessManager
    {
        $settings = $this->repository->get();
        $policy   = $this->policyFactory->create($settings);

        return new AssetAccessManager($policy);
    }
}
