<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\HtaccessWriteResult;
use Period\WpFramework\WordPress\Access\HtaccessWriterInterface;
use Period\WpFramework\WordPress\Access\NativeHtaccessWriter;

final class HtaccessWriterTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/period_htaccess_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*') ?: [] as $file) {
            @chmod($file, 0644);
            @unlink($file);
        }
        @rmdir($this->tempDir);
    }

    private function path(): string
    {
        return $this->tempDir . '/.htaccess';
    }

    private function makeWriter(): NativeHtaccessWriter
    {
        return new NativeHtaccessWriter();
    }

    private function sampleRules(): string
    {
        return implode("\n", [
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteRule ^protected-uploads/(.*)$ /asset-access?asset=protected-uploads/$1 [L,QSA]',
            '</IfModule>',
        ]);
    }

    // -----------------------------------------------------------------------
    // Interface compliance
    // -----------------------------------------------------------------------

    public function testImplementsHtaccessWriterInterface(): void
    {
        $this->assertInstanceOf(HtaccessWriterInterface::class, $this->makeWriter());
    }

    // -----------------------------------------------------------------------
    // HtaccessWriteResult value object
    // -----------------------------------------------------------------------

    public function testSuccessFactoryIsSuccess(): void
    {
        $result = HtaccessWriteResult::success('/path/.htaccess', true, false);

        $this->assertTrue($result->isSuccess());
    }

    public function testSuccessFactoryPathIsPreserved(): void
    {
        $result = HtaccessWriteResult::success('/path/.htaccess', true, false);

        $this->assertSame('/path/.htaccess', $result->path());
    }

    public function testSuccessFactoryWrittenIsPreserved(): void
    {
        $result = HtaccessWriteResult::success('/path/.htaccess', true, true);

        $this->assertTrue($result->written());
        $this->assertTrue($result->backupCreated());
    }

    public function testSuccessFactoryErrorIsNull(): void
    {
        $result = HtaccessWriteResult::success('/path/.htaccess', true, false);

        $this->assertNull($result->error());
    }

    public function testFailureFactoryIsNotSuccess(): void
    {
        $result = HtaccessWriteResult::failure('/path/.htaccess', 'something went wrong');

        $this->assertFalse($result->isSuccess());
    }

    public function testFailureFactoryWrittenIsFalse(): void
    {
        $result = HtaccessWriteResult::failure('/path/.htaccess', 'err');

        $this->assertFalse($result->written());
    }

    public function testFailureFactoryBackupCreatedIsFalse(): void
    {
        $result = HtaccessWriteResult::failure('/path/.htaccess', 'err');

        $this->assertFalse($result->backupCreated());
    }

    public function testFailureFactoryErrorIsPreserved(): void
    {
        $result = HtaccessWriteResult::failure('/path/.htaccess', 'something went wrong');

        $this->assertSame('something went wrong', $result->error());
    }

    // -----------------------------------------------------------------------
    // New file creation
    // -----------------------------------------------------------------------

    public function testWriteCreatesNewFileWhenNotExists(): void
    {
        $path = $this->path();
        $this->assertFileDoesNotExist($path);

        $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertFileExists($path);
    }

    public function testWriteReturnsSuccessForNewFile(): void
    {
        $result = $this->makeWriter()->write($this->path(), $this->sampleRules());

        $this->assertTrue($result->isSuccess());
    }

    public function testWriteReturnsWrittenTrueForNewFile(): void
    {
        $result = $this->makeWriter()->write($this->path(), $this->sampleRules());

        $this->assertTrue($result->written());
    }

    public function testWriteReturnsNoBackupForNewFile(): void
    {
        $result = $this->makeWriter()->write($this->path(), $this->sampleRules());

        $this->assertFalse($result->backupCreated());
    }

    public function testWritePathMatchesForNewFile(): void
    {
        $path   = $this->path();
        $result = $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertSame($path, $result->path());
    }

    public function testWriteErrorIsNullForNewFile(): void
    {
        $result = $this->makeWriter()->write($this->path(), $this->sampleRules());

        $this->assertNull($result->error());
    }

    // -----------------------------------------------------------------------
    // New file contains marker block
    // -----------------------------------------------------------------------

    public function testNewFileContainsMarkerBegin(): void
    {
        $path = $this->path();
        $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertStringContainsString('# BEGIN PeriodAssetAccess', (string) file_get_contents($path));
    }

    public function testNewFileContainsMarkerEnd(): void
    {
        $path = $this->path();
        $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertStringContainsString('# END PeriodAssetAccess', (string) file_get_contents($path));
    }

    public function testNewFileContainsRules(): void
    {
        $path = $this->path();
        $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertStringContainsString('RewriteRule', (string) file_get_contents($path));
    }

    // -----------------------------------------------------------------------
    // Append to existing file without marker
    // -----------------------------------------------------------------------

    public function testAppendsToExistingFileWithoutMarker(): void
    {
        $path = $this->path();
        file_put_contents($path, "# Existing content\n");

        $this->makeWriter()->write($path, $this->sampleRules());

        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('# Existing content', $content);
        $this->assertStringContainsString('# BEGIN PeriodAssetAccess', $content);
    }

    public function testExistingContentAppearsBeforeMarkerOnAppend(): void
    {
        $path = $this->path();
        file_put_contents($path, "# Existing content\n");

        $this->makeWriter()->write($path, $this->sampleRules());

        $content  = (string) file_get_contents($path);
        $posExisting = strpos($content, '# Existing content');
        $posMarker   = strpos($content, '# BEGIN PeriodAssetAccess');
        $this->assertGreaterThan($posExisting, $posMarker);
    }

    public function testAppendedFileEndsWithNewline(): void
    {
        $path = $this->path();
        file_put_contents($path, "# Existing\n");

        $this->makeWriter()->write($path, $this->sampleRules());

        $content = (string) file_get_contents($path);
        $this->assertStringEndsWith("\n", $content);
    }

    // -----------------------------------------------------------------------
    // Replace existing marker block
    // -----------------------------------------------------------------------

    public function testReplacesExistingMarkerBlock(): void
    {
        $path = $this->path();
        file_put_contents($path, implode("\n", [
            '# BEGIN PeriodAssetAccess',
            'RewriteRule ^old-prefix/(.*)$ /old-endpoint?asset=old-prefix/$1 [L,QSA]',
            '# END PeriodAssetAccess',
            '',
        ]));

        $this->makeWriter()->write($path, $this->sampleRules());

        $content = (string) file_get_contents($path);
        $this->assertStringNotContainsString('old-prefix', $content);
        $this->assertStringContainsString('protected-uploads', $content);
    }

    public function testOnlyOneMarkerBlockAfterReplace(): void
    {
        $path = $this->path();
        file_put_contents($path, implode("\n", [
            '# BEGIN PeriodAssetAccess',
            'RewriteRule ^old/(.*)$ /old?asset=old/$1 [L,QSA]',
            '# END PeriodAssetAccess',
            '',
        ]));

        $this->makeWriter()->write($path, $this->sampleRules());

        $content = (string) file_get_contents($path);
        $this->assertSame(1, substr_count($content, '# BEGIN PeriodAssetAccess'));
        $this->assertSame(1, substr_count($content, '# END PeriodAssetAccess'));
    }

    public function testContentOutsideMarkerPreservedOnReplace(): void
    {
        $path = $this->path();
        file_put_contents($path, implode("\n", [
            '# Custom header',
            '# BEGIN PeriodAssetAccess',
            'RewriteRule ^old/(.*)$ /old?asset=old/$1 [L,QSA]',
            '# END PeriodAssetAccess',
            '# Custom footer',
            '',
        ]));

        $this->makeWriter()->write($path, $this->sampleRules());

        $content = (string) file_get_contents($path);
        $this->assertStringContainsString('# Custom header', $content);
        $this->assertStringContainsString('# Custom footer', $content);
    }

    // -----------------------------------------------------------------------
    // Backup
    // -----------------------------------------------------------------------

    public function testBackupCreatedForExistingFile(): void
    {
        $path = $this->path();
        file_put_contents($path, "# Original\n");

        $result = $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertTrue($result->backupCreated());
        $this->assertFileExists($path . '.bak');
    }

    public function testBackupContainsOriginalContent(): void
    {
        $path     = $this->path();
        $original = "# Original content\n";
        file_put_contents($path, $original);

        $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertSame($original, (string) file_get_contents($path . '.bak'));
    }

    public function testBackupIsOverwrittenOnSecondWrite(): void
    {
        $path = $this->path();
        file_put_contents($path, "# First write\n");
        $this->makeWriter()->write($path, $this->sampleRules());

        $this->makeWriter()->write($path, $this->sampleRules());

        $backupContent = (string) file_get_contents($path . '.bak');
        $this->assertStringContainsString('# BEGIN PeriodAssetAccess', $backupContent);
    }

    public function testNoBackupCreatedWhenFileDidNotExist(): void
    {
        $path   = $this->path();
        $result = $this->makeWriter()->write($path, $this->sampleRules());

        $this->assertFalse($result->backupCreated());
        $this->assertFileDoesNotExist($path . '.bak');
    }

    // -----------------------------------------------------------------------
    // Idempotency — applying same rules twice
    // -----------------------------------------------------------------------

    public function testApplyingRulesTwiceProducesOneMarkerBlock(): void
    {
        $path  = $this->path();
        $rules = $this->sampleRules();

        $this->makeWriter()->write($path, $rules);
        $this->makeWriter()->write($path, $rules);

        $content = (string) file_get_contents($path);
        $this->assertSame(1, substr_count($content, '# BEGIN PeriodAssetAccess'));
    }

    public function testApplyingRulesTwiceSucceedsBothTimes(): void
    {
        $path  = $this->path();
        $rules = $this->sampleRules();

        $r1 = $this->makeWriter()->write($path, $rules);
        $r2 = $this->makeWriter()->write($path, $rules);

        $this->assertTrue($r1->isSuccess());
        $this->assertTrue($r2->isSuccess());
    }

    // -----------------------------------------------------------------------
    // Failure — file not writable
    // -----------------------------------------------------------------------

    public function testFailureWhenFileNotWritable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission test not reliable on Windows');
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Permission test not reliable when running as root');
        }

        $path = $this->path();
        file_put_contents($path, "# existing\n");
        chmod($path, 0444);

        $result = $this->makeWriter()->write($path, $this->sampleRules());

        chmod($path, 0644);

        $this->assertFalse($result->isSuccess());
        $this->assertNotNull($result->error());
    }

    public function testFailureResultHasCorrectPath(): void
    {
        $result = HtaccessWriteResult::failure('/some/.htaccess', 'oops');

        $this->assertSame('/some/.htaccess', $result->path());
    }
}
