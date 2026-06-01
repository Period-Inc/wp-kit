<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\MetaBox;
use Period\WpKit\WordPress\PostAssets;
use Period\WpKit\WordPress\PostAssetsCompileResult;
use Period\WpKit\WordPress\PostAssetsCompileService;
use Period\WpKit\WordPress\PostAssetsCompilerInterface;
use Period\WpKit\WordPress\PostMetaManager;

final class MetaBoxTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        $_POST = [];

        global $METABOX_TEST_META_UPDATES, $PERIOD_WP_FILTER_VALUES, $PERIOD_WP_ENQUEUED_SCRIPTS;
        $METABOX_TEST_META_UPDATES = [];
        $PERIOD_WP_FILTER_VALUES = [];
        $PERIOD_WP_ENQUEUED_SCRIPTS = [];
    }

    private function findEnqueuedScript(string $handle): ?array
    {
        global $PERIOD_WP_ENQUEUED_SCRIPTS;
        foreach ((array) $PERIOD_WP_ENQUEUED_SCRIPTS as $script) {
            if ($script['handle'] === $handle) {
                return $script;
            }
        }
        return null;
    }

    public function testRegisterDoesNotFailWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testSaveDoesNotFailWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A'],
            ],
        ]);

        $this->assertNull($metaBox->save(123));
    }

    public function testFieldsReturnsConfiguredFields(): void
    {
        $fields = [
            ['name' => 'field_a', 'label' => 'Field A'],
            ['name' => 'field_b', 'label' => 'Field B'],
        ];

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => $fields,
        ]);

        $this->assertSame($fields, $metaBox->fields());
    }

    public function testIdReturnsConfiguredId(): void
    {
        $metaBox = new MetaBox([
            'id' => 'custom_box',
            'title' => 'Custom Box',
            'post_type' => 'post',
        ]);

        $this->assertSame('custom_box', $metaBox->id());
    }

    public function testMissingConfigIsSafe(): void
    {
        $metaBox = new MetaBox([]);

        $this->assertSame('', $metaBox->id());
        $this->assertSame([], $metaBox->fields());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPress(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'field_a', 'label' => 'Field A', 'type' => 'text'],
                ['name' => 'field_b', 'label' => 'Field B', 'type' => 'checkbox'],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('name="field_a"', $output);
        $this->assertStringContainsString('name="field_b"', $output);
    }

    public function testRegisterDoesNotFailWithoutWordPressForImageField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'image_box',
            'title' => 'Image Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRegisterDoesNotFailWithoutWordPressForMediaField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRegisterDoesNotFailWithoutWordPressForGalleryField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPressForGalleryField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('data-period-wp-gallery', $output);
        $this->assertStringContainsString('name="gallery_ids"', $output);
    }

    public function testGallerySanitizeJsonArrayReturnsNumericArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '[1,2,3]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([1, 2, 3], $method->invoke($metaBox, $galleryField));
    }

    public function testGallerySanitizeInvalidJsonReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '[1,2,';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([], $method->invoke($metaBox, $galleryField));
    }

    public function testGallerySanitizeEmptyStringReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'gallery_box',
            'title' => 'Gallery Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'gallery_ids', 'type' => 'gallery'],
            ],
        ]);

        $_POST['gallery_ids'] = '';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $galleryField = ['name' => 'gallery_ids', 'type' => 'gallery'];

        $this->assertSame([], $method->invoke($metaBox, $galleryField));
    }

    public function testRegisterDoesNotFailWithoutWordPressForRepeaterField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $this->assertNull($metaBox->register());
    }

    public function testRenderOutputsMarkupEvenWithoutWordPressForRepeaterField(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 1]);
        $output = ob_get_clean();

        $this->assertStringContainsString('data-period-wp-repeater', $output);
        $this->assertStringContainsString('name="repeater_entries"', $output);
    }

    public function testRepeaterSanitizeJsonArrayReturnsArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A"},{"title":"B"}]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([['title' => 'A'], ['title' => 'B']], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterSanitizeInvalidJsonReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A"}';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterSanitizeEmptyStringReturnsEmptyArray(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
        ]];

        $this->assertSame([], $method->invoke($metaBox, $repeaterField));
    }

    public function testRepeaterChildFieldSanitizeValues(): void
    {
        $metaBox = new MetaBox([
            'id' => 'repeater_box',
            'title' => 'Repeater Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
                    ['name' => 'title', 'type' => 'text'],
                    ['name' => 'enabled', 'type' => 'checkbox'],
                    ['name' => 'image_id', 'type' => 'image'],
                ]],
            ],
        ]);

        $_POST['repeater_entries'] = '[{"title":"A","enabled":"1","image_id":"123"}]';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $repeaterField = ['name' => 'repeater_entries', 'type' => 'repeater', 'fields' => [
            ['name' => 'title', 'type' => 'text'],
            ['name' => 'enabled', 'type' => 'checkbox'],
            ['name' => 'image_id', 'type' => 'image'],
        ]];

        $this->assertSame([['title' => 'A', 'enabled' => '1', 'image_id' => '123']], $method->invoke($metaBox, $repeaterField));
    }

    public function testImageAndMediaSanitizeNumericValues(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        $_POST['image_id'] = '123';
        $_POST['media_id'] = '456';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $imageField = ['name' => 'image_id', 'type' => 'image'];
        $mediaField = ['name' => 'media_id', 'type' => 'media'];

        $this->assertSame('123', $method->invoke($metaBox, $imageField));
        $this->assertSame('456', $method->invoke($metaBox, $mediaField));
    }

    public function testImageAndMediaSanitizeEmptyValueReturnsEmptyString(): void
    {
        $metaBox = new MetaBox([
            'id' => 'media_box',
            'title' => 'Media Box',
            'post_type' => 'post',
            'fields' => [
                ['name' => 'image_id', 'type' => 'image'],
                ['name' => 'media_id', 'type' => 'media'],
            ],
        ]);

        unset($_POST['image_id'], $_POST['media_id']);

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $imageField = ['name' => 'image_id', 'type' => 'image'];
        $mediaField = ['name' => 'media_id', 'type' => 'media'];

        $this->assertSame('', $method->invoke($metaBox, $imageField));
        $this->assertSame('', $method->invoke($metaBox, $mediaField));
    }

    // --- $postData routing tests ---

    public function testSanitizeFieldValueUsesPostDataWhenProvided(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [['name' => 'my_field', 'type' => 'text']],
        ]);

        $_POST['my_field'] = 'from_global_post';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $result = $method->invoke($metaBox, ['name' => 'my_field', 'type' => 'text'], ['my_field' => 'from_postdata']);

        $this->assertSame('from_postdata', $result);
    }

    public function testSanitizeFieldValueFallsBackToGlobalPostWhenPostDataIsEmpty(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test Box',
            'post_type' => 'post',
            'fields' => [['name' => 'my_field', 'type' => 'text']],
        ]);

        $_POST['my_field'] = 'from_global_post';

        $reflection = new \ReflectionClass($metaBox);
        $method = $reflection->getMethod('sanitizeFieldValue');

        $result = $method->invoke($metaBox, ['name' => 'my_field', 'type' => 'text'], []);

        $this->assertSame('from_global_post', $result);
    }

    public function testSaveNonceIsReadFromPostDataNotFromGlobalPost(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $metaBox = new MetaBox([
            'id' => 'nonce_test',
            'title' => 'Nonce Test',
            'post_type' => 'post',
            'fields' => [['name' => 'my_field', 'type' => 'text']],
        ]);

        // nonce は $_POST にのみ存在。$postData にはない。
        $_POST['nonce_test_nonce'] = 'any_value';
        $_POST['my_field'] = 'from_post';

        // $postData が非空のため $_POST は参照されず、nonce チェックが失敗して早期リターンする
        $metaBox->save(1, ['my_field' => 'from_postdata']);

        $this->assertEmpty($METABOX_TEST_META_UPDATES);
    }

    // --- enqueueMedia() tests ---

    public function testEnqueueMediaRegistersScriptWhenFilterProvidesUrl(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['period_wp_metabox_js_url'] = 'https://example.com/metabox.js';

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [['name' => 'image_id', 'type' => 'image']],
        ]);

        $metaBox->enqueueMedia();

        $script = $this->findEnqueuedScript('period-wp-metabox');
        $this->assertNotNull($script);
        $this->assertSame('https://example.com/metabox.js', $script['src']);
        $this->assertTrue($script['in_footer']);
    }

    public function testEnqueueMediaSkipsScriptWhenFilterReturnsNull(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [['name' => 'image_id', 'type' => 'image']],
        ]);

        $metaBox->enqueueMedia();

        $this->assertNull($this->findEnqueuedScript('period-wp-metabox'));
    }

    public function testEnqueueMediaSkipsScriptWhenFilterReturnsEmptyString(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['period_wp_metabox_js_url'] = '';

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [['name' => 'image_id', 'type' => 'image']],
        ]);

        $metaBox->enqueueMedia();

        $this->assertNull($this->findEnqueuedScript('period-wp-metabox'));
    }

    public function testEnqueueMediaVersionMatchesFiletimeWhenJsFileExists(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['period_wp_metabox_js_url'] = 'https://example.com/metabox.js';

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [['name' => 'image_id', 'type' => 'image']],
        ]);

        $metaBox->enqueueMedia();

        $jsPath = dirname(__DIR__, 3) . '/assets/js/period-wp-metabox.js';
        $script = $this->findEnqueuedScript('period-wp-metabox');
        $this->assertNotNull($script);

        $expectedVersion = file_exists($jsPath) ? (filemtime($jsPath) ?: null) : null;
        $this->assertSame($expectedVersion, $script['ver']);
    }

    public function testEnqueueMediaDoesNotErrorWhenJsFileIsMissing(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['period_wp_metabox_js_url'] = 'https://example.com/metabox.js';

        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [['name' => 'image_id', 'type' => 'image']],
        ]);

        // enqueueMedia() は assets ファイルの有無にかかわらず例外を出さない。
        // ファイルが存在しない場合は version = null で enqueue される。
        $metaBox->enqueueMedia();

        $script = $this->findEnqueuedScript('period-wp-metabox');
        $this->assertNotNull($script);

        $jsPath = dirname(__DIR__, 3) . '/assets/js/period-wp-metabox.js';
        if (!file_exists($jsPath)) {
            $this->assertNull($script['ver']);
        }
    }

    public function testPrintMediaScriptProducesNoOutput(): void
    {
        $metaBox = new MetaBox([
            'id' => 'test_box',
            'title' => 'Test',
            'post_type' => 'post',
            'fields' => [],
        ]);

        ob_start();
        $metaBox->printMediaScript();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    // --- label fallback tests ---

    public function testButtonLabelFallbackForGallery(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'imgs', 'type' => 'gallery']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Select images', $output);
    }

    public function testButtonLabelFallbackForRepeater(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'items', 'type' => 'repeater', 'fields' => [
                ['name' => 'title', 'type' => 'text'],
            ]]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Add', $output);
    }

    public function testButtonLabelFallbackForImage(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'img', 'type' => 'image']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Select image', $output);
    }

    public function testButtonLabelFallbackForMedia(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'file', 'type' => 'media']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Select', $output);
        $this->assertStringNotContainsString('Select image', $output);
        $this->assertStringNotContainsString('Select images', $output);
    }

    public function testClearLabelFallback(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'img', 'type' => 'image']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Clear', $output);
    }

    public function testRemoveLabelDefaultIsRemove(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'items', 'type' => 'repeater', 'fields' => [
                ['name' => 'title', 'type' => 'text'],
            ]]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('period-wp-metabox-repeater-remove', $output);
        $this->assertStringNotContainsString('削除', $output);
    }

    public function testRemoveLabelCustomValue(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'items', 'type' => 'repeater', 'remove_label' => '削除', 'fields' => [
                ['name' => 'title', 'type' => 'text'],
            ]]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('>削除<', $output);
    }

    public function testButtonLabelCustomValueOverridesFallback(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [['name' => 'imgs', 'type' => 'gallery', 'button_label' => 'ギャラリーを選択']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('ギャラリーを選択', $output);
        $this->assertStringNotContainsString('Select images', $output);
    }

    // --- labels array tests ---

    public function testLabelsSelectImagesAppliedToGallery(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'imgs', 'type' => 'gallery',
                'labels' => ['select_images' => 'Choose photos'],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Choose photos', $output);
        $this->assertStringNotContainsString('Select images', $output);
    }

    public function testLabelsClearAppliedToClearButton(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'img', 'type' => 'image',
                'labels' => ['clear' => 'Reset'],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Reset', $output);
        $this->assertStringNotContainsString('Clear', $output);
    }

    public function testLabelsAddAppliedToRepeaterAddButton(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'items', 'type' => 'repeater',
                'labels' => ['add' => 'New item'],
                'fields' => [['name' => 'title', 'type' => 'text']],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('New item', $output);
        $this->assertStringNotContainsString('>Add<', $output);
    }

    public function testLabelsRemoveAppliedToRepeaterRemoveButton(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'items', 'type' => 'repeater',
                'labels' => ['remove' => 'Delete'],
                'fields' => [['name' => 'title', 'type' => 'text']],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('>Delete<', $output);
        $this->assertStringNotContainsString('>Remove<', $output);
    }

    public function testLabelsTakesPrecedenceOverButtonLabel(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'imgs', 'type' => 'gallery',
                'button_label' => 'Legacy label',
                'labels' => ['select_images' => 'New label'],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('New label', $output);
        $this->assertStringNotContainsString('Legacy label', $output);
    }

    public function testLegacyButtonLabelStillWorksWithoutLabels(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [['name' => 'imgs', 'type' => 'gallery', 'button_label' => 'Pick images']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Pick images', $output);
    }

    public function testLegacyClearLabelStillWorksWithoutLabels(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [['name' => 'img', 'type' => 'image', 'clear_label' => 'Wipe']],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Wipe', $output);
    }

    public function testLegacyRemoveLabelStillWorksWithoutLabels(): void
    {
        $metaBox = new MetaBox([
            'id' => 'box', 'title' => 'Box', 'post_type' => 'post',
            'fields' => [[
                'name' => 'items', 'type' => 'repeater',
                'remove_label' => 'Erase',
                'fields' => [['name' => 'title', 'type' => 'text']],
            ]],
        ]);

        ob_start();
        $metaBox->render((object) ['ID' => 0]);
        $output = ob_get_clean();

        $this->assertStringContainsString('>Erase<', $output);
    }

    public function testSaveFieldValueFromPostDataTakesPrecedenceOverGlobalPost(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $metaBox = new MetaBox([
            'id' => 'save_test',
            'title' => 'Save Test',
            'post_type' => 'post',
            'fields' => [['name' => 'my_field', 'type' => 'text']],
        ]);

        $_POST['save_test_nonce'] = 'any_value';
        $_POST['my_field'] = 'from_post';

        // $postData に nonce とフィールド値を両方渡す
        $metaBox->save(1, ['save_test_nonce' => 'any_value', 'my_field' => 'from_postdata']);

        $this->assertNotEmpty($METABOX_TEST_META_UPDATES);
        $this->assertSame(['from_postdata'], $METABOX_TEST_META_UPDATES[0]['value']);
    }

    // --- PostAssets compile integration tests ---

    private function makeCompileService(PostAssetsCompilerInterface $compiler): PostAssetsCompileService
    {
        return new PostAssetsCompileService(new PostMetaManager(), $compiler);
    }

    private function makeMetaBoxWithCompileService(PostAssetsCompileService $service): MetaBox
    {
        return new MetaBox([
            'id' => 'assets_box',
            'title' => 'Assets',
            'post_type' => 'post',
            'fields' => [
                ['name' => PostAssets::CSS_CODE, 'type' => 'textarea'],
                ['name' => PostAssets::JS_CODE,  'type' => 'textarea'],
            ],
            'post_assets_compile_service' => $service,
        ]);
    }

    public function testCompileServiceIsCalledWhenCssCodeIsSaved(): void
    {
        $compiler = $this->createMock(PostAssetsCompilerInterface::class);
        $compiler->expects($this->once())
            ->method('compile')
            ->willReturn(new PostAssetsCompileResult(true, 'body{}'));

        $metaBox = $this->makeMetaBoxWithCompileService($this->makeCompileService($compiler));

        $metaBox->save(1, [
            'assets_box_nonce'    => 'any_value',
            PostAssets::CSS_CODE  => 'body { color: red; }',
        ]);
    }

    public function testCompileSourceMatchesSanitizedInputValue(): void
    {
        $capturedSource = null;

        $compiler = $this->createMock(PostAssetsCompilerInterface::class);
        $compiler->expects($this->once())
            ->method('compile')
            ->willReturnCallback(function (string $src) use (&$capturedSource): PostAssetsCompileResult {
                $capturedSource = $src;
                return new PostAssetsCompileResult(true, '');
            });

        $metaBox = $this->makeMetaBoxWithCompileService($this->makeCompileService($compiler));

        $metaBox->save(1, [
            'assets_box_nonce'    => 'any_value',
            PostAssets::CSS_CODE  => 'body { margin: 0; }',
        ]);

        $this->assertSame('body { margin: 0; }', $capturedSource);
    }

    public function testCompileServiceIsNotCalledForNonCssField(): void
    {
        $compiler = $this->createMock(PostAssetsCompilerInterface::class);
        $compiler->expects($this->never())
            ->method('compile');

        $metaBox = new MetaBox([
            'id' => 'assets_box',
            'title' => 'Assets',
            'post_type' => 'post',
            'fields' => [
                ['name' => PostAssets::JS_CODE, 'type' => 'textarea'],
            ],
            'post_assets_compile_service' => $this->makeCompileService($compiler),
        ]);

        $metaBox->save(1, [
            'assets_box_nonce'  => 'any_value',
            PostAssets::JS_CODE => 'console.log("ok");',
        ]);
    }

    public function testMetaBoxSaveContinuesWhenCompileFails(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $compiler = $this->createMock(PostAssetsCompilerInterface::class);
        $compiler->method('compile')
            ->willReturn(new PostAssetsCompileResult(false, '', 'syntax error'));

        $metaBox = $this->makeMetaBoxWithCompileService($this->makeCompileService($compiler));

        $metaBox->save(1, [
            'assets_box_nonce'    => 'any_value',
            PostAssets::CSS_CODE  => 'body {',
            PostAssets::JS_CODE   => 'console.log("ok");',
        ]);

        // Both fields must have been persisted despite compile failure
        $savedKeys = array_column($METABOX_TEST_META_UPDATES, 'key');
        $this->assertContains(PostAssets::CSS_CODE, $savedKeys);
        $this->assertContains(PostAssets::JS_CODE, $savedKeys);
    }

    public function testMetaBoxSaveFlowIsUnaffectedWithoutCompileService(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        $metaBox = new MetaBox([
            'id' => 'plain_box',
            'title' => 'Plain',
            'post_type' => 'post',
            'fields' => [
                ['name' => PostAssets::CSS_CODE, 'type' => 'textarea'],
            ],
        ]);

        $metaBox->save(1, [
            'plain_box_nonce'     => 'any_value',
            PostAssets::CSS_CODE  => 'body {}',
        ]);

        $savedKeys = array_column($METABOX_TEST_META_UPDATES, 'key');
        $this->assertContains(PostAssets::CSS_CODE, $savedKeys);
    }

    /** @runInSeparateProcess */
    public function testRepeaterTrimsOnlyNewTrailingEmptyRow(): void
    {
        global $METABOX_TEST_META_UPDATES;
        $METABOX_TEST_META_UPDATES = [];

        // 既存は2件と仮定
        eval('function get_post_meta($id,$key,$single){ return ["a","b"]; }');

        $metaBox = new MetaBox([
            'id' => 'box',
            'title' => 'Box',
            'post_type' => 'post',
            'fields' => [[
                'name' => 'items',
                'type' => 'repeater',
                'fields' => [['name' => 'title', 'type' => 'text']],
            ]],
        ]);

        $_POST['box_nonce'] = 'x';

        // 3件目が空 → これは無視されるべき
        $metaBox->save(1, [
            'box_nonce' => 'x',
            'items' => [
                ['title' => 'a'],
                ['title' => 'b'],
                ['title' => ''],
            ],
        ]);

        $this->assertSame([
            ['title' => 'a'],
            ['title' => 'b'],
        ], $METABOX_TEST_META_UPDATES[0]['value']);
    }

}
