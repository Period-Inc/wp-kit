<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessHealthCheckInterface;
use Period\WpKit\WordPress\Access\AssetAccessHealthReporter;
use Period\WpKit\WordPress\Access\AssetAccessHealthStatus;
use Period\WpKit\WordPress\Access\DefaultProtectedAssetPathStrategy;
use Period\WpKit\WordPress\Access\DirectAccessProtectionHealthCheck;
use Period\WpKit\WordPress\Access\DirectAccessProtectionStrategy;
use Period\WpKit\WordPress\Access\OutsideWebrootAssetPathStrategy;
use Period\WpKit\WordPress\Access\OutsideWebrootHealthCheck;

final class AssetAccessHealthCheckTest extends TestCase
{
    public function testHealthStatusHelpers(): void
    {
        $info = AssetAccessHealthStatus::info('ok', 'Everything is fine');
        $warning = AssetAccessHealthStatus::warning('careful', 'Something needs attention');
        $error = AssetAccessHealthStatus::error('broken', 'Something is broken');

        $this->assertTrue($info->healthy());
        $this->assertSame('ok', $info->code());
        $this->assertSame('Everything is fine', $info->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_INFO, $info->severity());
        $this->assertFalse($warning->healthy());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_WARNING, $warning->severity());
        $this->assertFalse($error->healthy());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_ERROR, $error->severity());
    }

    public function testOutsideWebrootInfo(): void
    {
        $statuses = (new OutsideWebrootHealthCheck(
            new OutsideWebrootAssetPathStrategy('/var/private-assets'),
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertTrue($statuses[0]->healthy());
        $this->assertSame('outside_webroot_active', $statuses[0]->code());
        $this->assertSame('outside webroot strategy active', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_INFO, $statuses[0]->severity());
    }

    public function testOutsideWebrootWarning(): void
    {
        $statuses = (new OutsideWebrootHealthCheck(new DefaultProtectedAssetPathStrategy()))->check();

        $this->assertCount(1, $statuses);
        $this->assertFalse($statuses[0]->healthy());
        $this->assertSame('outside_webroot_not_enabled', $statuses[0]->code());
        $this->assertSame('outside webroot not enabled', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_WARNING, $statuses[0]->severity());
    }

    public function testRewriteWarning(): void
    {
        $statuses = (new DirectAccessProtectionHealthCheck(
            DirectAccessProtectionStrategy::rewrite(),
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertFalse($statuses[0]->healthy());
        $this->assertSame('direct_access_rewrite_only', $statuses[0]->code());
        $this->assertSame('protected assets rely on rewrite interception', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_WARNING, $statuses[0]->severity());
    }

    public function testDenyInfo(): void
    {
        $statuses = (new DirectAccessProtectionHealthCheck(
            DirectAccessProtectionStrategy::deny(),
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertTrue($statuses[0]->healthy());
        $this->assertSame('direct_access_deny_enabled', $statuses[0]->code());
        $this->assertSame('direct access deny strategy enabled', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_INFO, $statuses[0]->severity());
    }

    public function testOutsideWebrootStrategyInfo(): void
    {
        $statuses = (new DirectAccessProtectionHealthCheck(
            DirectAccessProtectionStrategy::outsideWebroot(),
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertTrue($statuses[0]->healthy());
        $this->assertSame('direct_access_outside_webroot_enabled', $statuses[0]->code());
        $this->assertSame('outside webroot strategy enabled', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_INFO, $statuses[0]->severity());
    }

    public function testReporterAggregation(): void
    {
        $reporter = new AssetAccessHealthReporter([
            new OutsideWebrootHealthCheck(new OutsideWebrootAssetPathStrategy('/var/private-assets')),
            new DirectAccessProtectionHealthCheck(DirectAccessProtectionStrategy::deny()),
        ]);

        $statuses = $reporter->report();

        $this->assertCount(2, $statuses);
        $this->assertSame('outside_webroot_active', $statuses[0]->code());
        $this->assertSame('direct_access_deny_enabled', $statuses[1]->code());
    }

    public function testReporterOrderIsDeterministic(): void
    {
        $reporter = new AssetAccessHealthReporter([
            new DirectAccessProtectionHealthCheck(DirectAccessProtectionStrategy::rewrite()),
            new OutsideWebrootHealthCheck(new DefaultProtectedAssetPathStrategy()),
            new DirectAccessProtectionHealthCheck(DirectAccessProtectionStrategy::deny()),
        ]);

        $codes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $reporter->report(),
        );

        $this->assertSame([
            'direct_access_rewrite_only',
            'outside_webroot_not_enabled',
            'direct_access_deny_enabled',
        ], $codes);
    }

    public function testReporterFlattensMultiStatusChecks(): void
    {
        $check = new class implements AssetAccessHealthCheckInterface {
            public function check(): array
            {
                return [
                    AssetAccessHealthStatus::info('first', 'first message'),
                    AssetAccessHealthStatus::warning('second', 'second message'),
                ];
            }
        };

        $statuses = (new AssetAccessHealthReporter([$check]))->report();

        $this->assertCount(2, $statuses);
        $this->assertSame('first', $statuses[0]->code());
        $this->assertSame('second', $statuses[1]->code());
    }

    public function testNoRuntimeSideEffects(): void
    {
        $statuses = (new AssetAccessHealthReporter([
            new OutsideWebrootHealthCheck(new OutsideWebrootAssetPathStrategy('/var/private-assets')),
            new DirectAccessProtectionHealthCheck(DirectAccessProtectionStrategy::rewrite()),
        ]))->report();

        $this->assertCount(2, $statuses);
        $this->assertSame('/var/private-assets/file.pdf', (new OutsideWebrootAssetPathStrategy('/var/private-assets'))->protectedPath('uploads/file.pdf'));
    }
}
