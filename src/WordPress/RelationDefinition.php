<?php
declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class RelationDefinition
{
    public function __construct(
        public readonly string $parentPostType,
        public readonly string $childPostType,
        public readonly string $parentMetaKey = 'relation_parent',
        public readonly string $childrenMetaKey = 'relation_children',
    ) {}

    public static function make(
        string $parentPostType,
        string $childPostType,
        string $parentMetaKey = 'relation_parent',
        string $childrenMetaKey = 'relation_children',
    ): self {
        return new self(
            $parentPostType,
            $childPostType,
            $parentMetaKey,
            $childrenMetaKey,
        );
    }
}
