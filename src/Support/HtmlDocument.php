<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

use Masterminds\HTML5;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Exception\InvalidArgumentException;

final class HtmlDocument
{
    private Crawler $crawler;

    private function __construct(string $html)
    {
        $this->crawler = $this->createCrawler($html);
    }

    public static function fromString(string $html): self
    {
        return new self($html);
    }

    public static function fromUrl(string $url): self
    {
        $html = '';

        if (ini_get('allow_url_fopen')) {
            $html = (string) @file_get_contents($url);
        }

        return new self($html);
    }

    public function filter(string $selector): array
    {
        try {
            $elements = $this->crawler->filter($selector);
        } catch (InvalidArgumentException $exception) {
            return [];
        }

        if (!$elements->count()) {
            return [];
        }

        $result = [];

        foreach ($elements as $element) {
            $result[] = trim((string) $element->textContent);
        }

        return $result;
    }

    public function firstText(string $selector): string
    {
        try {
            $element = $this->crawler->filter($selector)->first();
        } catch (InvalidArgumentException $exception) {
            return '';
        }

        if (!$element->count()) {
            return '';
        }

        return trim((string) $element->text());
    }

    public function firstAttr(string $selector, string $attr): string
    {
        try {
            $element = $this->crawler->filter($selector)->first();
        } catch (InvalidArgumentException $exception) {
            return '';
        }

        if (!$element->count()) {
            return '';
        }

        return (string) $element->attr($attr) ?: '';
    }

    public function html(string $selector): string
    {
        try {
            $element = $this->crawler->filter($selector)->first();
        } catch (InvalidArgumentException $exception) {
            return '';
        }

        if (!$element->count()) {
            return '';
        }

        $node = $element->getNode(0);

        if ($node === null || !$node instanceof \DOMNode) {
            return '';
        }

        $innerHtml = '';

        foreach ($element as $domElement) {
            if ($domElement->ownerDocument === null) {
                continue;
            }

            foreach ($domElement->childNodes as $childNode) {
                $innerHtml .= $domElement->ownerDocument->saveHTML($childNode);
            }
        }

        return $innerHtml;
    }

    private function createCrawler(string $html): Crawler
    {
        $html5 = new HTML5();

        try {
            $document = $html5->loadHTML($html);
            $content = $document->saveHTML();
        } catch (\Throwable $exception) {
            $content = $html;
        }

        return new Crawler($content);
    }
}
