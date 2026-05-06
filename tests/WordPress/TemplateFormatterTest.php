<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\TemplateFormatter;
use Period\WpFramework\Support\TemplateFormatter as BaseTemplateFormatter;

final class TemplateFormatterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES = [];
    }

    public function testIsSubclassOfSupportTemplateFormatter(): void
    {
        $formatter = new TemplateFormatter();

        $this->assertInstanceOf(BaseTemplateFormatter::class, $formatter);
    }

    public function testBasicReplacementStillWorks(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ title }}', ['title' => 'Hello']);

        $this->assertSame('Hello', $result);
    }

    public function testFilterModifiesResultWhenSpecified(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['my_title_filter'] = 'Filtered Result';

        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ title }}', ['title' => 'Hello'], 'my_title_filter');

        $this->assertSame('Filtered Result', $result);
    }

    public function testFilterSkippedWhenNotSpecified(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ title }}', ['title' => 'Hello']);

        $this->assertSame('Hello', $result);
    }

    public function testFilterPassesAndReturnsString(): void
    {
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['check_filter'] = 'ok';

        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ x }}', ['x' => 'v'], 'check_filter');

        $this->assertSame('ok', $result);
    }
}
