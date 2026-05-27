<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAccessHealthStatus;
use Period\WpFramework\WordPress\Access\FilesystemInspectorInterface;
use Period\WpFramework\WordPress\Access\FilesystemPathHealthCheck;
use Period\WpFramework\WordPress\Access\NativeFilesystemInspector;

final class FilesystemPathHealthCheckTest extends TestCase
{
    public function testExistingPathInfo(): void
    {
        $statuses = (new FilesystemPathHealthCheck(
            new StubFilesystemInspector(exists: true, readable: true),
            '/private/assets',
            'private asset root',
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertTrue($statuses[0]->healthy());
        $this->assertSame('private_asset_root_exists', $statuses[0]->code());
        $this->assertSame('private asset root exists', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_INFO, $statuses[0]->severity());
    }

    public function testMissingPathError(): void
    {
        $statuses = (new FilesystemPathHealthCheck(
            new StubFilesystemInspector(exists: false, readable: false),
            '/private/assets',
            'private asset root',
        ))->check();

        $this->assertCount(1, $statuses);
        $this->assertFalse($statuses[0]->healthy());
        $this->assertSame('private_asset_root_missing', $statuses[0]->code());
        $this->assertSame('private asset root missing', $statuses[0]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_ERROR, $statuses[0]->severity());
    }

    public function testUnreadablePathWarning(): void
    {
        $statuses = (new FilesystemPathHealthCheck(
            new StubFilesystemInspector(exists: true, readable: false),
            '/private/assets',
            'private asset root',
        ))->check();

        $this->assertCount(2, $statuses);
        $this->assertSame('private_asset_root_exists', $statuses[0]->code());
        $this->assertSame('private_asset_root_not_readable', $statuses[1]->code());
        $this->assertSame('private asset root is not readable', $statuses[1]->message());
        $this->assertSame(AssetAccessHealthStatus::SEVERITY_WARNING, $statuses[1]->severity());
    }

    public function testOrderIsDeterministic(): void
    {
        $statuses = (new FilesystemPathHealthCheck(
            new StubFilesystemInspector(exists: true, readable: false),
            '/private/assets',
            'private asset root',
        ))->check();

        $codes = array_map(
            static fn(AssetAccessHealthStatus $status): string => $status->code(),
            $statuses,
        );

        $this->assertSame([
            'private_asset_root_exists',
            'private_asset_root_not_readable',
        ], $codes);
    }

    public function testNativeInspectorDelegatesToFilesystemState(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pwf-access-health-');
        $this->assertIsString($path);

        try {
            $inspector = new NativeFilesystemInspector();

            $this->assertTrue($inspector->exists($path));
            $this->assertTrue($inspector->isReadable($path));
            $this->assertTrue($inspector->isWritable($path));

            unlink($path);

            $this->assertFalse($inspector->exists($path));
        } finally {
            if (is_string($path) && file_exists($path)) {
                unlink($path);
            }
        }
    }
}

final class StubFilesystemInspector implements FilesystemInspectorInterface
{
    public function __construct(
        private readonly bool $exists,
        private readonly bool $readable,
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
