<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

use Period\WpFramework\Support\CssName;

final class MetaBox
{
    private string $id;
    private string $title;
    private array $postTypes;
    private string $context;
    private string $priority;
    private array $fields;
    private string $nonceAction;
    private string $nonceName;

    public function __construct(array $config)
    {
        $id = $config['id'] ?? '';
        $title = $config['title'] ?? '';
        $postType = $config['post_type'] ?? [];
        $context = $config['context'] ?? '';
        $priority = $config['priority'] ?? '';
        $fields = $config['fields'] ?? [];
        $nonceAction = $config['nonce_action'] ?? null;
        $nonceName = $config['nonce_name'] ?? null;

        $this->id = is_string($id) ? $id : '';
        $this->title = is_string($title) ? $title : '';
        $this->postTypes = $this->normalizePostTypes($postType);
        $this->context = is_string($context) && $context !== '' ? $context : 'normal';
        $this->priority = is_string($priority) && $priority !== '' ? $priority : 'default';
        $this->fields = is_array($fields) ? $fields : [];
        $this->nonceAction = is_string($nonceAction) && $nonceAction !== '' ? $nonceAction : $this->id;
        $this->nonceName = is_string($nonceName) && $nonceName !== '' ? $nonceName : $this->id . '_nonce';
    }

    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        add_action('add_meta_boxes', [$this, 'setupMetaBoxes']);
        add_action('save_post', [$this, 'save']);

