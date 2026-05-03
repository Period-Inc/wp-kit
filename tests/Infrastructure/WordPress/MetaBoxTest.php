<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Infrastructure\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Infrastructure\WordPress\MetaBox;

final class MetaBoxTest extends TestCase
{
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
}
