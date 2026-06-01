<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairPlanRenderer
{
    public function render(AssetAccessRepairPlan $plan): string
    {
        $actions = $plan->actions();

        if ($actions === []) {
            return '<p>No repair actions required.</p>';
        }

        $rows = '';
        foreach ($actions as $action) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
                $this->escape($action->type()),
                $this->escape($action->path()),
                $this->escape($action->message()),
            );
        }

        return '<table class="widefat period-asset-access-repair">'
            . '<thead><tr><th>Action</th><th>Path</th><th>Message</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
