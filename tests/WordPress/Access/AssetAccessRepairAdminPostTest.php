<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessRepairAdminPostAction;
use Period\WpKit\WordPress\Access\AssetAccessRepairAdminPostRegistrar;
use Period\WpKit\WordPress\Access\AssetAccessRepairExecutionController;
use Period\WpKit\WordPress\Access\AssetAccessRepairRequest;
use Period\WpKit\WordPress\Access\FilesystemInspectorInterface;
use Period\WpKit\WordPress\Access\FilesystemOperatorInterface;
use Period\WpKit\WordPress\Access\FilesystemRepairExecutor;
use Period\WpKit\WordPress\Access\FilesystemRepairPlanner;

final class AssetAccessRepairAdminPostTest extends TestCase
{
    public function testAdminPostActionDelegatesToController(): void
    {
        $operator = new AdminPostOperator();
        $action = new AssetAccessRepairAdminPostAction(
            'period_asset_access_repair_execute',
            $this->makeController($operator),
            static fn(): string => '/wp-admin/options-general.php?page=period-asset-access',
        );

        $action->handle();

        $this->assertSame(['createDirectory:/private-assets'], $operator->calls());
    }

    public function testRedirectUrlReturned(): void
    {
        $action = new AssetAccessRepairAdminPostAction(
            'period_asset_access_repair_execute',
            $this->makeController(new AdminPostOperator()),
            static fn(): string => '/redirect-target',
        );

        $this->assertSame('/redirect-target', $action->handle());
    }

    public function testRegistrarRegistersAdminPostHook(): void
    {
        $calls = [];
        $registrar = new AssetAccessRepairAdminPostRegistrar(
            function (string $hook, callable $callback, int $priority) use (&$calls): void {
                $calls[] = [$hook, $callback, $priority];
            },
            new AssetAccessRepairAdminPostAction(
                'period_asset_access_repair_execute',
                $this->makeController(new AdminPostOperator()),
                static fn(): string => '/redirect-target',
            ),
        );

        $registrar->register();

        $this->assertSame('admin_post_period_asset_access_repair_execute', $calls[0][0]);
        $this->assertSame(10, $calls[0][2]);
        $this->assertIsCallable($calls[0][1]);
    }

    public function testDeterministicHookName(): void
    {
        $action = new AssetAccessRepairAdminPostAction(
            'period_asset_access_repair_execute',
            $this->makeController(new AdminPostOperator()),
            static fn(): string => '/redirect-target',
        );

        $this->assertSame('period_asset_access_repair_execute', $action->actionName());
    }

    private function makeController(AdminPostOperator $operator): AssetAccessRepairExecutionController
    {
        return new AssetAccessRepairExecutionController(
            new FilesystemRepairPlanner(
                new AdminPostInspector(),
                '/private-assets',
            ),
            new FilesystemRepairExecutor($operator),
            static fn(string $nonce): bool => true,
            new AssetAccessRepairRequest(true, 'nonce', true),
        );
    }
}

final class AdminPostInspector implements FilesystemInspectorInterface
{
    public function exists(string $path): bool
    {
        return false;
    }

    public function isReadable(string $path): bool
    {
        return true;
    }

    public function isWritable(string $path): bool
    {
        return true;
    }
}

final class AdminPostOperator implements FilesystemOperatorInterface
{
    /** @var string[] */
    private array $calls = [];

    public function createDirectory(string $path): bool
    {
        $this->calls[] = 'createDirectory:' . $path;

        return true;
    }

    public function setPermissions(string $path, int $mode): bool
    {
        $this->calls[] = 'setPermissions:' . $path . ':' . $mode;

        return true;
    }

    /** @return string[] */
    public function calls(): array
    {
        return $this->calls;
    }
}