        if ($this->hasMediaFields()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueueMedia']);
        }
    }

    public function render($post): void
    {
        $postId = is_object($post) && isset($post->ID) ? (int) $post->ID : ((is_int($post) || ctype_digit((string) $post)) ? (int) $post : 0);

        echo $this->renderNonceField();

        foreach ($this->fields as $field) {
            if (!isset($field['name']) || !is_string($field['name']) || $field['name'] === '') {
                continue;
            }

            $field = $this->normalizeField($field);
            $value = $this->loadFieldValue($postId, $field);

            echo $this->renderField($field, $value);
        }
    }

    public function save(int $postId): void
    {
        if (!function_exists('wp_verify_nonce')) {
            return;
        }

        if (empty($_POST[$this->nonceName])) {
            return;
        }

        if (!wp_verify_nonce((string) $_POST[$this->nonceName], $this->nonceAction)) {
            return;
        }

        if (function_exists('wp_is_post_autosave') && wp_is_post_autosave($postId)) {
            return;
        }

        if (function_exists('wp_is_post_revision') && wp_is_post_revision($postId)) {
            return;
        }

        if (function_exists('current_user_can') && !current_user_can('edit_post', $postId)) {
            return;
        }

        foreach ($this->fields as $field) {
            if (!isset($field['name']) || !is_string($field['name']) || $field['name'] === '') {
                continue;
            }

            $field = $this->normalizeField($field);
            $value = $this->sanitizeFieldValue($field);

            if (!function_exists('update_post_meta')) {
                continue;
            }

            update_post_meta($postId, $field['name'], $value);
        }
    }

    public function fields(): array
    {
        return $this->fields;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    private function setupMetaBoxes(): void
    {
        if (!function_exists('add_meta_box')) {
            return;
        }

        if ($this->id === '' || empty($this->postTypes)) {
            return;
        }

        foreach ($this->postTypes as $postType) {
            add_meta_box(
                $this->id,
                $this->title,
                [$this, 'render'],
                $postType,
                $this->context,
                $this->priority
            );
        }
    }

    private function hasMediaFields(): bool
    {
        foreach ($this->fields as $field) {
            $type = is_array($field) && isset($field['type']) && is_string($field['type']) ? $field['type'] : '';
            if ($type === 'image' || $type === 'media') {
                return true;
            }
        }

        return false;
    }

    public function enqueueMedia(): void
    {
        if (!function_exists('wp_enqueue_media')) {
            return;
        }

        wp_enqueue_media();

        if (function_exists('add_action')) {
            add_action('admin_footer', [$this, 'printMediaScript']);
        }
    }

    public function printMediaScript(): void
    {
        if (!function_exists('wp_enqueue_script')) {
            return;
        }

        $script = <<<'JS'
(function($){
    $(document).on('click', '.period-wp-metabox-media-button', function(e){
        e.preventDefault();
        var $button = $(this);
        var $container = $button.closest('.period-wp-metabox-media');
        var fieldName = $container.data('field-name');
        var mimeType = $container.data('mime');
        var previewTarget = $container.data('preview-target');
        var $input = $container.find('input[type="hidden"]');
        var $preview = $container.find('.period-wp-metabox-media-preview');

        var frame = wp.media({
            title: $button.data('button-label') || 'Select Media',
            button: { text: $button.data('button-label') || 'Select' },
            multiple: false,
            library: { type: mimeType || '' }
        });

        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.id).trigger('change');
            if ($preview.length) {
                var previewText = attachment.url ? attachment.url : attachment.id;
                $preview.text(previewText);
            }
        });

        frame.open();
    });

    $(document).on('click', '.period-wp-metabox-media-clear', function(e){
        e.preventDefault();
        var $container = $(this).closest('.period-wp-metabox-media');
        var $input = $container.find('input[type="hidden"]');
        var $preview = $container.find('.period-wp-metabox-media-preview');
        $input.val('').trigger('change');
        if ($preview.length) {
            $preview.text('');
        }
    });
})(jQuery);
JS;

        if (function_exists('wp_add_inline_script')) {
            wp_add_inline_script('jquery', $script);
        } else {
            echo '<script>' . $script . '</script>';
        }
    }

    private function normalizePostTypes(mixed $postTypes): array
    {
        if (is_string($postTypes)) {
            return [$postTypes];
        }

        if (is_array($postTypes)) {
            return array_values(array_filter($postTypes, fn ($item) => is_string($item) && $item !== ''));
        }

        return [];
    }

    private function normalizeField(array $field): array
    {
        $options = $field['options'] ?? [];
        $description = $field['description'] ?? '';
        $placeholder = $field['placeholder'] ?? '';
        $buttonLabel = $field['button_label'] ?? '';
        $clearLabel = $field['clear_label'] ?? '';
        $mime = $field['mime'] ?? '';

        return [
            'name' => is_string($field['name'] ?? '') ? $field['name'] : '',
            'label' => is_string($field['label'] ?? '') ? $field['label'] : '',
            'type' => is_string($field['type'] ?? '') && $field['type'] !== '' ? $field['type'] : 'text',
            'default' => $field['default'] ?? '',
            'options' => is_array($options) ? $options : [],
            'description' => is_string($description) ? $description : '',
            'placeholder' => is_string($placeholder) ? $placeholder : '',
            'button_label' => is_string($buttonLabel) && $buttonLabel !== '' ? $buttonLabel : '選択',
            'clear_label' => is_string($clearLabel) && $clearLabel !== '' ? $clearLabel : 'クリア',
            'preview' => isset($field['preview']) ? (bool) $field['preview'] : true,
            'mime' => is_string($mime) ? $mime : '',
        ];
    }

    private function loadFieldValue(int $postId, array $field): mixed
    {
        if ($postId === 0 || !function_exists('get_post_meta')) {
            return $field['default'];
        }

        $value = get_post_meta($postId, $field['name'], true);

        return $value === '' || $value === null ? $field['default'] : $value;
    }

    private function renderNonceField(): string
    {
        if (function_exists('wp_nonce_field')) {
            return wp_nonce_field($this->nonceAction, $this->nonceName, true, false);
        }

        $action = $this->escapeAttr($this->nonceAction);
        $name = $this->escapeAttr($this->nonceName);

        return sprintf('<input type="hidden" name="%s" value="%s" />', $name, $action);
    }

    private function renderField(array $field, mixed $value): string
    {
        switch ($field['type']) {
            case 'textarea':
                return $this->renderTextarea($field, $value);
            case 'checkbox':
                return $this->renderCheckbox($field, $value);
            case 'select':
                return $this->renderSelect($field, $value);
            case 'hidden':
                return $this->renderHidden($field, $value);
            case 'image':
            case 'media':
                return $this->renderMediaField($field, $value);
            case 'text':
            default:
                return $this->renderText($field, $value);
        }
    }

    private function renderText(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $value = $this->escapeAttr((string) $value);
        $placeholder = $this->escapeAttr($field['placeholder']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));

        return sprintf(
            '<p><label for="%s">%s</label><br /><input type="text" id="%s" name="%s" value="%s" placeholder="%s" /></p>',
            $id,
            $label,
            $id,
            $name,
            $value,
            $placeholder
        );
    }

    private function renderTextarea(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $placeholder = $this->escapeAttr($field['placeholder']);
        $content = $this->escapeHtml((string) $value ?: (string) $field['default']);

        return sprintf(
            '<p><label for="%s">%s</label><br /><textarea id="%s" name="%s" placeholder="%s">%s</textarea></p>',
            $id,
            $label,
            $id,
            $name,
            $placeholder,
            $content
        );
    }

    private function renderCheckbox(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $checked = $this->checked((string) $value === '1');
        $valueAttr = $this->escapeAttr('1');

        return sprintf(
            '<p><label for="%s"><input type="checkbox" id="%s" name="%s" value="%s" %s /> %s</label></p>',
            $id,
            $id,
            $name,
            $valueAttr,
            $checked,
            $label
        );
    }

    private function renderSelect(array $field, mixed $value): string
    {
        if (!is_array($field['options'])) {
            return '';
        }

        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $options = '';
        $selectedValue = (string) $value;

        foreach ($field['options'] as $optionValue => $optionLabel) {
            if (!is_string($optionValue) && !is_int($optionValue)) {
                continue;
            }

            $optionValue = (string) $optionValue;
            $optionLabel = is_string($optionLabel) ? $optionLabel : (string) $optionLabel;
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                $this->escapeAttr($optionValue),
                $this->selected($optionValue === $selectedValue),
                $this->escapeHtml($optionLabel)
            );
        }

        return sprintf(
            '<p><label for="%s">%s</label><br /><select id="%s" name="%s">%s</select></p>',
            $id,
            $label,
            $id,
            $name,
            $options
        );
    }

    private function renderHidden(array $field, mixed $value): string
    {
        $name = $this->escapeAttr($field['name']);
        $value = $this->escapeAttr((string) $value);

        return sprintf('<input type="hidden" name="%s" value="%s" />', $name, $value);
    }

    private function renderMediaField(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $buttonLabel = $this->escapeAttr($field['button_label']);
        $clearLabel = $this->escapeAttr($field['clear_label']);
        $previewTarget = $this->fieldId($field['name'] . '_preview');
        $mime = $this->escapeAttr((string) ($field['mime'] ?? ''));
        $value = $this->escapeAttr((string) $value);
        $previewHtml = '';

        if ($field['preview'] && $value !== '') {
            if ($field['type'] === 'image' && function_exists('wp_get_attachment_image')) {
                $previewHtml = wp_get_attachment_image((int) $value, 'thumbnail');
            } elseif ($field['type'] === 'media' && function_exists('wp_get_attachment_url')) {
                $url = wp_get_attachment_url((int) $value);
                $previewHtml = $this->escapeHtml((string) $url);
            }
        }

        $preview = $field['preview'] ? sprintf('<div id="%s" class="period-wp-metabox-media-preview">%s</div>', $this->escapeAttr($previewTarget), $previewHtml) : '';

        return sprintf(
            '<div class="period-wp-metabox-media" data-field-name="%s" data-mime="%s" data-preview-target="%s">'
            . '<p><label for="%s">%s</label></p>'
            . '<input type="hidden" id="%s" name="%s" value="%s" />'
            . '<p><button type="button" class="period-wp-metabox-media-button" data-button-label="%s">%s</button> '
            . '<button type="button" class="period-wp-metabox-media-clear">%s</button></p>'
            . '%s'
            . '</div>',
            $this->escapeAttr($field['name']),
            $mime,
            $this->escapeAttr($previewTarget),
            $id,
            $label,
            $id,
            $name,
            $value,
            $buttonLabel,
            $buttonLabel,
            $clearLabel,
            $preview
        );
    }

    private function sanitizeFieldValue(array $field): string
    {
        $raw = $_POST[$field['name']] ?? null;

        switch ($field['type']) {
            case 'checkbox':
                return isset($_POST[$field['name']]) ? '1' : '';
            case 'select':
                return is_string($raw) ? $raw : '';
            case 'image':
            case 'media':
                if (is_numeric($raw)) {
                    return (string) ((int) $raw);
                }

                return is_string($raw) ? trim($raw) : '';
            case 'textarea':
            case 'hidden':
            case 'text':
            default:
                return is_string($raw) ? $raw : '';
        }
    }

    private function fieldId(string $name): string
    {
        return $this->id . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    }

    private function escapeHtml(string $value): string
    {
        if (function_exists('esc_html')) {
            return esc_html($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeAttr(string $value): string
    {
        if (function_exists('esc_attr')) {
            return esc_attr($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function checked(bool $checked): string
    {
        if (function_exists('checked')) {
            return checked($checked, true, false);
        }

        return $checked ? 'checked' : '';
    }

    private function selected(bool $selected): string
    {
        if (function_exists('selected')) {
            return selected($selected, true, false);
        }

        return $selected ? 'selected' : '';
    }
}
