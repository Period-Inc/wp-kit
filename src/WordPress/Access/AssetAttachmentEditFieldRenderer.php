<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentEditFieldRenderer
{
    public function __construct(private readonly AssetAttachmentMetaReader $reader) {}

    public function render(int $attachmentId): string
    {
        $meta = $this->reader->read($attachmentId);

        $checked = $meta->isProtected() ? ' checked' : '';
        $path    = htmlspecialchars((string) ($meta->protectedPath() ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url     = htmlspecialchars((string) ($meta->deliveryUrl() ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name    = htmlspecialchars(
            'attachments[' . $attachmentId . '][period_asset_access_protected]',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        $labelProtected = htmlspecialchars('Protected', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labelPath      = htmlspecialchars('Protected Path', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labelUrl       = htmlspecialchars('Delivery URL', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $cssClass       = htmlspecialchars('widefat', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<label><input type="checkbox" name="%s" value="1"%s> %s</label>' .
            '<p><label>%s<br><input type="text" class="%s" value="%s" readonly></label></p>' .
            '<p><label>%s<br><input type="text" class="%s" value="%s" readonly></label></p>',
            $name,
            $checked,
            $labelProtected,
            $labelPath,
            $cssClass,
            $path,
            $labelUrl,
            $cssClass,
            $url,
        );
    }
}
