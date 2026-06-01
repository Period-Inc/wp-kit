<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class DocumentRenderer
{
    private StartHtmlRenderer $start;
    private BodyRenderer $body;
    private EndHtmlRenderer $end;

    public function __construct(
        ?StartHtmlRenderer $start = null,
        ?BodyRenderer $body = null,
        ?EndHtmlRenderer $end = null,
    ) {
        $this->start = $start ?? new StartHtmlRenderer();
        $this->body = $body ?? new BodyRenderer();
        $this->end = $end ?? new EndHtmlRenderer();
    }

    public function render(string $content = '', array $args = []): string
    {
        $startArgs = [];
        if (isset($args['html_attrs'])) {
            $startArgs['html_attrs'] = $args['html_attrs'];
        }
        if (isset($args['head_elements'])) {
            $startArgs['elements'] = $args['head_elements'];
        }
        if (isset($args['include_wp_head'])) {
            $startArgs['include_wp_head'] = $args['include_wp_head'];
        }

        $bodyArgs = [];
        if (isset($args['body_class'])) {
            $bodyArgs['class'] = $args['body_class'];
        }
        if (isset($args['body_attrs'])) {
            $bodyArgs['attrs'] = $args['body_attrs'];
        }

        $endArgs = [];
        if (isset($args['include_wp_footer'])) {
            $endArgs['include_wp_footer'] = $args['include_wp_footer'];
        }

        $html = $this->start->render($startArgs);
        $html .= $this->body->render($bodyArgs);
        $html .= $content;
        $html .= $this->end->render($endArgs);

        return $html;
    }
}
