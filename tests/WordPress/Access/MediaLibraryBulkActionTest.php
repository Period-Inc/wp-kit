<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\Access\AssetBulkProtectionActionProvider;
use Period\WpKit\WordPress\Access\AssetBulkProtectionProcessor;
use Period\WpKit\WordPress\Access\AssetBulkProtectionResult;
use Period\WpKit\WordPress\Access\WordPressMediaLibraryBulkActionHookRegistrar;

final class MediaLibraryBulkActionTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** @param array<array{int, string, mixed}> $calls */
    private function makeProcessor(array &$calls = []): AssetBulkProtectionProcessor
    {
        return new AssetBulkProtectionProcessor(
            function (int $id, string $key, mixed $value) use (&$calls): void {
                $calls[] = [$id, $key, $value];
            },
        );
    }

    private function makeProvider(?AssetBulkProtectionProcessor $processor = null): AssetBulkProtectionActionProvider
    {
        return new AssetBulkProtectionActionProvider($processor ?? $this->makeProcessor());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionResult — value object
    // -----------------------------------------------------------------------

    public function testProtectFactoryActionIsProtect(): void
    {
        $result = AssetBulkProtectionResult::protect([1, 2]);

        $this->assertSame('period_protect_assets', $result->action());
    }

    public function testProtectFactoryProcessedIdsArePreserved(): void
    {
        $result = AssetBulkProtectionResult::protect([1, 2, 3]);

        $this->assertSame([1, 2, 3], $result->processedIds());
    }

    public function testProtectFactoryProtectedIsTrue(): void
    {
        $result = AssetBulkProtectionResult::protect([1]);

        $this->assertTrue($result->protected());
    }

    public function testUnprotectFactoryActionIsUnprotect(): void
    {
        $result = AssetBulkProtectionResult::unprotect([1, 2]);

        $this->assertSame('period_unprotect_assets', $result->action());
    }

    public function testUnprotectFactoryProcessedIdsArePreserved(): void
    {
        $result = AssetBulkProtectionResult::unprotect([5, 6]);

        $this->assertSame([5, 6], $result->processedIds());
    }

    public function testUnprotectFactoryProtectedIsFalse(): void
    {
        $result = AssetBulkProtectionResult::unprotect([1]);

        $this->assertFalse($result->protected());
    }

    public function testNoopFactoryProtectedIsNull(): void
    {
        $result = AssetBulkProtectionResult::noop('some_unknown_action');

        $this->assertNull($result->protected());
    }

    public function testNoopFactoryProcessedIdsIsEmpty(): void
    {
        $result = AssetBulkProtectionResult::noop('some_unknown_action');

        $this->assertSame([], $result->processedIds());
    }

    public function testNoopFactoryPreservesActionString(): void
    {
        $result = AssetBulkProtectionResult::noop('custom_action');

        $this->assertSame('custom_action', $result->action());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionActionProvider — registerActions
    // -----------------------------------------------------------------------

    public function testRegisterActionsContainsProtectKey(): void
    {
        $actions = $this->makeProvider()->registerActions([]);

        $this->assertArrayHasKey('period_protect_assets', $actions);
    }

    public function testRegisterActionsContainsUnprotectKey(): void
    {
        $actions = $this->makeProvider()->registerActions([]);

        $this->assertArrayHasKey('period_unprotect_assets', $actions);
    }

    public function testRegisterActionsProtectLabel(): void
    {
        $actions = $this->makeProvider()->registerActions([]);

        $this->assertSame('Protect Assets', $actions['period_protect_assets']);
    }

    public function testRegisterActionsUnprotectLabel(): void
    {
        $actions = $this->makeProvider()->registerActions([]);

        $this->assertSame('Unprotect Assets', $actions['period_unprotect_assets']);
    }

    public function testRegisterActionsPreservesExistingActions(): void
    {
        $actions = $this->makeProvider()->registerActions(['delete' => 'Delete']);

        $this->assertArrayHasKey('delete', $actions);
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionActionProvider — handleAction / unknown → noop
    // -----------------------------------------------------------------------

    public function testUnknownActionReturnsNoop(): void
    {
        $result = $this->makeProvider()->handleAction('some_unknown_action', [1, 2]);

        $this->assertNull($result->protected());
    }

    public function testUnknownActionProcessedIdsIsEmpty(): void
    {
        $result = $this->makeProvider()->handleAction('some_unknown_action', [1, 2]);

        $this->assertSame([], $result->processedIds());
    }

    public function testUnknownActionPreservesActionString(): void
    {
        $result = $this->makeProvider()->handleAction('my_action', []);

        $this->assertSame('my_action', $result->action());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionActionProvider — handleAction / protect
    // -----------------------------------------------------------------------

    public function testProtectActionReturnsProtectResult(): void
    {
        $result = $this->makeProvider()->handleAction('period_protect_assets', [1, 2]);

        $this->assertTrue($result->protected());
    }

    public function testProtectActionProcessedIdsMatchInput(): void
    {
        $result = $this->makeProvider()->handleAction('period_protect_assets', [3, 7]);

        $this->assertSame([3, 7], $result->processedIds());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionActionProvider — handleAction / unprotect
    // -----------------------------------------------------------------------

    public function testUnprotectActionReturnsUnprotectResult(): void
    {
        $result = $this->makeProvider()->handleAction('period_unprotect_assets', [1, 2]);

        $this->assertFalse($result->protected());
    }

    public function testUnprotectActionProcessedIdsMatchInput(): void
    {
        $result = $this->makeProvider()->handleAction('period_unprotect_assets', [4, 8]);

        $this->assertSame([4, 8], $result->processedIds());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionProcessor — protect
    // -----------------------------------------------------------------------

    public function testProcessProtectCallsUpdateMetaForEachId(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([1, 2, 3], true);

        $this->assertCount(3, $calls);
    }

    public function testProcessProtectSetsProtectedMetaKeyToOne(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([10], true);

        $this->assertSame('_period_asset_protected', $calls[0][1]);
        $this->assertSame('1', $calls[0][2]);
    }

    public function testProcessProtectPassesCorrectAttachmentId(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([42], true);

        $this->assertSame(42, $calls[0][0]);
    }

    public function testProcessProtectReturnsResultWithProtectedTrue(): void
    {
        $result = $this->makeProcessor()->process([1], true);

        $this->assertTrue($result->protected());
    }

    public function testProcessProtectResultContainsProcessedIds(): void
    {
        $result = $this->makeProcessor()->process([5, 6], true);

        $this->assertSame([5, 6], $result->processedIds());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionProcessor — unprotect
    // -----------------------------------------------------------------------

    public function testProcessUnprotectSetsProtectedMetaKeyToZero(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([10], false);

        $this->assertSame('_period_asset_protected', $calls[0][1]);
        $this->assertSame('0', $calls[0][2]);
    }

    public function testProcessUnprotectReturnsResultWithProtectedFalse(): void
    {
        $result = $this->makeProcessor()->process([1], false);

        $this->assertFalse($result->protected());
    }

    // -----------------------------------------------------------------------
    // AssetBulkProtectionProcessor — invalid ids are ignored
    // -----------------------------------------------------------------------

    public function testZeroIdIsIgnored(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([0], true);

        $this->assertEmpty($calls);
    }

    public function testNegativeIdIsIgnored(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([-5], true);

        $this->assertEmpty($calls);
    }

    public function testStringNonNumericIdIsIgnored(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process(['abc'], true);

        $this->assertEmpty($calls);
    }

    public function testEmptyStringIdIsIgnored(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([''], true);

        $this->assertEmpty($calls);
    }

    public function testMixedValidAndInvalidIdsOnlyProcessesValid(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([1, 0, 3, -2, 'bad', 5], true);

        $this->assertCount(3, $calls);
        $processedIds = array_column($calls, 0);
        $this->assertSame([1, 3, 5], $processedIds);
    }

    public function testInvalidIdsExcludedFromProcessedIds(): void
    {
        $result = $this->makeProcessor()->process([1, 0, -1, 'x', 5], true);

        $this->assertSame([1, 5], $result->processedIds());
    }

    public function testEmptyIdsArrayResultsInNoMetaCalls(): void
    {
        $calls     = [];
        $processor = $this->makeProcessor($calls);

        $processor->process([], true);

        $this->assertEmpty($calls);
    }

    // -----------------------------------------------------------------------
    // update_post_meta() is not called directly
    // -----------------------------------------------------------------------

    public function testProcessorUsesCallableNotWordPressDirectly(): void
    {
        $callableWasUsed = false;
        $processor       = new AssetBulkProtectionProcessor(
            function (int $id, string $key, mixed $value) use (&$callableWasUsed): void {
                $callableWasUsed = true;
            },
        );

        $processor->process([1], true);

        $this->assertTrue($callableWasUsed, 'Processor must use injected callable, not update_post_meta() directly');
    }

    // -----------------------------------------------------------------------
    // WordPressMediaLibraryBulkActionHookRegistrar — hook registration
    // -----------------------------------------------------------------------

    public function testRegisterCallsAddFilter(): void
    {
        $filterCalls = [];
        $registrar   = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function () use (&$filterCalls): void { $filterCalls[] = func_get_args(); },
            function (): void {},
        );

        $registrar->register();

        $this->assertNotEmpty($filterCalls);
    }

    public function testRegisterHooksBulkActionsUploadFilter(): void
    {
        $hooks     = [];
        $registrar = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function (string $hook) use (&$hooks): void { $hooks[] = $hook; },
            function (): void {},
        );

        $registrar->register();

        $this->assertContains('bulk_actions-upload', $hooks);
    }

    public function testRegisterHooksHandleBulkActionsUploadFilter(): void
    {
        $hooks     = [];
        $registrar = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function (string $hook) use (&$hooks): void { $hooks[] = $hook; },
            function (): void {},
        );

        $registrar->register();

        $this->assertContains('handle_bulk_actions-upload', $hooks);
    }

    public function testHandleBulkActionsFilterAcceptsThreeArgs(): void
    {
        $capturedArgs = [];
        $registrar    = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function (string $hook, callable $cb, int $priority, int $acceptedArgs = 1) use (&$capturedArgs): void {
                if ($hook === 'handle_bulk_actions-upload') {
                    $capturedArgs['acceptedArgs'] = $acceptedArgs;
                }
            },
            function (): void {},
        );

        $registrar->register();

        $this->assertSame(3, $capturedArgs['acceptedArgs']);
    }

    public function testBulkActionsFilterPassesRegisterActionsCallable(): void
    {
        $capturedCallable = null;
        $registrar        = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function (string $hook, callable $cb) use (&$capturedCallable): void {
                if ($hook === 'bulk_actions-upload') {
                    $capturedCallable = $cb;
                }
            },
            function (): void {},
        );

        $registrar->register();

        $this->assertNotNull($capturedCallable);
        $result = ($capturedCallable)([]);
        $this->assertArrayHasKey('period_protect_assets', $result);
    }

    public function testHandleBulkActionsCallableReturnsRedirectTo(): void
    {
        $capturedCallable = null;
        $registrar        = new WordPressMediaLibraryBulkActionHookRegistrar(
            $this->makeProvider(),
            function (string $hook, callable $cb) use (&$capturedCallable): void {
                if ($hook === 'handle_bulk_actions-upload') {
                    $capturedCallable = $cb;
                }
            },
            function (): void {},
        );

        $registrar->register();

        $redirectTo = ($capturedCallable)('https://example.com/wp-admin/upload.php', 'some_action', []);
        $this->assertSame('https://example.com/wp-admin/upload.php', $redirectTo);
    }
}
