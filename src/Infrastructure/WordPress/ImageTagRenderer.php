<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

use Period\WpFramework\Support\ImageUtil;
use Period\WpFramework\View\Element;

class ImageTagRenderer
{
    public function render(int $attachmentId, array $args = []): string
    {
        if (!function_exists('wp_get_attachment_image_src')) {
            return '';
        }

        $args = $this->normalizeArgs($args);
        $size = $args['size'];

        $srcData = wp_get_attachment_image_src($attachmentId, $size);
        if ($srcData === false || !is_array($srcData) || count($srcData) < 3) {
            return '';
        }

        [$src, $width, $height] = $srcData;
        $width = (int) $width;
        $height = (int) $height;

        $alt = $this->resolveAlt($attachmentId, $args['alt']);
        $orientation = ImageUtil::orientation($width, $height);
        $wrapperClasses = $this->buildWrapperClasses($args['wrapper_class'], $orientation, $args['class']);

        $imgHtml = Element::void('img', [
            'src' => $src,
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
            'loading' => $args['lazy'] ? 'lazy' : null,
        ]);

        if ($args['wrapper']) {
            return sprintf('<div class="%s">%s</div>', htmlspecialchars($wrapperClasses, ENT_QUOTES, 'UTF-8'), $imgHtml);
        }

        return $imgHtml;
    }

    private function normalizeArgs(array $args): array
    {
        $size = $args['size'] ?? 'full';
        $class = $args['class'] ?? '';
        $wrapper = isset($args['wrapper']) ? (bool) $args['wrapper'] : true;
        $wrapperClass = $args['wrapper_class'] ?? 'image';
        $lazy = isset($args['lazy']) ? (bool) $args['lazy'] : true;
        $alt = array_key_exists('alt', $args) ? $args['alt'] : null;

        return [
            'size' => is_string($size) && $size !== '' ? $size : 'full',
            'class' => is_string($class) ? $class : '',
            'wrapper' => $wrapper,
            'wrapper_class' => is_string($wrapperClass) && $wrapperClass !== '' ? $wrapperClass : 'image',
            'lazy' => $lazy,
            'alt' => is_string($alt) ? $alt : null,
        ];
    }

    private function resolveAlt(int $attachmentId, ?string $explicitAlt): string
    {
        if (is_string($explicitAlt) && $explicitAlt !== '') {
            return $explicitAlt;
        }

        if (function_exists('get_post_meta')) {
            $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
            if (is_string($alt) && $alt !== '') {
                return $alt;
            }
        }

        return '';
    }

    private function buildWrapperClasses(string $wrapperClass, string $orientation, string $extraClass): string
    {
        $classes = [$wrapperClass, $wrapperClass . '--' . $orientation];

        if ($extraClass !== '') {
            $classes[] = $extraClass;
        }

        return implode(' ', $classes);
    }
}
