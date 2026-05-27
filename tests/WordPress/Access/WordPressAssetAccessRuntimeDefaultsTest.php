<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\NativeFilesystemInspector;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeDefaults;
use Period\WpFramework\WordPress\Access\WordPressAssetAccessRuntimeDefaultsFactory;

final class WordPressAssetAccessRuntimeDefaultsTest extends TestCase
{
    public function testPrivateRootDefaultPathGeneration(): void
    {
        $defaults = (new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => ['basedir' => '/var/www/wp-content/uploads'],
            wpContentDirResolver: fn(): string => '/var/www/wp-content',
        ))->create();

        $this->assertSame('/var/www/private-assets', $defaults->privateAssetRoot());
    }

    public function testPrivateRootPathGenerationNormalizesDuplicateSlashes(): void
    {
        $defaults = (new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => ['basedir' => '/var/www/wp-content/uploads'],
            wpContentDirResolver: fn(): string => '/var//www//wp-content//',
        ))->create();

        $this->assertSame('/var/www/private-assets', $defaults->privateAssetRoot());
    }

    public function testNativeFilesystemInspectorIsIncluded(): void
    {
        $defaults = (new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => [],
            wpContentDirResolver: fn(): string => '/var/www/wp-content',
        ))->create();

        $this->assertInstanceOf(NativeFilesystemInspector::class, $defaults->filesystemInspector());
    }

    public function testCallableDependenciesArePreserved(): void
    {
        $getOption = fn(string $key, mixed $default): mixed => 'option';
        $updateOption = fn(string $key, mixed $value): null => null;
        $getMeta = fn(int $id, string $key, bool $single): mixed => 'meta';
        $updateMeta = fn(int $id, string $key, mixed $value): null => null;
        $addAction = fn(string $hook, callable $callback, int $priority): null => null;
        $addFilter = fn(string $hook, callable $callback, int $priority): null => null;

        $defaults = (new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => [],
            wpContentDirResolver: fn(): string => '/var/www/wp-content',
            getOption: $getOption,
            updateOption: $updateOption,
            getMeta: $getMeta,
            updateMeta: $updateMeta,
            addAction: $addAction,
            addFilter: $addFilter,
        ))->create();

        $this->assertSame($getOption, $defaults->getOption());
        $this->assertSame($updateOption, $defaults->updateOption());
        $this->assertSame($getMeta, $defaults->getMeta());
        $this->assertSame($updateMeta, $defaults->updateMeta());
        $this->assertSame($addAction, $defaults->addAction());
        $this->assertSame($addFilter, $defaults->addFilter());
    }

    public function testUploadsDirResolverIsNotExecutedDuringCreate(): void
    {
        $uploadsCalls = 0;

        (new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: function () use (&$uploadsCalls): array {
                $uploadsCalls++;
                return [];
            },
            wpContentDirResolver: fn(): string => '/var/www/wp-content',
        ))->create();

        $this->assertSame(0, $uploadsCalls);
    }

    public function testOutputIsDeterministic(): void
    {
        $factory = new WordPressAssetAccessRuntimeDefaultsFactory(
            uploadsDirResolver: fn(): array => [],
            wpContentDirResolver: fn(): string => '/var/www/wp-content',
        );

        $first = $factory->create();
        $second = $factory->create();

        $this->assertSame($first->privateAssetRoot(), $second->privateAssetRoot());
        $this->assertNotSame($first, $second);
    }

    public function testDefaultsValueObjectReturnsStoredValues(): void
    {
        $getOption = fn(string $key, mixed $default): mixed => $default;
        $updateOption = fn(string $key, mixed $value): null => null;
        $getMeta = fn(int $id, string $key, bool $single): mixed => '';
        $updateMeta = fn(int $id, string $key, mixed $value): null => null;
        $addAction = fn(mixed ...$args): null => null;
        $addFilter = fn(mixed ...$args): null => null;

        $defaults = new WordPressAssetAccessRuntimeDefaults(
            '/private-assets',
            null,
            $getOption,
            $updateOption,
            $getMeta,
            $updateMeta,
            $addAction,
            $addFilter,
        );

        $this->assertSame('/private-assets', $defaults->privateAssetRoot());
        $this->assertNull($defaults->filesystemInspector());
        $this->assertSame($getOption, $defaults->getOption());
    }
}
