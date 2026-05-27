<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessRepairAction;
use Period\WpFramework\WordPress\Access\AssetAccessRepairExecutionResult;
use Period\WpFramework\WordPress\Access\AssetAccessRepairPlan;
use Period\WpFramework\WordPress\Access\FilesystemOperatorInterface;
use Period\WpFramework\WordPress\Access\FilesystemRepairExecutor;
use Period\WpFramework\WordPress\Access\NativeFilesystemOperator;

final class FilesystemRepairExecutorTest extends TestCase
{
    public function testCreateDirectoryExecution(): void
    {
        $operator = new RecordingFilesystemOperator(createDirectoryResult: true);
        $results = (new FilesystemRepairExecutor($operator))->execute(new AssetAccessRepairPlan([
            AssetAccessRepairAction::createDirectory('/private-assets'),
        ]));

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->success());
        $this->assertSame(AssetAccessRepairAction::TYPE_CREATE_DIRECTORY, $results[0]->actionType());
        $this->assertSame('/private-assets', $results[0]->path());
        $this->assertSame('create directory executed', $results[0]->message());
        $this->assertSame(['createDirectory:/private-assets'], $operator->calls());
    }

    public function testCreateDirectoryFailureResult(): void
    {
        $results = (new FilesystemRepairExecutor(
            new RecordingFilesystemOperator(createDirectoryResult: false),
        ))->execute(new AssetAccessRepairPlan([
            AssetAccessRepairAction::createDirectory('/private-assets'),
        ]));

        $this->assertFalse($results[0]->success());
    }

    public function testInformationalWarningHandlingDoesNotCallOperator(): void
    {
        $operator = new RecordingFilesystemOperator();
        $results = (new FilesystemRepairExecutor($operator))->execute(new AssetAccessRepairPlan([
            AssetAccessRepairAction::permissionWarning('/private-assets'),
            AssetAccessRepairAction::readabilityWarning('/private-assets'),
        ]));

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->success());
        $this->assertTrue($results[1]->success());
        $this->assertSame('permission warning requires manual review', $results[0]->message());
        $this->assertSame('readability warning requires manual review', $results[1]->message());
        $this->assertSame([], $operator->calls());
    }

    public function testUnknownActionHandling(): void
    {
        $results = (new FilesystemRepairExecutor(new RecordingFilesystemOperator()))->execute(new AssetAccessRepairPlan([
            new AssetAccessRepairAction('unknown_action', '/private-assets', 'unknown'),
        ]));

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->success());
        $this->assertSame('unknown_action', $results[0]->actionType());
        $this->assertSame('unknown repair action', $results[0]->message());
    }

    public function testDeterministicResultOrder(): void
    {
        $results = (new FilesystemRepairExecutor(new RecordingFilesystemOperator()))->execute(new AssetAccessRepairPlan([
            AssetAccessRepairAction::permissionWarning('/private-assets'),
            AssetAccessRepairAction::createDirectory('/private-assets'),
            AssetAccessRepairAction::readabilityWarning('/private-assets'),
        ]));

        $types = array_map(
            static fn(AssetAccessRepairExecutionResult $result): string => $result->actionType(),
            $results,
        );

        $this->assertSame([
            AssetAccessRepairAction::TYPE_PERMISSION_WARNING,
            AssetAccessRepairAction::TYPE_CREATE_DIRECTORY,
            AssetAccessRepairAction::TYPE_READABILITY_WARNING,
        ], $types);
    }

    public function testNativeFilesystemOperatorCreatesDirectory(): void
    {
        $path = sys_get_temp_dir() . '/pwf-access-repair-' . bin2hex(random_bytes(6));
        $operator = new NativeFilesystemOperator();

        try {
            $this->assertTrue($operator->createDirectory($path));
            $this->assertDirectoryExists($path);
        } finally {
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }
}

final class RecordingFilesystemOperator implements FilesystemOperatorInterface
{
    /** @var string[] */
    private array $calls = [];

    public function __construct(private readonly bool $createDirectoryResult = true) {}

    public function createDirectory(string $path): bool
    {
        $this->calls[] = 'createDirectory:' . $path;

        return $this->createDirectoryResult;
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
