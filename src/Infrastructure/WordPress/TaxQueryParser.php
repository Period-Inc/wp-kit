<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

final class TaxQueryParser
{
    public static function parse(string $string = null, bool $return_tax_query_key = true): array
    {
        if (!$string) {
            return [];
        }

        $decoded = json_decode($string, true);

        if (is_array($decoded)) {
            $result = self::fromJson($decoded);
            return $return_tax_query_key ? $result : ($result['tax_query'] ?? []);
        }

        return self::fromLegacy($string, $return_tax_query_key);
    }

    private static function fromJson(array $decoded): array
    {
        $relation = 'AND';
        $queries = [];

        if (isset($decoded['relation']) && in_array($decoded['relation'], ['AND', 'OR'], true)) {
            $relation = $decoded['relation'];
        }

        if (isset($decoded['queries']) && is_array($decoded['queries'])) {
            $queries = $decoded['queries'];
        } else {
            $queries = $decoded;
        }

        $tax_query = ['relation' => $relation];

        foreach ($queries as $query) {
            if (!is_array($query)) continue;
            if (!isset($query['taxonomy'], $query['field'], $query['terms'])) continue;
            $tax_query[] = $query;
        }

        return ['tax_query' => $tax_query];
    }

    private static function fromLegacy(string $string, bool $return_tax_query_key): array
    {
        $args = [];

        if ($string = preg_replace('/-(?:(?:&gt;)|>)/', '=>', $string)) {
            if (preg_match('/^(AND|OR)\x28(.*?)\x29$/', $string, $matches)) {
                $args['tax_query'] = ['relation' => $matches[1]];

                foreach (preg_split('/\x26(?:amp;)?/', $matches[2]) as $t) {
                    if ($t) {
                        $a = [];

                        foreach (explode(';', $t) as $p) {
                            $p = explode('=>', $p);

                            if (isset($p[1]) && preg_match('/^(.*?)\x28(.*?)\x29$/', $p[1], $m)) {
                                $a['taxonomy'] = $p[0];
                                $a['field'] = $m[1];
                                $a['terms'] = explode(',', $m[2]);
                            } else {
                                $a[$p[0]] = ($p[0] == 'operator' && isset($p[1]))
                                    ? str_replace('_', ' ', $p[1])
                                    : '';
                            }
                        }

                        $args['tax_query'][] = $a;
                    }
                }
            } else {
                if (preg_match('/^(.*?)(?:[-=])\x3e(.*?)\x28(.*?)\x29?$/', $string, $matches)) {
                    list($string, $tax, $field, $value) = $matches;

                    $args['tax_query'] = ['relation' => 'AND'];
                    $args['tax_query'][] = [
                        'field' => $field,
                        'taxonomy' => $tax,
                        'terms' => explode(',', $value),
                    ];
                }
            }
        }

        return $return_tax_query_key ? $args : ($args['tax_query'] ?? []);
    }
}
