<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

use Period\WpKit\Support\CssName;

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
    private ?PostAssetsCompileService $postAssetsCompileService;

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
        $postAssetsCompileService = $config['post_assets_compile_service'] ?? null;

        $this->id = is_string($id) ? $id : '';
        $this->title = is_string($title) ? $title : '';
        $this->postTypes = $this->normalizePostTypes($postType);
        $this->context = is_string($context) && $context !== '' ? $context : 'normal';
        $this->priority = is_string($priority) && $priority !== '' ? $priority : 'default';
        $this->fields = is_array($fields) ? $fields : [];
        $this->nonceAction = is_string($nonceAction) && $nonceAction !== '' ? $nonceAction : $this->id;
        $this->nonceName = is_string($nonceName) && $nonceName !== '' ? $nonceName : $this->id . '_nonce';
        $this->postAssetsCompileService = $postAssetsCompileService instanceof PostAssetsCompileService
            ? $postAssetsCompileService
            : null;
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

    public function save(int $postId, array $postData = []): void
    {
        if (!function_exists('wp_verify_nonce')) {
            return;
        }

        $data = $postData !== [] ? $postData : $_POST;

        if (empty($data[$this->nonceName])) {
            return;
        }

        if (!wp_verify_nonce((string) $data[$this->nonceName], $this->nonceAction)) {
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
            $value = $this->sanitizeFieldValue($field, $data);

            $values = $this->normalizeFieldValue($value);

            if (($field['type'] ?? '') === 'repeater') {
                $existing = function_exists('get_post_meta')
                    ? get_post_meta($postId, $field['name'], true)
                    : [];

                $existingCount = is_array($existing) ? count($existing) : 0;

                $values = $this->trimNewTrailingEmptyRows($values, $existingCount);
            }

            if (!function_exists('update_post_meta')) {
                continue;
            }

            update_post_meta($postId, $field['name'], $values);

            if ($field['name'] === PostAssets::CSS_CODE && $this->postAssetsCompileService instanceof PostAssetsCompileService) {
                $source = $values[0] ?? '';
                $source = is_scalar($source) ? (string) $source : '';

                $this->postAssetsCompileService->compileCss($postId, $source);
            }
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
            if ($this->fieldRequiresInteractiveScript($field)) {
                return true;
            }
        }

        return false;
    }

    private function fieldRequiresInteractiveScript(array $field): bool
    {
        $type = is_array($field) && isset($field['type']) && is_string($field['type']) ? $field['type'] : '';
        if ($type === 'image' || $type === 'media' || $type === 'gallery' || $type === 'repeater') {
            return true;
        }

        if ($type === 'repeater' && isset($field['fields']) && is_array($field['fields'])) {
            foreach ($field['fields'] as $child) {
                if (is_array($child) && $this->fieldRequiresInteractiveScript($child)) {
                    return true;
                }
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

        $sortablePath = dirname(__DIR__, 3) . '/assets/vendor/sortable/Sortable.min.js';
        if (file_exists($sortablePath) && function_exists('wp_enqueue_script') && function_exists('plugins_url')) {
            wp_enqueue_script(
                'period-wp-metabox-sortable',
                plugins_url('assets/vendor/sortable/Sortable.min.js', dirname(__DIR__, 3) . '/bootstrap.php'),
                [],
                null,
                true
            );
        }

        if (!function_exists('apply_filters') || !function_exists('wp_enqueue_script')) {
            return;
        }

        $jsUrl = apply_filters('period_wp_metabox_js_url', null);

        if (!is_string($jsUrl) || $jsUrl === '') {
            return;
        }

        $jsPath = dirname(__DIR__, 3) . '/assets/js/period-wp-metabox.js';
        $version = file_exists($jsPath) ? (filemtime($jsPath) ?: null) : null;

        wp_enqueue_script('period-wp-metabox', $jsUrl, [], $version, true);
    }

    public function printMediaScript(): void
    {
        // JS は assets/js/period-wp-metabox.js に移動。
        // period_wp_metabox_js_url フィルターで URL を注入して enqueueMedia() で読み込んでください。
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
        $type = is_string($field['type'] ?? '') && $field['type'] !== '' ? $field['type'] : 'text';
        $options = $field['options'] ?? [];
        $fields = $field['fields'] ?? [];
        $description = $field['description'] ?? '';
        $placeholder = $field['placeholder'] ?? '';
        // @deprecated Use 'labels' => ['select' => ..., 'select_image' => ..., 'select_images' => ..., 'add' => ...] instead.
        $buttonLabel = $field['button_label'] ?? '';
        // @deprecated Use 'labels' => ['clear' => ...] instead.
        $clearLabel = $field['clear_label'] ?? '';
        // @deprecated Use 'labels' => ['remove' => ...] instead.
        $removeLabel = $field['remove_label'] ?? '';
        $mime = $field['mime'] ?? '';
        $sortable = isset($field['sortable']) ? (bool) $field['sortable'] : true;
        $min = isset($field['min']) && is_int($field['min']) && $field['min'] >= 0 ? $field['min'] : 0;
        $max = array_key_exists('max', $field) && (is_int($field['max']) || $field['max'] === null) ? $field['max'] : null;
        $group = $field['group'] ?? [];
        if (!is_array($group)) {
            $group = [];
        }

        $groupLabel = $group['label'] ?? '';
        if (!is_string($groupLabel)) {
            $groupLabel = '';
        }

        $groupCollapsible = isset($group['collapsible']) ? (bool) $group['collapsible'] : false;
        $groupDefaultOpen = isset($group['default_open']) ? (bool) $group['default_open'] : true;
        $groupIndexLabel = isset($group['index_label']) ? (bool) $group['index_label'] : true;

        $labels = $this->normalizeLabels($field, $type);

        return [
            'name' => isset($field['name']) && is_string($field['name']) ? $field['name'] : '',
            'label' => isset($field['label']) && is_string($field['label']) ? $field['label'] : '',
            'type' => $type,
            'default' => $type === 'gallery' ? $this->normalizeGalleryValue($field['default'] ?? []) : ($type === 'repeater' ? $this->normalizeRepeaterValue($field['default'] ?? []) : ($field['default'] ?? '')),
            'fields' => is_array($fields) ? $fields : [],
            'options' => is_array($options) ? $options : [],
            'description' => is_string($description) ? $description : '',
            'placeholder' => is_string($placeholder) ? $placeholder : '',
            'button_label' => $labels[$type === 'gallery' ? 'select_images' : ($type === 'repeater' ? 'add' : ($type === 'image' ? 'select_image' : 'select'))],
            'clear_label' => $labels['clear'],
            'remove_label' => $labels['remove'],
            'labels' => $labels,
            'preview' => isset($field['preview']) ? (bool) $field['preview'] : true,
            'mime' => is_string($mime) ? ($type === 'gallery' && $mime === '' ? 'image' : $mime) : ($type === 'gallery' ? 'image' : ''),
            'sortable' => $sortable,
            'min' => $min,
            'max' => $max,
            'group' => [
                'label' => $groupLabel,
                'collapsible' => $groupCollapsible,
                'default_open' => $groupDefaultOpen,
                'index_label' => $groupIndexLabel,
            ],
        ];
    }

    private function normalizeLabels(array $field, string $type): array
    {
        $explicit = isset($field['labels']) && is_array($field['labels']) ? $field['labels'] : [];
        // @deprecated 'button_label' is deprecated. Use 'labels' array instead.
        $buttonLabel = isset($field['button_label']) && is_string($field['button_label']) && $field['button_label'] !== '' ? $field['button_label'] : null;
        // @deprecated 'clear_label' is deprecated. Use 'labels' => ['clear' => ...] instead.
        $clearLabel = isset($field['clear_label']) && is_string($field['clear_label']) && $field['clear_label'] !== '' ? $field['clear_label'] : null;
        // @deprecated 'remove_label' is deprecated. Use 'labels' => ['remove' => ...] instead.
        $removeLabel = isset($field['remove_label']) && is_string($field['remove_label']) && $field['remove_label'] !== '' ? $field['remove_label'] : null;

        $resolve = function (string $key, ?string $legacy, string $fallback) use ($explicit): string {
            if (isset($explicit[$key]) && is_string($explicit[$key]) && $explicit[$key] !== '') {
                return $explicit[$key];
            }
            if ($legacy !== null) {
                return $legacy;
            }
            return $fallback;
        };

        return [
            'select'        => $resolve('select',        $type === 'media'    ? $buttonLabel : null, 'Select'),
            'select_image'  => $resolve('select_image',  $type === 'image'    ? $buttonLabel : null, 'Select image'),
            'select_images' => $resolve('select_images', $type === 'gallery'  ? $buttonLabel : null, 'Select images'),
            'add'           => $resolve('add',           $type === 'repeater' ? $buttonLabel : null, 'Add'),
            'clear'         => $resolve('clear',         $clearLabel,                                 'Clear'),
            'remove'        => $resolve('remove',        $removeLabel,                                'Remove'),
        ];
    }

    private function loadFieldValue(int $postId, array $field): mixed
    {
        if ($postId === 0 || !function_exists('get_post_meta')) {
            return $field['default'];
        }

        $value = get_post_meta($postId, $field['name'], true);

        return $value === null ? $field['default'] : $value;
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
            case 'repeater':
                return $this->renderRepeaterField($field, $value);
            case 'gallery':
                return $this->renderGalleryField($field, $value);
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
        $buttonLabel = $this->escapeAttr($field['type'] === 'image' ? $field['labels']['select_image'] : $field['labels']['select']);
        $clearLabel = $this->escapeAttr($field['labels']['clear']);
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

    private function renderGalleryField(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $buttonLabel = $this->escapeAttr($field['labels']['select_images']);
        $clearLabel = $this->escapeAttr($field['labels']['clear']);
        $previewTarget = $this->fieldId($field['name'] . '_preview');
        $mime = $this->escapeAttr((string) ($field['mime'] ?? ''));
        $galleryIds = $this->normalizeGalleryValue($value);
        $sortable = $field['sortable'] ? 'true' : 'false';
        $value = $this->escapeAttr((string) json_encode($galleryIds, JSON_UNESCAPED_UNICODE));
        $previewHtml = '';

        if ($field['preview'] && !empty($galleryIds)) {
            $items = '';
            foreach ($galleryIds as $itemId) {
                if ($field['preview'] && function_exists('wp_get_attachment_image')) {
                    $items .= sprintf('<div class="period-wp-metabox-gallery-item" data-attachment-id="%s">%s</div>', $this->escapeAttr((string) $itemId), wp_get_attachment_image((int) $itemId, 'thumbnail'));
                } else {
                    $items .= sprintf('<div class="period-wp-metabox-gallery-item" data-attachment-id="%s">%s</div>', $this->escapeAttr((string) $itemId), $this->escapeHtml((string) $itemId));
                }
            }
            $previewHtml = $items;
        }

        $preview = $field['preview'] ? sprintf(
            '<div id="%s" class="period-wp-metabox-gallery-preview" data-period-wp-gallery data-field-name="%s" data-sortable="%s">%s</div>',
            $this->escapeAttr($previewTarget),
            $this->escapeAttr($field['name']),
            $this->escapeAttr($sortable),
            $previewHtml
        ) : '';

        return sprintf(
            '<div class="period-wp-metabox-gallery" data-field-name="%s" data-period-wp-gallery data-sortable="%s" data-mime="%s">'
            . '<p><label for="%s">%s</label></p>'
            . '<input type="hidden" id="%s" name="%s" value="%s" />'
            . '<p><button type="button" class="period-wp-metabox-gallery-button" data-button-label="%s">%s</button> '
            . '<button type="button" class="period-wp-metabox-gallery-clear">%s</button></p>'
            . '%s'
            . '</div>',
            $this->escapeAttr($field['name']),
            $this->escapeAttr($sortable),
            $mime,
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

    private function renderRepeaterField(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $buttonLabel = $this->escapeAttr($field['labels']['add']);
        $sortable = $field['sortable'] ? 'true' : 'false';
        $min = (int) ($field['min'] ?? 0);
        $max = $field['max'] === null ? '' : (string) $field['max'];
        $items = $this->normalizeRepeaterValue($value);
        $value = $this->escapeAttr((string) json_encode($items, JSON_UNESCAPED_UNICODE));

        $itemsHtml = '';
        foreach ($items as $index => $item) {
            $itemsHtml .= $this->renderRepeaterItem($field, (string) $index, is_array($item) ? $item : []);
        }

        $templateHtml = $this->renderRepeaterItem($field, '__INDEX__', []);

        return sprintf(
            '<div class="period-wp-metabox-repeater" data-period-wp-repeater data-field-name="%s" data-sortable="%s" data-min="%s" data-max="%s">'
            . '<p><label>%s</label></p>'
            . '<input type="hidden" name="%s" value="%s" />'
            . '<div data-period-wp-repeater-items>%s</div>'
            . '<p><button type="button" class="period-wp-metabox-repeater-add" data-button-label="%s">%s</button></p>'
            . '<template class="period-wp-metabox-repeater-template">%s</template>'
            . '</div>',
            $this->escapeAttr($field['name']),
            $this->escapeAttr($sortable),
            $this->escapeAttr((string) $min),
            $this->escapeAttr($max),
            $label,
            $this->escapeAttr($field['name']),
            $value,
            $itemsHtml,
            $buttonLabel,
            $buttonLabel,
            $templateHtml
        );
    }

    private function renderRepeaterItem(array $field, string $index, array $item): string
    {
        $itemFields = '';
        $itemPrefix = $field['name'] . '[' . $index . ']';

        foreach ($field['fields'] as $childField) {
            if (!is_array($childField) || !isset($childField['name']) || !is_string($childField['name'])) {
                continue;
            }

            $child = $childField;
            $child['name'] = $itemPrefix . '[' . $childField['name'] . ']';
            $child['default'] = $item[$childField['name']] ?? ($childField['default'] ?? '');
            $child['label'] = $childField['label'] ?? $childField['name'];

            if (($childField['type'] ?? 'text') === 'repeater') {
                continue;
            }

            $itemFields .= $this->renderField($this->normalizeField($child), $child['default']);
        }

        $group = $field['group'] ?? [];
        $groupLabel = isset($group['label']) && is_string($group['label']) ? $group['label'] : '';
        $groupCollapsible = isset($group['collapsible']) ? (bool) $group['collapsible'] : false;
        $groupDefaultOpen = isset($group['default_open']) ? (bool) $group['default_open'] : true;
        $groupIndexLabel = isset($group['index_label']) ? (bool) $group['index_label'] : true;
        $removeLabel = $field['labels']['remove'] ?? 'Remove';

        if ($groupLabel !== '' || $groupCollapsible || $groupIndexLabel) {
            $headerText = $groupLabel !== '' ? $groupLabel : '';
            if ($groupIndexLabel) {
                $headerText = $groupLabel !== '' ? trim($groupLabel . ' ' . ((int) $index + 1)) : (string) ((int) $index + 1);
            }
            $header = sprintf('<div data-period-wp-group-header>%s</div>', $this->escapeHtml($headerText));
            $bodyStyle = $groupCollapsible && !$groupDefaultOpen ? ' style="display:none;"' : '';
            $body = sprintf('<div data-period-wp-group-body%s>%s</div>', $bodyStyle, $itemFields);
            $itemFields = sprintf('<div data-period-wp-group>%s%s</div>', $header, $body);

            return sprintf(
                '<div data-period-wp-repeater-item data-item-index="%s" data-group-label="%s" data-group-collapsible="%s" data-group-default-open="%s" data-group-index-label="%s">%s<p><button type="button" class="period-wp-metabox-repeater-remove">%s</button></p></div>',
                $this->escapeAttr($index),
                $this->escapeAttr($groupLabel),
                $groupCollapsible ? 'true' : 'false',
                $groupDefaultOpen ? 'true' : 'false',
                $groupIndexLabel ? 'true' : 'false',
                $itemFields,
                $this->escapeHtml($removeLabel)
            );
        }

        return sprintf(
            '<div data-period-wp-repeater-item data-item-index="%s">%s<p><button type="button" class="period-wp-metabox-repeater-remove">%s</button></p></div>',
            $this->escapeAttr($index),
            $itemFields,
            $this->escapeHtml($removeLabel)
        );
    }

    private function normalizeGalleryValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return [];
            }
            $value = $decoded;
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_numeric($item)) {
                return (int) $item;
            }

            if (is_string($item) && trim($item) !== '' && is_numeric(trim($item))) {
                return (int) trim($item);
            }

            return null;
        }, $value), function ($item) {
            return is_int($item) && $item > 0;
        }));
    }

    private function normalizeRepeaterValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return [];
            }
            $value = $decoded;
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, function ($item) {
            return is_array($item);
        }));
    }

    private function sanitizeRepeaterValue(array $field, mixed $raw): array
    {
        if ($raw === '' || $raw === null) {
            return [];
        }

        $items = [];
        if (is_string($raw)) {
            $items = $this->normalizeRepeaterValue($raw);
        } elseif (is_array($raw)) {
            $items = $this->normalizeRepeaterValue($raw);
        }

        $sanitized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $row = [];
            foreach ($field['fields'] as $childField) {
                if (!is_array($childField) || !isset($childField['name']) || !is_string($childField['name'])) {
                    continue;
                }

                $name = $childField['name'];
                $type = is_string($childField['type'] ?? '') ? $childField['type'] : 'text';
                $row[$name] = $this->sanitizeNestedFieldValue($type, $item[$name] ?? null);
            }

            $sanitized[] = $row;
        }

        return $sanitized;
    }

    private function sanitizeNestedFieldValue(string $type, mixed $raw): mixed
    {
        switch ($type) {
            case 'checkbox':
                return $raw === '1' ? '1' : '';
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
                return is_string($raw) || is_numeric($raw) ? (string) $raw : '';
        }
    }

    private function sanitizeFieldValue(array $field, array $postData = []): mixed
    {
        $data = $postData !== [] ? $postData : $_POST;
        $raw = $data[$field['name']] ?? null;

        switch ($field['type']) {
            case 'checkbox':
                return isset($data[$field['name']]) ? '1' : '';
            case 'select':
                return is_string($raw) ? $raw : '';
            case 'image':
            case 'media':
                if (is_numeric($raw)) {
                    return (string) ((int) $raw);
                }

                return is_string($raw) ? trim($raw) : '';
            case 'gallery':
                if ($raw === '' || $raw === null) {
                    return [];
                }

                if (is_array($raw)) {
                    return $this->normalizeGalleryValue($raw);
                }

                if (is_string($raw)) {
                    return $this->normalizeGalleryValue($raw);
                }

                return [];
            case 'repeater':
                return $this->sanitizeRepeaterValue($field, $raw);
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
    private function normalizeFieldValue(mixed $value): array
    {
        if ($value === null) {
            return [null];
        }

        return (array) $value;
    }

    private function trimNewTrailingEmptyRows(array $values, int $existingCount): array
    {
        if (count($values) <= $existingCount) {
            return $values;
        }

        $last = end($values);

        if ($last === '') {
            array_pop($values);
            return $values;
        }

        if (is_array($last) && $this->isEmptyRepeaterRow($last)) {
            array_pop($values);
        }

        return $values;
    }

    private function isEmptyRepeaterRow(array $row): bool
    {
        foreach ($row as $value) {
            if (is_array($value)) {
                if (!$this->isEmptyRepeaterRow($value)) {
                    return false;
                }

                continue;
            }

            if ($value !== '' && $value !== null) {
                return false;
            }
        }

        return true;
    }
}
