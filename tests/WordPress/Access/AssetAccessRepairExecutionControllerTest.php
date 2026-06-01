<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAccessRepairAction;
use Period\WpKit\WordPress\Access\AssetAccessRepairExecutionController;
use Period\WpKit\WordPress\Access\AssetAccessRepairExecutionRenderer;
use Period\WpKit\WordPress\Access\AssetAccessRepairRequest;
use Period\WpKit\WordPress\Access\FilesystemInspectorInterface;
use Period\WpKit\WordPress\Access\FilesystemOperatorInterface;
use Period\WpKit\WordPress\Access\FilesystemRepairExecutor;
use Period\WpKit\WordPress\Access\FilesystemRepairPlanner;

final class AssetAccessRepairExecutionControllerTest extends TestCase
{
    public function testControllerBlocksUnconfirmedRequest(): void
    {
        $operator = new ExecutionOperator();
        $results = $this->makeController(
            new AssetAccessRepairRequest(false, 'nonce', true),
            static fn(string $nonce): bool => true,
            $operator,
        )->execute();

        $this->assertSame([], $results);
        $this->assertSame([], $operator->calls());
    }

    public function testControllerBlocksInvalidNonce(): void
    {
        $operator = new ExecutionOperator();
        $results = $this->makeController(
            new AssetAccessRepairRequest(true, 'bad', true),
            static fn(string $nonce): bool => false,
            $operator,
        )->execute();

        $this->assertSame([], $results);
        $this->assertSame([], $operator->calls());
    }

    public function testControllerBlocksInsufficientCapability(): void
    {
        $operator = new ExecutionOperator();
        $results = $this->makeController(
            new AssetAccessRepairRequest(true, 'nonce', false),
            static fn(string $nonce): bool => true,
            $operator,
        )->execute();

        $this->assertSame([], $results);
        $this->assertSame([], $operator->calls());
    }

    public function testControllerExecutesValidRequest(): void
    {
        $operator = new ExecutionOperator();
        $results = $this->makeController(
            new AssetAccessRepairRequest(true, 'nonce', true),
            static fn(string $nonce): bool => $nonce === 'nonce',
            $operator,
        )->execute();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->success());
        $this->assertSame(AssetAccessRepairAction::TYPE_CREATE_DIRECTORY, $results[0]->actionType());
        $this->assertSame(['createDirectory:/private-assets'], $operator->calls());
    }

    public function testRendererRendersResults(): void
    {
        $results = $this->makeController(
            new AssetAccessRepairRequest(true, 'nonce', true),
            static fn(string $nonce): bool => true,
            new ExecutionOperator(),
        )->execute();

        $html = (new AssetAccessRepairExecutionRenderer())->render($results);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('success', $html);
        $this->assertStringContainsString('create_directory', $html);
        $this->assertStringContainsString('/private-assets', $html);
    }

    public function testRendererRendersEmptyState(): void
    {
        $html = (new AssetAccessRepairExecutionRenderer())->render([]);

        $this->assertSame('<p>No repair actions executed.</p>', $html);
    }

    public function testRendererEscapesOutput(): void
    {
        $html = (new AssetAccessRepairExecutionRenderer())->render([
            new \Period\WpKit\WordPress\Access\AssetAccessRepairExecutionResult(
                false,
                '"><script>alert(1)</script>',
                '/tmp/"><script>alert(2)</script>',
                '"><script>alert(3)</script>',
            ),
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;&lt;script&gt;', $html);
    }

    public function testRendererHasNoInlineStyle(): void
    {
        $html = (new AssetAccessRepairExecutionRenderer())->render([]);

        $this->assertStringNotContainsString('style=', $html);
    }

    public function testDeterministicOutput(): void
    {
        $renderer = new AssetAccessRepairExecutionRenderer();
        $results = $this->makeController(
            new AssetAccessRepairRequest(true, 'nonce', true),
            static fn(string $nonce): bool => true,
            new ExecutionOperator(),
        )->execute();

        $this->assertSame($renderer->render($results), $renderer->render($results));
    }

    /** @param callable(string): bool $nonceVerifier */
    private function makeController(
        AssetAccessRepairRequest $request,
        callable $nonceVerifier,
        ExecutionOperator $operator,
    ): AssetAccessRepairExecutionController {
        return new AssetAccessRepairExecutionController(
            new FilesystemRepairPlanner(
                new ExecutionInspector(exists: false),
                '/private-assets',
            ),
            new FilesystemRepairExecutor($operator),
            $nonceVerifier,
            $request,
        );
    }
}

final class ExecutionInspector implements FilesystemInspectorInterface
{
    public function __construct(private readonly bool $exists) {}

    public function exists(string $path): bool
    {
        return $this->exists;
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

final class ExecutionOperator implements FilesystemOperatorInterface
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
