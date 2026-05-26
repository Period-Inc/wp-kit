<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetAttachmentEditFieldRenderer;
use Period\WpFramework\WordPress\Access\AssetAttachmentEditFieldSaver;
use Period\WpFramework\WordPress\Access\AssetAttachmentMetaReader;
use Period\WpFramework\WordPress\Access\WordPressAssetAttachmentEditFieldHookRegistrar;

final class AssetAttachmentEditFieldTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeReader(
        bool $protected = false,
        ?string $path   = null,
        ?string $url    = null,
    ): AssetAttachmentMetaReader {
        return new AssetAttachmentMetaReader(
            function (int $id, string $key, bool $single) use ($protected, $path, $url): mixed {
                return match ($key) {
                    '_period_asset_protected'      => $protected ? '1' : '',
                    '_period_asset_protected_path' => $path ?? '',
                    '_period_asset_delivery_url'   => $url ?? '',
                    default                        => '',
                };
            },
        );
    }

    private function makeRenderer(
        bool $protected = false,
        ?string $path   = null,
        ?string $url    = null,
    ): AssetAttachmentEditFieldRenderer {
        return new AssetAttachmentEditFieldRenderer($this->makeReader($protected, $path, $url));
    }

    /** @param array<array{int, string, mixed}> $calls */
    private function makeSaver(array &$calls = []): AssetAttachmentEditFieldSaver
    {
        return new AssetAttachmentEditFieldSaver(
            function (int $id, string $key, mixed $value) use (&$calls): void {
                $calls[] = [$id, $key, $value];
            },
        );
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentEditFieldRenderer — checkbox state
    // -----------------------------------------------------------------------

    public function testRenderContainsCheckboxInput(): void
    {
        $html = $this->makeRenderer()->render(1);

        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function testRenderCheckboxHasValue1(): void
    {
        $html = $this->makeRenderer()->render(1);

        $this->assertStringContainsString('value="1"', $html);
    }

    public function testRenderCheckedWhenProtected(): void
    {
        $html = $this->makeRenderer(protected: true)->render(1);

        $this->assertStringContainsString(' checked', $html);
    }

    public function testRenderNotCheckedWhenNotProtected(): void
    {
        $html = $this->makeRenderer(protected: false)->render(1);

        $this->assertStringNotContainsString(' checked', $html);
    }

    public function testRenderCheckboxNameContainsAttachmentId(): void
    {
        $html = $this->makeRenderer()->render(42);

        $this->assertStringContainsString('attachments[42]', $html);
    }

    public function testRenderCheckboxNameContainsFieldKey(): void
    {
        $html = $this->makeRenderer()->render(1);

        $this->assertStringContainsString('period_asset_access_protected', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentEditFieldRenderer — readonly fields
    // -----------------------------------------------------------------------

    public function testRenderProtectedPathIsDisplayed(): void
    {
        $html = $this->makeRenderer(path: '/protected-uploads/file.pdf')->render(1);

        $this->assertStringContainsString('/protected-uploads/file.pdf', $html);
    }

    public function testRenderDeliveryUrlIsDisplayed(): void
    {
        $html = $this->makeRenderer(url: 'https://example.com/delivery/file.pdf')->render(1);

        $this->assertStringContainsString('https://example.com/delivery/file.pdf', $html);
    }

    public function testRenderEmptyValueWhenProtectedPathIsNull(): void
    {
        $html = $this->makeRenderer(path: null)->render(1);

        $this->assertStringContainsString('value=""', $html);
    }

    public function testRenderEmptyValueWhenDeliveryUrlIsNull(): void
    {
        $html = $this->makeRenderer(url: null)->render(1);

        $this->assertStringContainsString('value=""', $html);
    }

    public function testRenderReadonlyAttributeOnPathField(): void
    {
        $html = $this->makeRenderer(path: '/some/path.pdf')->render(1);

        $this->assertStringContainsString('readonly', $html);
    }

    public function testRenderReadonlyAttributeOnUrlField(): void
    {
        $html = $this->makeRenderer(url: 'https://example.com/file.pdf')->render(1);

        $occurrences = substr_count($html, 'readonly');
        $this->assertGreaterThanOrEqual(2, $occurrences);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentEditFieldRenderer — HTML escaping
    // -----------------------------------------------------------------------

    public function testRenderEscapesProtectedPath(): void
    {
        $xss  = '<script>alert(1)</script>';
        $html = $this->makeRenderer(path: $xss)->render(1);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testRenderEscapesDeliveryUrl(): void
    {
        $xss  = '"onload="alert(1)';
        $html = $this->makeRenderer(url: $xss)->render(1);

        $this->assertStringNotContainsString('"onload="', $html);
        $this->assertStringContainsString('&quot;onload=', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentEditFieldRenderer — no inline styles
    // -----------------------------------------------------------------------

    public function testRenderHasNoInlineStyles(): void
    {
        $html = $this->makeRenderer(true, '/path/file.pdf', 'https://example.com/file.pdf')->render(1);

        $this->assertStringNotContainsString('style=', $html);
    }

    // -----------------------------------------------------------------------
    // AssetAttachmentEditFieldSaver — protected flag
    // -----------------------------------------------------------------------

    public function testSaveSetsProtectedTo1WhenCheckboxSubmitted(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(10, ['period_asset_access_protected' => '1']);

        $this->assertCount(1, $calls);
        $this->assertSame('1', $calls[0][2]);
    }

    public function testSaveSetsProtectedTo0WhenCheckboxAbsent(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(10, []);

        $this->assertCount(1, $calls);
        $this->assertSame('0', $calls[0][2]);
    }

    public function testSaveSetsProtectedTo0WhenCheckboxValueIsNot1(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(10, ['period_asset_access_protected' => '0']);

        $this->assertSame('0', $calls[0][2]);
    }

    public function testSaveUsesCorrectMetaKey(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(5, ['period_asset_access_protected' => '1']);

        $this->assertSame('_period_asset_protected', $calls[0][1]);
    }

    public function testSavePassesCorrectAttachmentId(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(99, ['period_asset_access_protected' => '1']);

        $this->assertSame(99, $calls[0][0]);
    }

    public function testSaveMakesExactlyOneMetaCall(): void
    {
        $calls = [];
        $saver = $this->makeSaver($calls);

        $saver->save(1, ['period_asset_access_protected' => '1']);

        $this->assertCount(1, $calls);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentEditFieldHookRegistrar — hook registration
    // -----------------------------------------------------------------------

    private function makeRegistrar(array &$filters = []): WordPressAssetAttachmentEditFieldHookRegistrar
    {
        return new WordPressAssetAttachmentEditFieldHookRegistrar(
            $this->makeRenderer(false, '/protected/file.pdf', 'https://example.com/file.pdf'),
            $this->makeSaver(),
            function (string $hook, callable $cb, int $priority, int $accepted = 1) use (&$filters): void {
                $filters[] = ['hook' => $hook, 'cb' => $cb, 'priority' => $priority, 'accepted' => $accepted];
            },
            function (): void {},
        );
    }

    public function testRegisterHooksAttachmentFieldsToEditFilter(): void
    {
        $filters = [];
        $reg     = $this->makeRegistrar($filters);
        $reg->register();

        $hooks = array_column($filters, 'hook');
        $this->assertContains('attachment_fields_to_edit', $hooks);
    }

    public function testRegisterHooksAttachmentFieldsToSaveFilter(): void
    {
        $filters = [];
        $reg     = $this->makeRegistrar($filters);
        $reg->register();

        $hooks = array_column($filters, 'hook');
        $this->assertContains('attachment_fields_to_save', $hooks);
    }

    public function testRegisterHooksExactlyTwoFilters(): void
    {
        $filters = [];
        $reg     = $this->makeRegistrar($filters);
        $reg->register();

        $this->assertCount(2, $filters);
    }

    public function testEditFilterAccepts2Args(): void
    {
        $filters = [];
        $reg     = $this->makeRegistrar($filters);
        $reg->register();

        $editEntry = current(array_filter($filters, fn($f) => $f['hook'] === 'attachment_fields_to_edit'));
        $this->assertSame(2, $editEntry['accepted']);
    }

    public function testSaveFilterAccepts2Args(): void
    {
        $filters = [];
        $reg     = $this->makeRegistrar($filters);
        $reg->register();

        $saveEntry = current(array_filter($filters, fn($f) => $f['hook'] === 'attachment_fields_to_save'));
        $this->assertSame(2, $saveEntry['accepted']);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentEditFieldHookRegistrar — edit filter callback
    // -----------------------------------------------------------------------

    private function getCallback(string $hook, array &$filters): callable
    {
        $reg = $this->makeRegistrar($filters);
        $reg->register();

        $entry = current(array_filter($filters, fn($f) => $f['hook'] === $hook));
        return $entry['cb'];
    }

    public function testEditFilterCallbackAddsFieldToFormFields(): void
    {
        $filters  = [];
        $cb       = $this->getCallback('attachment_fields_to_edit', $filters);
        $post     = (object) ['ID' => 42];

        $result = $cb([], $post);

        $this->assertArrayHasKey('period_asset_access', $result);
    }

    public function testEditFilterCallbackPreservesExistingFields(): void
    {
        $filters  = [];
        $cb       = $this->getCallback('attachment_fields_to_edit', $filters);
        $post     = (object) ['ID' => 1];
        $existing = ['title' => ['label' => 'Title', 'input' => 'text', 'value' => 'Hello']];

        $result = $cb($existing, $post);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('period_asset_access', $result);
    }

    public function testEditFilterFieldHasHtmlInput(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_edit', $filters);
        $post    = (object) ['ID' => 1];

        $result = $cb([], $post);

        $this->assertSame('html', $result['period_asset_access']['input']);
    }

    public function testEditFilterFieldHasLabel(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_edit', $filters);
        $post    = (object) ['ID' => 1];

        $result = $cb([], $post);

        $this->assertNotEmpty($result['period_asset_access']['label']);
    }

    public function testEditFilterFieldHasNonEmptyHtml(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_edit', $filters);
        $post    = (object) ['ID' => 1];

        $result = $cb([], $post);

        $this->assertNotEmpty($result['period_asset_access']['html']);
    }

    public function testEditFilterFieldHtmlContainsCheckbox(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_edit', $filters);
        $post    = (object) ['ID' => 5];

        $result = $cb([], $post);

        $this->assertStringContainsString('type="checkbox"', $result['period_asset_access']['html']);
    }

    public function testEditFilterPassesAttachmentIdToRenderer(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_edit', $filters);
        $post    = (object) ['ID' => 77];

        $result = $cb([], $post);

        $this->assertStringContainsString('attachments[77]', $result['period_asset_access']['html']);
    }

    // -----------------------------------------------------------------------
    // WordPressAssetAttachmentEditFieldHookRegistrar — save filter callback
    // -----------------------------------------------------------------------

    public function testSaveFilterCallsSaver(): void
    {
        $metaCalls = [];
        $renderer  = $this->makeRenderer();
        $saver     = $this->makeSaver($metaCalls);
        $filters   = [];

        $reg = new WordPressAssetAttachmentEditFieldHookRegistrar(
            $renderer,
            $saver,
            function (string $hook, callable $cb, int $priority, int $accepted = 1) use (&$filters): void {
                $filters[] = ['hook' => $hook, 'cb' => $cb];
            },
            function (): void {},
        );
        $reg->register();

        $saveEntry = current(array_filter($filters, fn($f) => $f['hook'] === 'attachment_fields_to_save'));
        $cb        = $saveEntry['cb'];

        $post       = ['ID' => 20];
        $attachment = ['period_asset_access_protected' => '1'];
        $cb($post, $attachment);

        $this->assertCount(1, $metaCalls);
        $this->assertSame(20, $metaCalls[0][0]);
        $this->assertSame('_period_asset_protected', $metaCalls[0][1]);
        $this->assertSame('1', $metaCalls[0][2]);
    }

    public function testSaveFilterReturnsOriginalPost(): void
    {
        $filters = [];
        $cb      = $this->getCallback('attachment_fields_to_save', $filters);

        $post   = ['ID' => 10, 'post_title' => 'My File'];
        $result = $cb($post, []);

        $this->assertSame($post, $result);
    }

    public function testSaveFilterSetsProtectedTo0WhenCheckboxAbsent(): void
    {
        $metaCalls = [];
        $renderer  = $this->makeRenderer();
        $saver     = $this->makeSaver($metaCalls);
        $filters   = [];

        $reg = new WordPressAssetAttachmentEditFieldHookRegistrar(
            $renderer,
            $saver,
            function (string $hook, callable $cb, int $priority, int $accepted = 1) use (&$filters): void {
                $filters[] = ['hook' => $hook, 'cb' => $cb];
            },
            function (): void {},
        );
        $reg->register();

        $saveEntry = current(array_filter($filters, fn($f) => $f['hook'] === 'attachment_fields_to_save'));
        ($saveEntry['cb'])(['ID' => 3], []);

        $this->assertSame('0', $metaCalls[0][2]);
    }
}
