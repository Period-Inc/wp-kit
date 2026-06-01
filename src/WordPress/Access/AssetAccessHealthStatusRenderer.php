<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessHealthStatusRenderer
{
    /** @param AssetAccessHealthStatus[] $statuses */
    public function render(array $statuses): string
    {
        if ($statuses === []) {
            return '<p>No health issues reported.</p>';
        }

        $rows = '';
        foreach ($statuses as $status) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape($status->severity()),
                $this->escape($status->code()),
                $this->escape($status->message()),
            );
        }

        return '<table class="widefat period-asset-access-health">'
            . '<thead><tr><th>Severity</th><th>Code</th><th>Message</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
