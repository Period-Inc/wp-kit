<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessHealthCheckInterface;
use Period\WpKit\WordPress\Access\AssetAccessHealthReporter;
use Period\WpKit\WordPress\Access\AssetAccessHealthSettingsSection;
use Period\WpKit\WordPress\Access\AssetAccessHealthStatus;
use Period\WpKit\WordPress\Access\AssetAccessHealthStatusRenderer;

final class AssetAccessHealthStatusRendererTest extends TestCase
{
    public function testTableRendering(): void
    {
        $html = (new AssetAccessHealthStatusRenderer())->render([
            AssetAccessHealthStatus::warning('direct_access_rewrite_only', 'protected assets rely on rewrite interception'),
        ]);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('warning', $html);
        $this->assertStringContainsString('direct_access_rewrite_only', $html);
        $this->assertStringContainsString('protected assets rely on rewrite interception', $html);
    }

    public function testEmptyStatusRendering(): void
    {
        $html = (new AssetAccessHealthStatusRenderer())->render([]);

        $this->assertSame('<p>No health issues reported.</p>', $html);
    }

    public function testSeverityCodeAndMessageAreEscaped(): void
    {
        $html = (new AssetAccessHealthStatusRenderer())->render([
            AssetAccessHealthStatus::info(
                '<strong>info</strong>',
                '<script>alert("message")</script>',
            ),
        ]);

        $this->assertStringContainsString('&lt;strong&gt;info&lt;/strong&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;message&quot;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<strong>info</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testSectionDelegatesToReporter(): void
    {
        $calls = 0;
        $reporter = new AssetAccessHealthReporter([
            new class($calls) implements AssetAccessHealthCheckInterface {
                public function __construct(private int &$calls) {}

                public function check(): array
                {
                    $this->calls++;
                    return [AssetAccessHealthStatus::info('runtime_ok', 'runtime ok')];
                }
            },
        ]);
        $section = new AssetAccessHealthSettingsSection(
            $reporter,
            new AssetAccessHealthStatusRenderer(),
        );

        $html = $section->render();

        $this->assertSame(1, $calls);
        $this->assertStringContainsString('runtime_ok', $html);
    }

    public function testInlineStyleIsNotIncluded(): void
    {
        $html = (new AssetAccessHealthStatusRenderer())->render([
            AssetAccessHealthStatus::error('broken', 'broken message'),
        ]);

        $this->assertStringNotContainsString('style=', $html);
    }
}
