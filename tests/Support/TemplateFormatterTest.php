<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\TemplateFormatter;

final class TemplateFormatterTest extends TestCase
{
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

    public function testNoWordPressDependency(): void
    {
        $formatter = new TemplateFormatter();
        $result = $formatter->format('{{ title }}', ['title' => 'Test']);

        $this->assertSame('Test', $result);
    }
}
