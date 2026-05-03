<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Infrastructure\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Infrastructure\WordPress\PostTypeRegistrar;

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
}
