<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\PostTypeRegistrar;

final class PostTypeRegistrarTest extends TestCase
{
    public function testRegisterDoesNotFailWithoutWordPress(): void
    {
        $registrar = new PostTypeRegistrar();

        $this->assertSame($registrar, $registrar->register('news', [
            'label' => 'ニュース',
            'menu_icon' => 'dashicons-media-text',
        ]));
    }

    public function testRegisterTaxonomyDoesNotFailWithoutWordPress(): void
    {
        $registrar = new PostTypeRegistrar();

        $this->assertSame($registrar, $registrar->registerTaxonomy('news_category', 'news', [
            'label' => 'カテゴリー',
        ]));
    }

    public function testBootDoesNotFailWithoutWordPress(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->register('news', [
            'label' => 'ニュース',
        ]);
        $registrar->registerTaxonomy('news_category', 'news', [
            'label' => 'カテゴリー',
        ]);

        $this->assertNull($registrar->boot());
    }

    public function testRegisterReturnsSelfForChaining(): void
    {
        $registrar = new PostTypeRegistrar();

        $this->assertSame(
            $registrar,
            $registrar->register('news', ['label' => 'ニュース'])->registerTaxonomy('news_category', 'news', ['label' => 'カテゴリー'])
        );
    }

    public function testMetaBoxDoesNotFailWithoutWordPress(): void
    {
        $registrar = new PostTypeRegistrar();

        $this->assertSame(
            $registrar,
            $registrar->metaBox([
                'id' => 'news_detail',
                'title' => 'ニュース詳細',
                'fields' => [
                    ['name' => 'lead', 'type' => 'textarea'],
                ],
            ])
        );
    }

    public function testRegisterMetaBoxBootDoesNotFailWithoutWordPress(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->register('news', ['label' => 'ニュース'])
            ->metaBox([
                'id' => 'news_detail',
                'title' => 'ニュース詳細',
                'fields' => [
                    ['name' => 'lead', 'type' => 'textarea'],
                ],
            ]);

        $this->assertNull($registrar->boot());
    }

    public function testMetaBoxAddsCurrentPostTypeWhenMissing(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->register('news', ['label' => 'ニュース'])
            ->metaBox([
                'id' => 'news_detail',
                'title' => 'ニュース詳細',
                'fields' => [
                    ['name' => 'lead', 'type' => 'textarea'],
                ],
            ]);

        $this->assertSame('news', $registrar->metaBoxes()[0]['post_type']);
    }

    public function testMetaBoxKeepsExplicitPostType(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->register('news', ['label' => 'ニュース'])
            ->metaBox([
                'id' => 'news_detail',
                'post_type' => 'custom_news',
                'title' => 'ニュース詳細',
                'fields' => [
                    ['name' => 'lead', 'type' => 'textarea'],
                ],
            ]);

        $this->assertSame('custom_news', $registrar->metaBoxes()[0]['post_type']);
    }

    public function testMetaBoxUsesLastRegisteredPostTypeAfterMultipleRegisters(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->register('news', ['label' => 'ニュース'])
            ->register('product', ['label' => '製品'])
            ->metaBox([
                'id' => 'product_detail',
                'title' => '製品詳細',
                'fields' => [
                    ['name' => 'price', 'type' => 'text'],
                ],
            ]);

        $this->assertSame('product', $registrar->metaBoxes()[0]['post_type']);
    }

    public function testMetaBoxDoesNotInferPostTypeWhenCalledBeforeRegister(): void
    {
        $registrar = new PostTypeRegistrar();

        $registrar->metaBox([
            'id' => 'news_detail',
            'title' => 'ニュース詳細',
            'fields' => [
                ['name' => 'lead', 'type' => 'textarea'],
            ],
        ]);
        $registrar->register('news', ['label' => 'ニュース']);

        $this->assertArrayNotHasKey('post_type', $registrar->metaBoxes()[0]);
    }
}
