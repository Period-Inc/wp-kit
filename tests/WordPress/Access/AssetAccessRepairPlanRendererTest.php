<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessRepairAction;
use Period\WpFramework\WordPress\Access\AssetAccessRepairPlan;
use Period\WpFramework\WordPress\Access\AssetAccessRepairPlanRenderer;
use Period\WpFramework\WordPress\Access\AssetAccessRepairSection;
use Period\WpFramework\WordPress\Access\FilesystemInspectorInterface;
use Period\WpFramework\WordPress\Access\FilesystemRepairPlanner;

final class AssetAccessRepairPlanRendererTest extends TestCase
{
    public function testRendererRendersActions(): void
    {
        $html = (new AssetAccessRepairPlanRenderer())->render(new AssetAccessRepairPlan([
            AssetAccessRepairAction::createDirectory('/private-assets'),
            AssetAccessRepairAction::permissionWarning('/private-assets'),
        ]));

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('create_directory', $html);
        $this->assertStringContainsString('permission_warning', $html);
        $this->assertStringContainsString('/private-assets', $html);
    }

    public function testRendererRendersEmptyState(): void
    {
        $html = (new AssetAccessRepairPlanRenderer())->render(new AssetAccessRepairPlan([]));

        $this->assertSame('<p>No repair actions required.</p>', $html);
    }

    public function testRendererEscapesOutput(): void
    {
        $html = (new AssetAccessRepairPlanRenderer())->render(new AssetAccessRepairPlan([
            new AssetAccessRepairAction(
                '"><script>alert(1)</script>',
                '/tmp/"><script>alert(2)</script>',
                '"><script>alert(3)</script>',
            ),
        ]));

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function testRendererHasNoInlineStyle(): void
    {
        $html = (new AssetAccessRepairPlanRenderer())->render(new AssetAccessRepairPlan([
            AssetAccessRepairAction::readabilityWarning('/private-assets'),
        ]));

        $this->assertStringNotContainsString('style=', $html);
    }

    public function testOutputIsDeterministic(): void
    {
        $plan = new AssetAccessRepairPlan([
            AssetAccessRepairAction::permissionWarning('/private-assets'),
            AssetAccessRepairAction::readabilityWarning('/private-assets'),
        ]);
        $renderer = new AssetAccessRepairPlanRenderer();

        $this->assertSame($renderer->render($plan), $renderer->render($plan));
    }

    public function testRepairSectionDelegatesToPlanner(): void
    {
        $section = new AssetAccessRepairSection(
            new FilesystemRepairPlanner(
                new RepairSectionInspector(exists: false),
                '/private-assets',
            ),
            new AssetAccessRepairPlanRenderer(),
        );

        $this->assertStringContainsString('create_directory', $section->render());
    }
}

final class RepairSectionInspector implements FilesystemInspectorInterface
{
    public function __construct(
        private readonly bool $exists,
        private readonly bool $readable = true,
        private readonly bool $writable = true,
    ) {}

    public function exists(string $path): bool
    {
        return $this->exists;
    }

    public function isReadable(string $path): bool
    {
        return $this->readable;
    }

    public function isWritable(string $path): bool
    {
        return $this->writable;
    }
}
