<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\PostClassEnhancer;

final class PostClassEnhancerTest extends TestCase
{
    public function testRegisterDoesNotFailWithoutWordPress(): void
    {
        $enhancer = new PostClassEnhancer();

        $this->assertNull($enhancer->register());
    }

    public function testAddClassesKeepsExistingClasses(): void
    {
        $enhancer = new PostClassEnhancer();

        $result = $enhancer->addClasses(['existing'], '', null);

        $this->assertContains('existing', $result);
    }

    public function testAddClassesAddsArticleAndPosts(): void
    {
        $enhancer = new PostClassEnhancer();

        $result = $enhancer->addClasses([], '', null);

        $this->assertContains('article', $result);
        $this->assertContains('posts', $result);
    }

    public function testAddClassesDoesNotFailWithoutPostInformation(): void
    {
        $enhancer = new PostClassEnhancer();

        $result = $enhancer->addClasses([], '', null);

        $this->assertIsArray($result);
    }

    public function testAddClassesRemovesDuplicateClasses(): void
    {
        $enhancer = new PostClassEnhancer();

        $result = $enhancer->addClasses(['article', 'article', 'posts'], '', null);

        $this->assertSame(['article', 'posts'], $result);
    }
}
