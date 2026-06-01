<?php
declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class PostAssets
{
    public const CSS_CODE = 'csscode';
    public const CSS_FILE = 'cssfile';
    public const JS_CODE = 'jscode';
    public const JS_FILE = 'jsfile';

    public function __construct(
        private readonly PostMetaManager $meta,
    ) {}

    /**
     * @return array{csscode:mixed, cssfile:mixed, jscode:mixed, jsfile:mixed}
     */
    public function get(int $postId): array
    {
        return [
            self::CSS_CODE => $this->meta->get($postId, self::CSS_CODE),
            self::CSS_FILE => $this->meta->get($postId, self::CSS_FILE),
            self::JS_CODE => $this->meta->get($postId, self::JS_CODE),
            self::JS_FILE => $this->meta->get($postId, self::JS_FILE),
        ];
    }

    public function cssCode(int $postId): string
    {
        return $this->stringValue($this->meta->get($postId, self::CSS_CODE, ''));
    }

    public function cssFile(int $postId): string
    {
        return $this->stringValue($this->meta->get($postId, self::CSS_FILE, ''));
    }

    public function jsCode(int $postId): string
    {
        return $this->stringValue($this->meta->get($postId, self::JS_CODE, ''));
    }

    public function jsFile(int $postId): string
    {
        return $this->stringValue($this->meta->get($postId, self::JS_FILE, ''));
    }

    private function stringValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
