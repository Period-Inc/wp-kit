<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpKit\WordPress\ScriptStyleRegistrar;

final class ScriptStyleRegistrarTest extends TestCase
{
    public function testScriptDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->script('test-script', '/path/to/script.js'));
    }

    public function testStyleDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->style('test-style', '/path/to/style.css'));
    }

    public function testEnqueueDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $registrar->script('test-script', '/path/to/script.js');
        $registrar->style('test-style', '/path/to/style.css');

        $this->assertNull($registrar->enqueue('test-script'));
        $this->assertNull($registrar->enqueue('test-style'));
    }

    public function testScriptWithPathDoesNotFailWhenFileMissing(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->script('test-script', 'https://example.com/script.js', [
            'path' => __DIR__ . '/non-existent.js',
        ]));
    }

    public function testStyleWithPathDoesNotFailWhenFileMissing(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->style('test-style', 'https://example.com/style.css', [
            'path' => __DIR__ . '/non-existent.css',
        ]));
    }

    public function testScriptEnqueueOptionDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->script('test-script', '/path/to/script.js', [
            'enqueue' => true,
        ]));
    }

    public function testStyleEnqueueOptionDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertSame($registrar, $registrar->style('test-style', '/path/to/style.css', [
            'enqueue' => true,
        ]));
    }

    public function testInlineScriptDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertNull($registrar->inlineScript('test-script', 'console.log("ok");'));
    }

    public function testInlineStyleDoesNotFailWithoutWordPress(): void
    {
        $registrar = new ScriptStyleRegistrar(__DIR__);

        $this->assertNull($registrar->inlineStyle('test-style', 'body { color: red; }'));
    }
}
