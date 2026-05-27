<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessRepairAction;
use Period\WpFramework\WordPress\Access\FilesystemInspectorInterface;
use Period\WpFramework\WordPress\Access\FilesystemRepairPlanner;

final class FilesystemRepairPlannerTest extends TestCase
{
    public function testMissingPrivateRootPlansCreateDirectoryAction(): void
    {
        $plan = (new FilesystemRepairPlanner(
            new RepairPlannerInspector(exists: false),
            '/private-assets',
        ))->plan();

        $this->assertTrue($plan->hasActions());
        $this->assertCount(1, $plan->actions());
        $this->assertSame(AssetAccessRepairAction::TYPE_CREATE_DIRECTORY, $plan->actions()[0]->type());
        $this->assertSame('/private-assets', $plan->actions()[0]->path());
    }

    public function testUnwritablePrivateRootPlansPermissionWarningAction(): void
    {
        $plan = (new FilesystemRepairPlanner(
            new RepairPlannerInspector(exists: true, readable: true, writable: false),
            '/private-assets',
        ))->plan();

        $this->assertCount(1, $plan->actions());
        $this->assertSame(AssetAccessRepairAction::TYPE_PERMISSION_WARNING, $plan->actions()[0]->type());
        $this->assertSame('private asset root is not writable', $plan->actions()[0]->message());
    }

    public function testUnreadablePrivateRootPlansReadabilityWarningAction(): void
    {
        $plan = (new FilesystemRepairPlanner(
            new RepairPlannerInspector(exists: true, readable: false, writable: true),
            '/private-assets',
        ))->plan();

        $this->assertCount(1, $plan->actions());
        $this->assertSame(AssetAccessRepairAction::TYPE_READABILITY_WARNING, $plan->actions()[0]->type());
        $this->assertSame('private asset root is not readable', $plan->actions()[0]->message());
    }

    public function testReadableWritablePrivateRootPlansNoActions(): void
    {
        $plan = (new FilesystemRepairPlanner(
            new RepairPlannerInspector(exists: true, readable: true, writable: true),
            '/private-assets',
        ))->plan();

        $this->assertFalse($plan->hasActions());
        $this->assertSame([], $plan->actions());
    }

    public function testWarningOrderIsDeterministic(): void
    {
        $plan = (new FilesystemRepairPlanner(
            new RepairPlannerInspector(exists: true, readable: false, writable: false),
            '/private-assets',
        ))->plan();

        $types = array_map(
            static fn(AssetAccessRepairAction $action): string => $action->type(),
            $plan->actions(),
        );

        $this->assertSame([
            AssetAccessRepairAction::TYPE_PERMISSION_WARNING,
            AssetAccessRepairAction::TYPE_READABILITY_WARNING,
        ], $types);
    }

    public function testMissingPrivateRootDoesNotCheckPermissions(): void
    {
        $inspector = new RepairPlannerInspector(exists: false);

        (new FilesystemRepairPlanner($inspector, '/private-assets'))->plan();

        $this->assertSame(['exists:/private-assets'], $inspector->calls());
    }

    public function testExistingPrivateRootUsesInspectorOnly(): void
    {
        $inspector = new RepairPlannerInspector(exists: true, readable: true, writable: true);

        (new FilesystemRepairPlanner($inspector, '/private-assets'))->plan();

        $this->assertSame([
            'exists:/private-assets',
            'writable:/private-assets',
            'readable:/private-assets',
        ], $inspector->calls());
    }
}

final class RepairPlannerInspector implements FilesystemInspectorInterface
{
    /** @var string[] */
    private array $calls = [];

    public function __construct(
        private readonly bool $exists,
        private readonly bool $readable = true,
        private readonly bool $writable = true,
    ) {}

    public function exists(string $path): bool
    {
        $this->calls[] = 'exists:' . $path;

        return $this->exists;
    }

    public function isReadable(string $path): bool
    {
        $this->calls[] = 'readable:' . $path;

        return $this->readable;
    }

    public function isWritable(string $path): bool
    {
        $this->calls[] = 'writable:' . $path;

        return $this->writable;
    }

    /** @return string[] */
    public function calls(): array
    {
        return $this->calls;
    }
}
