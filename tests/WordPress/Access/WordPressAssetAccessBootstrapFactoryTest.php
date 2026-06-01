<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\FilesystemInspectorInterface;
use Period\WpKit\WordPress\Access\WordPressAssetAccessApplicationFactory;
use Period\WpKit\WordPress\Access\WordPressAssetAccessBootstrapFactory;
use Period\WpKit\WordPress\Access\WordPressAssetAccessRuntimeDefaults;
use Period\WpKit\WordPress\Access\WordPressAssetAccessRuntimeInstaller;

final class WordPressAssetAccessBootstrapFactoryTest extends TestCase
{
    public function testCreateFactoryReturnsApplicationFactory(): void
    {
        $factory = new WordPressAssetAccessBootstrapFactory($this->makeDefaults());

        $this->assertInstanceOf(WordPressAssetAccessApplicationFactory::class, $factory->createFactory());
    }

    public function testCreateInstallerReturnsRuntimeInstaller(): void
    {
        $factory = new WordPressAssetAccessBootstrapFactory($this->makeDefaults());

        $this->assertInstanceOf(WordPressAssetAccessRuntimeInstaller::class, $factory->createInstaller());
    }

    public function testPrivateAssetRootAndFilesystemInspectorArePassedThrough(): void
    {
        $paths = new \ArrayObject();
        $defaults = $this->makeDefaults(
            privateAssetRoot: '/runtime-private-assets',
            filesystemInspector: $this->makeCapturingFilesystemInspector($paths),
        );
        $factory = new WordPressAssetAccessBootstrapFactory($defaults);

        $factory->createFactory()->createHealthReporter()->report();

        $this->assertSame([
            '/runtime-private-assets',
            '/runtime-private-assets',
        ], $paths->getArrayCopy());
    }

    public function testGetOptionCallableIsPreservedInSettingsRepository(): void
    {
        $getOptionCalls = [];
        $defaults = $this->makeDefaults(
            getOption: function (string $key, mixed $default) use (&$getOptionCalls): mixed {
                $getOptionCalls[] = [$key, $default];

                return [
                    'enabled'            => true,
                    'protected_roles'    => ['subscriber'],
                    'default_visibility' => 'private',
                ];
            },
        );
        $factory = new WordPressAssetAccessBootstrapFactory($defaults);

        $factory->createFactory()->createManager();

        $this->assertSame('period_asset_access_settings', $getOptionCalls[0][0]);
    }

    public function testAddActionAndAddFilterCallablesArePreservedInInstaller(): void
    {
        $actions = [];
        $filters = [];
        $defaults = $this->makeDefaults(
            addAction: function (string $hook, callable $callback, int $priority) use (&$actions): void {
                $actions[] = [$hook, $priority];
            },
            addFilter: function (string $hook, callable $callback, int $priority) use (&$filters): void {
                $filters[] = [$hook, $priority];
            },
        );
        $factory = new WordPressAssetAccessBootstrapFactory($defaults);

        $factory->createInstaller()->install();

        $this->assertContains('init', array_column($actions, 0));
        $this->assertContains('wp_handle_upload', array_column($filters, 0));
    }

    public function testGetMetaAndUpdateMetaCallablesArePreserved(): void
    {
        $getMetaCalls = [];
        $updateMetaCalls = [];
        $filters = [];
        $defaults = $this->makeDefaults(
            getMeta: function (int $id, string $key, bool $single) use (&$getMetaCalls): mixed {
                $getMetaCalls[] = [$id, $key, $single];

                return match ($key) {
                    '_period_asset_protected' => '1',
                    '_period_asset_protected_path' => '/private/file.pdf',
                    default => '',
                };
            },
            updateMeta: function (int $id, string $key, mixed $value) use (&$updateMetaCalls): void {
                $updateMetaCalls[] = [$id, $key, $value];
            },
            addFilter: function (string $hook, callable $callback, int $priority, int $acceptedArgs) use (&$filters): void {
                $filters[$hook] = $callback;
            },
        );
        $factory = new WordPressAssetAccessBootstrapFactory($defaults);
        $applicationFactory = $factory->createFactory();

        $applicationFactory->createAttachmentUrlFilterHookRegistrar()->register();
        $applicationFactory->createAttachmentEditFieldHookRegistrar()->register();

        ($filters['wp_get_attachment_url'])('https://example.test/file.pdf', 123);
        ($filters['attachment_fields_to_save'])(['ID' => 123], [
            'period_asset_access_protected' => '1',
        ]);

        $this->assertSame([123, '_period_asset_protected', true], $getMetaCalls[0]);
        $this->assertSame([123, '_period_asset_protected_path', true], $getMetaCalls[1]);
        $this->assertSame([123, '_period_asset_protected', '1'], $updateMetaCalls[0]);
    }

    public function testFactoryReturnsNewInstancesEachTime(): void
    {
        $factory = new WordPressAssetAccessBootstrapFactory($this->makeDefaults());

        $this->assertNotSame($factory->createFactory(), $factory->createFactory());
        $this->assertNotSame($factory->createInstaller(), $factory->createInstaller());
    }

    private function makeDefaults(
        ?string $privateAssetRoot = '/private-assets',
        ?FilesystemInspectorInterface $filesystemInspector = null,
        ?callable $getOption = null,
        ?callable $updateOption = null,
        ?callable $getMeta = null,
        ?callable $updateMeta = null,
        ?callable $addAction = null,
        ?callable $addFilter = null,
    ): WordPressAssetAccessRuntimeDefaults {
        return new WordPressAssetAccessRuntimeDefaults(
            $privateAssetRoot,
            $filesystemInspector ?? $this->makeFilesystemInspector(),
            $getOption ?? static fn(string $key, mixed $default): mixed => $default,
            $updateOption ?? static fn(string $key, mixed $value): null => null,
            $getMeta ?? static fn(int $id, string $key, bool $single): mixed => '',
            $updateMeta ?? static fn(int $id, string $key, mixed $value): null => null,
            $addAction ?? static fn(mixed ...$args): null => null,
            $addFilter ?? static fn(mixed ...$args): null => null,
        );
    }

    private function makeFilesystemInspector(): FilesystemInspectorInterface
    {
        return new class implements FilesystemInspectorInterface {
            public function exists(string $path): bool
            {
                return true;
            }

            public function isReadable(string $path): bool
            {
                return true;
            }

            public function isWritable(string $path): bool
            {
                return true;
            }
        };
    }

    /** @param \ArrayObject<int, string> $paths */
    private function makeCapturingFilesystemInspector(\ArrayObject $paths): FilesystemInspectorInterface
    {
        return new class($paths) implements FilesystemInspectorInterface {
            /** @param \ArrayObject<int, string> $paths */
            public function __construct(private readonly \ArrayObject $paths) {}

            public function exists(string $path): bool
            {
                $this->paths->append($path);
                return true;
            }

            public function isReadable(string $path): bool
            {
                $this->paths->append($path);
                return true;
            }

            public function isWritable(string $path): bool
            {
                $this->paths->append($path);
                return true;
            }
        };
    }
}
