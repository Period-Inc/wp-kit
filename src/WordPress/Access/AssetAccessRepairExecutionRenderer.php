<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessRepairExecutionRenderer
{
    /** @param AssetAccessRepairExecutionResult[] $results */
    public function render(array $results): string
    {
        if ($results === []) {
            return '<p>No repair actions executed.</p>';
        }

        $rows = '';
        foreach ($results as $result) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape($result->success() ? 'success' : 'failed'),
                $this->escape($result->actionType()),
                $this->escape($result->path()),
                $this->escape($result->message()),
            );
        }

        return '<table class="widefat period-asset-access-repair-execution">'
            . '<thead><tr><th>Status</th><th>Action</th><th>Path</th><th>Message</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
