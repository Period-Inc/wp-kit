<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetFileMoveResult;
use Period\WpFramework\WordPress\Access\AssetFileMoverInterface;
use Period\WpFramework\WordPress\Access\AssetUploadMoveProcessor;
use Period\WpFramework\WordPress\Access\NativeAssetFileMover;

final class AssetFileMoveTest extends TestCase
{
    // -----------------------------------------------------------------------
    // AssetFileMoveResult
    // -----------------------------------------------------------------------

    public function testSuccessResultIsSuccess(): void
    {
        $result = AssetFileMoveResult::success('/from/a.pdf', '/to/a.pdf');

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->error());
    }

    public function testSuccessResultStoresPaths(): void
    {
        $result = AssetFileMoveResult::success('/from/a.pdf', '/to/a.pdf');

        $this->assertSame('/from/a.pdf', $result->from());
        $this->assertSame('/to/a.pdf', $result->to());
    }

    public function testFailureResultIsNotSuccess(): void
    {
        $result = AssetFileMoveResult::failure('/from/a.pdf', '/to/a.pdf', 'something went wrong');

        $this->assertFalse($result->isSuccess());
    }

    public function testFailureResultStoresError(): void
    {
        $result = AssetFileMoveResult::failure('/from/a.pdf', '/to/a.pdf', 'something went wrong');

        $this->assertSame('something went wrong', $result->error());
    }

    public function testFailureResultStoresPaths(): void
    {
        $result = AssetFileMoveResult::failure('/from/a.pdf', '/to/a.pdf', 'err');

        $this->assertSame('/from/a.pdf', $result->from());
        $this->assertSame('/to/a.pdf', $result->to());
    }

    // -----------------------------------------------------------------------
    // NativeAssetFileMover — real temp-dir tests
    // -----------------------------------------------------------------------

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/pwf_mover_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testMoveSucceeds(): void
    {
        $from = $this->tmpDir . '/source.pdf';
        $to   = $this->tmpDir . '/dest/source.pdf';

        file_put_contents($from, 'content');

        $mover  = new NativeAssetFileMover();
        $result = $mover->move($from, $to);

        $this->assertTrue($result->isSuccess());
        $this->assertFileExists($to);
        $this->assertFileDoesNotExist($from);
    }

    public function testMoveSuccessStoresPaths(): void
    {
        $from = $this->tmpDir . '/a.pdf';
        $to   = $this->tmpDir . '/moved/a.pdf';

        file_put_contents($from, 'x');

        $result = (new NativeAssetFileMover())->move($from, $to);

        $this->assertSame($from, $result->from());
        $this->assertSame($to, $result->to());
    }

    public function testMoveCreatesDestinationDirectory(): void
    {
        $from = $this->tmpDir . '/file.pdf';
        $to   = $this->tmpDir . '/nested/deep/dir/file.pdf';

        file_put_contents($from, 'data');

        (new NativeAssetFileMover())->move($from, $to);

        $this->assertFileExists($to);
    }

    public function testMoveFailsWhenSourceMissing(): void
    {
        $from = $this->tmpDir . '/nonexistent.pdf';
        $to   = $this->tmpDir . '/dest.pdf';

        $result = (new NativeAssetFileMover())->move($from, $to);

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->error());
    }

    public function testMoveFailsWhenDestinationEmpty(): void
    {
        $from = $this->tmpDir . '/file.pdf';
        file_put_contents($from, 'data');

        $result = (new NativeAssetFileMover())->move($from, '');

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->error());
    }

    public function testMoveImplementsInterface(): void
    {
        $this->assertInstanceOf(AssetFileMoverInterface::class, new NativeAssetFileMover());
    }

    // -----------------------------------------------------------------------
    // AssetUploadMoveProcessor — fake mover, no real file IO
    // -----------------------------------------------------------------------

    private function makeFakeMover(bool $succeeds, string $error = ''): AssetFileMoverInterface
    {
        return new class($succeeds, $error) implements AssetFileMoverInterface {
            public ?string $capturedFrom = null;
            public ?string $capturedTo   = null;

            public function __construct(
                private readonly bool $succeeds,
                private readonly string $error,
            ) {}

            public function move(string $from, string $to): AssetFileMoveResult
            {
                $this->capturedFrom = $from;
                $this->capturedTo   = $to;

                return $this->succeeds
                    ? AssetFileMoveResult::success($from, $to)
                    : AssetFileMoveResult::failure($from, $to, $this->error ?: 'move failed');
            }
        };
    }

    public function testProcessSuccessReturnsUploadWithResult(): void
    {
        $fakeMover = $this->makeFakeMover(true);
        $processor = new AssetUploadMoveProcessor($fakeMover);

        $upload = [
            'file' => '/protected-uploads/photo.jpg',
            'url'  => 'https://example.com/uploads/photo.jpg',
            'type' => 'image/jpeg',
        ];

        $result = $processor->process($upload, '/uploads/photo.jpg');

        $this->assertArrayHasKey('asset_move_result', $result);
        $this->assertInstanceOf(AssetFileMoveResult::class, $result['asset_move_result']);
        $this->assertTrue($result['asset_move_result']->isSuccess());
    }

    public function testProcessSuccessDoesNotSetErrorKey(): void
    {
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(true));

        $result = $processor->process(
            ['file' => '/protected-uploads/a.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/a.pdf',
        );

        $this->assertArrayNotHasKey('error', $result);
    }

    public function testProcessFailureSetsErrorKey(): void
    {
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(false, 'disk full'));

        $result = $processor->process(
            ['file' => '/protected-uploads/a.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/a.pdf',
        );

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('disk full', $result['error']);
    }

    public function testProcessFailureStoresMoveResult(): void
    {
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(false, 'oops'));

        $result = $processor->process(
            ['file' => '/protected-uploads/a.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/a.pdf',
        );

        $this->assertInstanceOf(AssetFileMoveResult::class, $result['asset_move_result']);
        $this->assertFalse($result['asset_move_result']->isSuccess());
        $this->assertSame('oops', $result['asset_move_result']->error());
    }

    public function testProcessPassesOriginalPathAsMoveSource(): void
    {
        $fakeMover = $this->makeFakeMover(true);
        $processor = new AssetUploadMoveProcessor($fakeMover);

        $processor->process(
            ['file' => '/protected-uploads/b.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/original.pdf',
        );

        $this->assertSame('/uploads/original.pdf', $fakeMover->capturedFrom);
    }

    public function testProcessPassesProtectedPathAsMoveDestination(): void
    {
        $fakeMover = $this->makeFakeMover(true);
        $processor = new AssetUploadMoveProcessor($fakeMover);

        $processor->process(
            ['file' => '/protected-uploads/b.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/b.pdf',
        );

        $this->assertSame('/protected-uploads/b.pdf', $fakeMover->capturedTo);
    }

    public function testProcessLeavesUrlUnchanged(): void
    {
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(true));

        $upload = ['file' => '/protected-uploads/c.jpg', 'url' => 'https://example.com/uploads/c.jpg', 'type' => 'image/jpeg'];
        $result = $processor->process($upload, '/uploads/c.jpg');

        $this->assertSame('https://example.com/uploads/c.jpg', $result['url']);
    }

    public function testProcessLeavesMimeTypeUnchanged(): void
    {
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(true));

        $upload = ['file' => '/protected-uploads/c.pdf', 'url' => '', 'type' => 'application/pdf'];
        $result = $processor->process($upload, '/uploads/c.pdf');

        $this->assertSame('application/pdf', $result['type']);
    }

    public function testProcessDoesNotInteractWithMediaLibrary(): void
    {
        // If this completes without calling any WordPress function, no Media Library
        // interaction has occurred. The fake mover isolates all filesystem access.
        $processor = new AssetUploadMoveProcessor($this->makeFakeMover(true));

        $result = $processor->process(
            ['file' => '/protected-uploads/x.pdf', 'url' => '', 'type' => 'application/pdf'],
            '/uploads/x.pdf',
        );

        $this->assertIsArray($result);
    }
}
