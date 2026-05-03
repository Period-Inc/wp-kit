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

        if (function_exists('add_action')) {
            add_action('admin_footer', [$this, 'printMediaScript']);
        }
    }

    public function printMediaScript(): void
    {
        if (!function_exists('wp_enqueue_media')) {
            return;
        }

        $script = <<<'JS'
(function(){
    function closest(element, selector) {
        return element && element.closest(selector);
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function parseGalleryIds(value) {
        if (value === '') {
            return [];
        }

        try {
            var parsed = JSON.parse(value);
        } catch (e) {
            return [];
        }

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map(function(item) {
                return typeof item === 'string' || typeof item === 'number' ? parseInt(item, 10) : NaN;
            })
            .filter(function(item) {
                return Number.isInteger(item) && item > 0;
            });
    }

    function updateInputValue(input, value) {
        input.value = JSON.stringify(value);
        input.dispatchEvent(new Event('change'));
    }

    function createGalleryItemAttachment(attachment) {
        var item = document.createElement('div');
        item.className = 'period-wp-metabox-gallery-item';
        item.dataset.attachmentId = String(attachment.id);

        if (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
            var image = document.createElement('img');
            image.src = attachment.sizes.thumbnail.url;
            image.alt = attachment.alt || '';
            item.appendChild(image);
        } else if (attachment.url) {
            var image = document.createElement('img');
            image.src = attachment.url;
            image.alt = attachment.alt || '';
            item.appendChild(image);
        } else {
            item.textContent = String(attachment.id);
        }

        return item;
    }

    function createPreviewItem(id) {
        var item = document.createElement('div');
        item.className = 'period-wp-metabox-gallery-item';
        item.dataset.attachmentId = String(id);
        item.textContent = String(id);
        return item;
    }

    function renderGalleryPreview(container, ids) {
        container.innerHTML = '';

        ids.forEach(function(id) {
            container.appendChild(createPreviewItem(id));
        });
    }

    function updateGalleryFromPreview(container, input) {
        var ids = [];
        container.querySelectorAll('[data-attachment-id]').forEach(function(item) {
            var attachmentId = parseInt(item.dataset.attachmentId, 10);
            if (Number.isInteger(attachmentId)) {
                ids.push(attachmentId);
            }
        });
        updateInputValue(input, ids);
    }

    function ensureSortable(container, onEnd) {
        if (typeof Sortable !== 'function' || container.dataset.sortableInitialized === '1') {
            return;
        }

        if (container.dataset.sortable !== 'true') {
            return;
        }

        Sortable.create(container, {
            animation: 150,
            onEnd: onEnd
        });

        container.dataset.sortableInitialized = '1';
    }

    function parseRepeaterValue(value) {
        if (value === '') {
            return [];
        }

        try {
            var parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function buildItemObject(item, rootName) {
        var result = {};
        var selector = 'input[name], select[name], textarea[name]';
        item.querySelectorAll(selector).forEach(function(element) {
            var name = element.getAttribute('name');
            if (!name) {
                return;
            }

            var regex = new RegExp('^' + escapeRegExp(rootName) + '\\[\\d+\\]\\[(.+?)\\]$');
            var match = name.match(regex);
            if (!match) {
                return;
            }

            var key = match[1];
            if (element.type === 'checkbox') {
                result[key] = element.checked ? '1' : '';
                return;
            }

            if (element.tagName.toLowerCase() === 'select' || element.tagName.toLowerCase() === 'textarea' || element.type === 'hidden' || element.type === 'text') {
                result[key] = element.value;
            }
        });

        return result;
    }

    function syncRepeater(repeater) {
        if (!repeater) {
            return;
        }

        var rootName = repeater.dataset.fieldName;
        var input = repeater.querySelector('input[type="hidden"]');
        var items = [];

        repeater.querySelectorAll('[data-period-wp-repeater-item]').forEach(function(item) {
            items.push(buildItemObject(item, rootName));
        });

        if (input) {
            updateInputValue(input, items);
        }
    }

    function updateRepeaterIndices(repeater) {
        var rootName = repeater.dataset.fieldName;
        var items = repeater.querySelectorAll('[data-period-wp-repeater-item]');

        items.forEach(function(item, index) {
            item.dataset.itemIndex = String(index);
            item.querySelectorAll('[name]').forEach(function(element) {
                var name = element.getAttribute('name');
                if (!name) {
                    return;
                }

                var pattern = new RegExp('^' + escapeRegExp(rootName) + '\\[\\d+\\]');
                if (pattern.test(name)) {
                    element.setAttribute('name', name.replace(pattern, rootName + '[' + index + ']'));
                }
            });

            var groupLabel = item.dataset.groupLabel || '';
            var groupIndexLabel = item.dataset.groupIndexLabel !== 'false';
            var header = item.querySelector('[data-period-wp-group-header]');
            if (header) {
                if (groupIndexLabel) {
                    header.textContent = groupLabel !== '' ? groupLabel + ' ' + (index + 1) : String(index + 1);
                } else {
                    header.textContent = groupLabel;
                }
            }
        });
    }

    function createRepeaterItem(repeater) {
        var template = repeater.querySelector('template.period-wp-metabox-repeater-template');
        if (!template) {
            return null;
        }

        var content = template.content.cloneNode(true);
        var rootName = repeater.dataset.fieldName;
        var itemCount = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;

        content.querySelectorAll('[name], [id], [for]').forEach(function(element) {
            ['name', 'id', 'for'].forEach(function(attribute) {
                if (!element.hasAttribute(attribute)) {
                    return;
                }

                var value = element.getAttribute(attribute);
                if (value === null) {
                    return;
                }

                value = value.replace(/__INDEX__/g, String(itemCount));
                var pattern = new RegExp('^' + escapeRegExp(rootName) + '\\[__INDEX__\\]');
                if (pattern.test(value)) {
                    element.setAttribute(attribute, value.replace(pattern, rootName + '[' + itemCount + ']'));
                } else {
                    element.setAttribute(attribute, value);
                }
            });
        });

        return content;
    }

    function initializeRepeater(repeater) {
        var itemsContainer = repeater.querySelector('[data-period-wp-repeater-items]');
        if (!itemsContainer) {
            return;
        }

        if (typeof Sortable === 'function' && repeater.dataset.sortable === 'true') {
            ensureSortable(itemsContainer, function() {
                updateRepeaterIndices(repeater);
                syncRepeater(repeater);
            });
        }
    }

    document.addEventListener('click', function(event) {
        var button = closest(event.target, '.period-wp-metabox-media-button');
        var galleryButton = closest(event.target, '.period-wp-metabox-gallery-button');
        var repeaterAdd = closest(event.target, '.period-wp-metabox-repeater-add');
        var repeaterRemove = closest(event.target, '.period-wp-metabox-repeater-remove');
        var groupHeader = closest(event.target, '[data-period-wp-group-header]');
        var clearButton = closest(event.target, '.period-wp-metabox-media-clear');
        var galleryClearButton = closest(event.target, '.period-wp-metabox-gallery-clear');

        if (button) {
            event.preventDefault();
            var container = closest(button, '.period-wp-metabox-media');
            if (!container || !window.wp || !wp.media) {
                return;
            }

            var mimeType = container.dataset.mime || '';
            var input = container.querySelector('input[type="hidden"]');
            var preview = container.querySelector('.period-wp-metabox-media-preview');

            var frame = wp.media({
                title: button.dataset.buttonLabel || 'Select Media',
                button: { text: button.dataset.buttonLabel || 'Select' },
                multiple: false,
                library: { type: mimeType }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                if (input) {
                    input.value = String(attachment.id);
                    input.dispatchEvent(new Event('change'));
                }
                if (preview) {
                    preview.textContent = attachment.url ? attachment.url : String(attachment.id);
                }
            });

            frame.open();
            return;
        }

        if (galleryButton) {
            event.preventDefault();
            var container = closest(galleryButton, '.period-wp-metabox-gallery');
            if (!container || !window.wp || !wp.media) {
                return;
            }

            var mimeType = container.dataset.mime || 'image';
            var input = container.querySelector('input[type="hidden"]');
            var preview = container.querySelector('.period-wp-metabox-gallery-preview');
            var ids = input ? parseGalleryIds(input.value) : [];

            var frame = wp.media({
                title: galleryButton.dataset.buttonLabel || 'Select Images',
                button: { text: galleryButton.dataset.buttonLabel || 'Select' },
                multiple: true,
                library: { type: mimeType }
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection').toArray();
                selection.forEach(function(attachment) {
                    attachment = attachment.toJSON();
                    var attachmentId = parseInt(attachment.id, 10);
                    if (Number.isInteger(attachmentId) && ids.indexOf(attachmentId) === -1) {
                        ids.push(attachmentId);
                        if (preview) {
                            preview.appendChild(createGalleryItemAttachment(attachment));
                        }
                    }
                });
                if (input) {
                    updateInputValue(input, ids);
                }
                if (preview && typeof Sortable === 'function') {
                    ensureSortable(preview, function() {
                        updateGalleryFromPreview(preview, input);
                    });
                }
            });

            frame.open();
            return;
        }

        if (repeaterAdd) {
            event.preventDefault();
            var repeater = closest(repeaterAdd, '[data-period-wp-repeater]');
            if (!repeater) {
                return;
            }

            var max = repeater.dataset.max !== undefined && repeater.dataset.max !== '' ? parseInt(repeater.dataset.max, 10) : null;
            var items = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;
            if (Number.isInteger(max) && max !== null && items >= max) {
                return;
            }

            var itemsContainer = repeater.querySelector('[data-period-wp-repeater-items]');
            var newItem = createRepeaterItem(repeater);
            if (itemsContainer && newItem) {
                itemsContainer.appendChild(newItem);
                updateRepeaterIndices(repeater);
                syncRepeater(repeater);
            }
            return;
        }

        if (repeaterRemove) {
            event.preventDefault();
            var item = closest(repeaterRemove, '[data-period-wp-repeater-item]');
            var repeater = closest(repeaterRemove, '[data-period-wp-repeater]');
            if (!item || !repeater) {
                return;
            }

            var min = repeater.dataset.min !== undefined && repeater.dataset.min !== '' ? parseInt(repeater.dataset.min, 10) : 0;
            var items = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;
            if (Number.isInteger(min) && items <= min) {
                return;
            }

            item.remove();
            updateRepeaterIndices(repeater);
            syncRepeater(repeater);
            return;
        }

        if (groupHeader) {
            var item = closest(groupHeader, '[data-period-wp-repeater-item]');
            if (!item || item.dataset.groupCollapsible !== 'true') {
                return;
            }

            event.preventDefault();
            var body = item.querySelector('[data-period-wp-group-body]');
            if (!body) {
                return;
            }

            body.style.display = body.style.display === 'none' ? '' : 'none';
            return;
        }

        if (clearButton) {
            event.preventDefault();
            var container = closest(clearButton, '.period-wp-metabox-media');
            var input = container ? container.querySelector('input[type="hidden"]') : null;
            var preview = container ? container.querySelector('.period-wp-metabox-media-preview') : null;
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('change'));
            }
            if (preview) {
                preview.textContent = '';
            }
            return;
        }

        if (galleryClearButton) {
            event.preventDefault();
            var container = closest(galleryClearButton, '.period-wp-metabox-gallery');
            var input = container ? container.querySelector('input[type="hidden"]') : null;
            var preview = container ? container.querySelector('.period-wp-metabox-gallery-preview') : null;
            if (input) {
                updateInputValue(input, []);
            }
            if (preview) {
                renderGalleryPreview(preview, []);
            }
            return;
        }
    });

    document.addEventListener('change', function(event) {
        var repeater = closest(event.target, '[data-period-wp-repeater]');
        if (repeater) {
            syncRepeater(repeater);
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-period-wp-repeater]'), function(repeater) {
        initializeRepeater(repeater);
    });
})();
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
        $type = is_string($field['type'] ?? '') && $field['type'] !== '' ? $field['type'] : 'text';
        $options = $field['options'] ?? [];
        $fields = $field['fields'] ?? [];
        $description = $field['description'] ?? '';
        $placeholder = $field['placeholder'] ?? '';
        $buttonLabel = $field['button_label'] ?? '';
        $clearLabel = $field['clear_label'] ?? '';
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

        return [
            'name' => isset($field['name']) && is_string($field['name']) ? $field['name'] : '',
            'label' => isset($field['label']) && is_string($field['label']) ? $field['label'] : '',
            'type' => $type,
            'default' => $type === 'gallery' ? $this->normalizeGalleryValue($field['default'] ?? []) : ($type === 'repeater' ? $this->normalizeRepeaterValue($field['default'] ?? []) : ($field['default'] ?? '')),
            'fields' => is_array($fields) ? $fields : [],
            'options' => is_array($options) ? $options : [],
            'description' => is_string($description) ? $description : '',
            'placeholder' => is_string($placeholder) ? $placeholder : '',
            'button_label' => is_string($buttonLabel) && $buttonLabel !== '' ? $buttonLabel : ($type === 'gallery' ? '画像を選択' : ($type === 'repeater' ? '追加' : '選択')),
            'clear_label' => is_string($clearLabel) && $clearLabel !== '' ? $clearLabel : 'クリア',
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

    private function renderGalleryField(array $field, mixed $value): string
    {
        $label = $this->escapeHtml($field['label'] ?: $field['name']);
        $name = $this->escapeAttr($field['name']);
        $id = $this->escapeAttr($this->fieldId($field['name']));
        $buttonLabel = $this->escapeAttr($field['button_label']);
        $clearLabel = $this->escapeAttr($field['clear_label']);
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
        $buttonLabel = $this->escapeAttr($field['button_label']);
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
                '<div data-period-wp-repeater-item data-item-index="%s" data-group-label="%s" data-group-collapsible="%s" data-group-default-open="%s" data-group-index-label="%s">%s<p><button type="button" class="period-wp-metabox-repeater-remove">削除</button></p></div>',
                $this->escapeAttr($index),
                $this->escapeAttr($groupLabel),
                $groupCollapsible ? 'true' : 'false',
                $groupDefaultOpen ? 'true' : 'false',
                $groupIndexLabel ? 'true' : 'false',
                $itemFields
            );
        }

        return sprintf(
            '<div data-period-wp-repeater-item data-item-index="%s">%s<p><button type="button" class="period-wp-metabox-repeater-remove">削除</button></p></div>',
            $this->escapeAttr($index),
            $itemFields
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
}
