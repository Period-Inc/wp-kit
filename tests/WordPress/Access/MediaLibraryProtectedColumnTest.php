<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetAttachmentMetaReader;
use Period\WpKit\WordPress\Access\AssetProtectedStateBadgeRenderer;
use Period\WpKit\WordPress\Access\MediaLibraryProtectedColumnProvider;
use Period\WpKit\WordPress\Access\WordPressMediaLibraryProtectedColumnHookRegistrar;

final class MediaLibraryProtectedColumnTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeReader(bool $protected): AssetAttachmentMetaReader
    {
        return new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use ($protected): mixed {
                return match ($key) {
                    '_period_asset_protected' => $protected ? '1' : '',
                    default                  => '',
                };
            },
        );
    }

    private function makeRenderer(bool $protected = true): AssetProtectedStateBadgeRenderer
    {
        return new AssetProtectedStateBadgeRenderer($this->makeReader($protected));
    }

    private function makeProvider(bool $protected = true): MediaLibraryProtectedColumnProvider
    {
        return new MediaLibraryProtectedColumnProvider($this->makeRenderer($protected));
    }

    // -----------------------------------------------------------------------
    // AssetProtectedStateBadgeRenderer — protected badge
    // -----------------------------------------------------------------------

    public function testProtectedBadgeReturnsNonEmptyString(): void
    {
        $this->assertNotEmpty($this->makeRenderer(true)->render(1));
    }

    public function testProtectedBadgeContainsSpanTag(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringContainsString('<span', $html);
        $this->assertStringContainsString('</span>', $html);
    }

    public function testProtectedBadgeHasCorrectClass(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringContainsString('period-asset-protected-badge', $html);
    }

    public function testProtectedBadgeContainsProtectedLabel(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringContainsString('Protected', $html);
    }

    public function testProtectedBadgeExactOutput(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertSame('<span class="period-asset-protected-badge">Protected</span>', $html);
    }

    public function testProtectedBadgeDoesNotContainPublicClass(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringNotContainsString('period-asset-public-badge', $html);
    }

    // -----------------------------------------------------------------------
    // AssetProtectedStateBadgeRenderer — public badge
    // -----------------------------------------------------------------------

    public function testPublicBadgeReturnsNonEmptyString(): void
    {
        $this->assertNotEmpty($this->makeRenderer(false)->render(1));
    }

    public function testPublicBadgeHasCorrectClass(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertStringContainsString('period-asset-public-badge', $html);
    }

    public function testPublicBadgeContainsPublicLabel(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertStringContainsString('Public', $html);
    }

    public function testPublicBadgeExactOutput(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertSame('<span class="period-asset-public-badge">Public</span>', $html);
    }

    public function testPublicBadgeDoesNotContainProtectedClass(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertStringNotContainsString('period-asset-protected-badge', $html);
    }

    // -----------------------------------------------------------------------
    // HTML escaping
    // -----------------------------------------------------------------------

    public function testProtectedBadgeContainsNoInlineStyle(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringNotContainsString('style=', $html);
    }

    public function testPublicBadgeContainsNoInlineStyle(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertStringNotContainsString('style=', $html);
    }

    public function testProtectedBadgeContainsNoScriptTag(): void
    {
        $html = $this->makeRenderer(true)->render(1);

        $this->assertStringNotContainsString('<script', $html);
    }

    public function testPublicBadgeContainsNoScriptTag(): void
    {
        $html = $this->makeRenderer(false)->render(1);

        $this->assertStringNotContainsString('<script', $html);
    }

    public function testAttachmentIdDoesNotLeakIntoOutput(): void
    {
        $html = $this->makeRenderer(true)->render(9999);

        $this->assertStringNotContainsString('9999', $html);
    }

    // -----------------------------------------------------------------------
    // MediaLibraryProtectedColumnProvider — column registration
    // -----------------------------------------------------------------------

    public function testAddColumnReturnsPeriodAssetAccessKey(): void
    {
        $provider = $this->makeProvider();
        $columns  = $provider->addColumn(['title' => 'File']);

        $this->assertArrayHasKey('period_asset_access', $columns);
    }

    public function testAddColumnLabelIsAssetAccess(): void
    {
        $provider = $this->makeProvider();
        $columns  = $provider->addColumn([]);

        $this->assertSame('Asset Access', $columns['period_asset_access']);
    }

    public function testAddColumnPreservesExistingColumns(): void
    {
        $provider  = $this->makeProvider();
        $original  = ['title' => 'File', 'author' => 'Author'];
        $columns   = $provider->addColumn($original);

        $this->assertArrayHasKey('title', $columns);
        $this->assertArrayHasKey('author', $columns);
    }

    public function testAddColumnWithEmptyInputAddsColumn(): void
    {
        $provider = $this->makeProvider();
        $columns  = $provider->addColumn([]);

        $this->assertCount(1, $columns);
        $this->assertArrayHasKey('period_asset_access', $columns);
    }

    // -----------------------------------------------------------------------
    // MediaLibraryProtectedColumnProvider — column rendering
    // -----------------------------------------------------------------------

    public function testRenderColumnForTargetColumnReturnsNonEmpty(): void
    {
        $provider = $this->makeProvider(true);

        $this->assertNotEmpty($provider->renderColumn('period_asset_access', 1));
    }

    public function testRenderColumnForTargetColumnReturnsProtectedBadge(): void
    {
        $provider = $this->makeProvider(true);

        $html = $provider->renderColumn('period_asset_access', 1);

        $this->assertStringContainsString('period-asset-protected-badge', $html);
    }

    public function testRenderColumnForTargetColumnReturnsPublicBadgeForPublicAttachment(): void
    {
        $provider = $this->makeProvider(false);

        $html = $provider->renderColumn('period_asset_access', 1);

        $this->assertStringContainsString('period-asset-public-badge', $html);
    }

    // -----------------------------------------------------------------------
    // MediaLibraryProtectedColumnProvider — unrelated column returns empty
    // -----------------------------------------------------------------------

    public function testRenderColumnForUnrelatedColumnReturnsEmptyString(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame('', $provider->renderColumn('title', 1));
    }

    public function testRenderColumnForAnotherUnrelatedColumnReturnsEmptyString(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame('', $provider->renderColumn('author', 42));
    }

    public function testRenderColumnForEmptyColumnNameReturnsEmptyString(): void
    {
        $provider = $this->makeProvider();

        $this->assertSame('', $provider->renderColumn('', 1));
    }

    // -----------------------------------------------------------------------
    // WordPressMediaLibraryProtectedColumnHookRegistrar — hook registration
    // -----------------------------------------------------------------------

    public function testRegisterCallsAddFilter(): void
    {
        $filterCalls = [];
        $registrar   = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $this->makeProvider(),
            function () use (&$filterCalls): void { $filterCalls[] = func_get_args(); },
            function (): void {},
        );

        $registrar->register();

        $this->assertNotEmpty($filterCalls);
    }

    public function testRegisterHooksManageUploadColumnsFilter(): void
    {
        $hooks     = [];
        $registrar = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $this->makeProvider(),
            function (string $hook) use (&$hooks): void { $hooks[] = $hook; },
            function (): void {},
        );

        $registrar->register();

        $this->assertContains('manage_upload_columns', $hooks);
    }

    public function testRegisterCallsAddAction(): void
    {
        $actionCalls = [];
        $registrar   = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $this->makeProvider(),
            function (): void {},
            function () use (&$actionCalls): void { $actionCalls[] = func_get_args(); },
        );

        $registrar->register();

        $this->assertNotEmpty($actionCalls);
    }

    public function testRegisterHooksManageMediaCustomColumnAction(): void
    {
        $hooks     = [];
        $registrar = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $this->makeProvider(),
            function (): void {},
            function (string $hook) use (&$hooks): void { $hooks[] = $hook; },
        );

        $registrar->register();

        $this->assertContains('manage_media_custom_column', $hooks);
    }

    public function testManageMediaCustomColumnActionAcceptsTwoArgs(): void
    {
        $capturedArgs = [];
        $registrar    = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $this->makeProvider(),
            function (): void {},
            function (string $hook, callable $cb, int $priority, int $acceptedArgs) use (&$capturedArgs): void {
                if ($hook === 'manage_media_custom_column') {
                    $capturedArgs['acceptedArgs'] = $acceptedArgs;
                }
            },
        );

        $registrar->register();

        $this->assertSame(2, $capturedArgs['acceptedArgs']);
    }

    public function testManageUploadColumnsFilterPassesProviderAddColumn(): void
    {
        $capturedCallable = null;
        $provider         = $this->makeProvider();
        $registrar        = new WordPressMediaLibraryProtectedColumnHookRegistrar(
            $provider,
            function (string $hook, callable $cb) use (&$capturedCallable): void {
                if ($hook === 'manage_upload_columns') {
                    $capturedCallable = $cb;
                }
            },
            function (): void {},
        );

        $registrar->register();

        $this->assertNotNull($capturedCallable);
        $result = ($capturedCallable)(['title' => 'File']);
        $this->assertArrayHasKey('period_asset_access', $result);
    }
}
