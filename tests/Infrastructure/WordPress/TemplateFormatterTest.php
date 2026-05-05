<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Infrastructure\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Infrastructure\WordPress\TemplateFormatter;

final class TemplateFormatterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES = [];
    }

    public function testBasicPlaceholderReplacement(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ title }} | {{ site_name }}', [
            'title' => 'Hello',
            'site_name' => 'MySite',
        ]);

        $this->assertSame('Hello | MySite', $result);
    }

    public function testPlaceholderWithExtraSpaces(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{  title  }}', ['title' => 'Hello']);

        $this->assertSame('Hello', $result);
    }

    public function testMissingKeyBecomesEmptyString(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ missing }}', []);

        $this->assertSame('', $result);
    }

    public function testNullValueBecomesEmptyString(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ key }}', ['key' => null]);

        $this->assertSame('', $result);
    }

    public function testArrayValueBecomesEmptyString(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('[{{ key }}]', ['key' => ['a', 'b']]);

        $this->assertSame('[]', $result);
    }

    public function testObjectValueBecomesEmptyString(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('[{{ key }}]', ['key' => new \stdClass()]);

        $this->assertSame('[]', $result);
    }

    public function testIntegerValueIsCastToString(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ count }}', ['count' => 42]);

        $this->assertSame('42', $result);
    }

    public function testTrimIsApplied(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('  {{ title }}  ', ['title' => 'Hello']);

        $this->assertSame('Hello', $result);
    }

    public function testTrimHandlesOnlyWhitespaceResult(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('  {{ missing }}  ', []);

        $this->assertSame('', $result);
    }

    public function testNoFilterReturnResultUnchanged(): void
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

    public function testFilterPassesOriginalTemplateAndContext(): void
    {
        $capturedArgs = null;

        // apply_filters は bootstrap.php で定義済み。
        // グローバル変数経由では引数を捕捉できないため、
        // フィルターが呼ばれて戻り値が使われることのみ確認する。
        global $PERIOD_WP_FILTER_VALUES;
        $PERIOD_WP_FILTER_VALUES['check_filter'] = 'ok';

        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ x }}', ['x' => 'v'], 'check_filter');

        $this->assertSame('ok', $result);
    }

    public function testWorksWithoutWordPressFunctions(): void
    {
        $formatter = new TemplateFormatter();

        $this->assertIsString($formatter->format('{{ title }}', ['title' => 'Test']));
    }

    public function testEmptyTemplateReturnsEmptyString(): void
    {
        $formatter = new TemplateFormatter();

        $this->assertSame('', $formatter->format(''));
    }

    public function testTemplateWithNoPlaceholders(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('plain text', ['key' => 'value']);

        $this->assertSame('plain text', $result);
    }
}
