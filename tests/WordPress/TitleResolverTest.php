<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\SiteInfo;
use Period\WpKit\WordPress\TitleResolver;

final class TitleResolverTest extends TestCase
{
    public function testTitleDoesNotThrowWithoutWordPress(): void
    {
        $resolver = new TitleResolver();

        $this->assertIsString($resolver->title());
    }

    public function testTitleReturnsEmptyStringWithoutWordPressAndNoFallback(): void
    {
        $resolver = new TitleResolver();

        $this->assertSame('', $resolver->title());
    }

    public function testTitleReturnsFallbackWhenProvided(): void
    {
        $resolver = new TitleResolver();

        $this->assertSame('My Page', $resolver->title(['fallback' => 'My Page']));
    }

    public function testTitleIgnoresNonStringFallback(): void
    {
        $resolver = new TitleResolver();

        $result = $resolver->title(['fallback' => 123]);
        $this->assertIsString($result);
    }

    public function testTitleIgnoresEmptyStringFallback(): void
    {
        $resolver = new TitleResolver();

        $this->assertSame('', $resolver->title(['fallback' => '']));
    }

    public function testSiteTitleDoesNotThrowWithoutWordPress(): void
    {
        $resolver = new TitleResolver();

        $this->assertIsString($resolver->siteTitle());
    }

    public function testSiteTitleReturnsEmptyStringWhenBothEmpty(): void
    {
        $resolver = new TitleResolver();

        $this->assertSame('', $resolver->siteTitle());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSiteTitleJoinsTitleAndSiteName(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return 'About Us'; }
function get_bloginfo($key) { return $key === 'name' ? 'My Site' : ''; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('About Us | My Site', $resolver->siteTitle());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSiteTitleUsesCustomSeparator(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return 'News'; }
function get_bloginfo($key) { return $key === 'name' ? 'My Site' : ''; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('News – My Site', $resolver->siteTitle(' – '));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSiteTitleDoesNotDuplicateWhenTitleAndNameAreEqual(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return 'My Site'; }
function get_bloginfo($key) { return $key === 'name' ? 'My Site' : ''; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('My Site', $resolver->siteTitle());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSiteTitleReturnsNameWhenTitleIsEmpty(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return ''; }
function get_bloginfo($key) { return $key === 'name' ? 'My Site' : ''; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('My Site', $resolver->siteTitle());
    }

    /**
     * @runInSeparateProcess
     */
    public function testTitleReturnsNotFoundWhen404(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return ''; }
function is_404() { return true; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('Not Found', $resolver->title());
    }

    /**
     * @runInSeparateProcess
     */
    public function testTitleReturnsSearchQueryWhenIsSearch(): void
    {
        eval(<<<'PHP'
function wp_get_document_title() { return ''; }
function is_404() { return false; }
function is_search() { return true; }
function get_search_query() { return 'hello'; }
PHP
        );

        $resolver = new TitleResolver();

        $this->assertSame('Search: hello', $resolver->title());
    }

    public function testAcceptsExternalSiteInfo(): void
    {
        $siteInfo = new SiteInfo();
        $resolver = new TitleResolver($siteInfo);

        $this->assertIsString($resolver->title());
    }
}
