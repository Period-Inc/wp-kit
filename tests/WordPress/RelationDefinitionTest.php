<?php
declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\RelationDefinition;

final class RelationDefinitionTest extends TestCase
{
    public function testMakeWithDefaultMetaKeys(): void
    {
        $r = RelationDefinition::make('facility', 'job');

        $this->assertSame('facility', $r->parentPostType);
        $this->assertSame('job', $r->childPostType);
        $this->assertSame('relation_parent', $r->parentMetaKey);
        $this->assertSame('relation_children', $r->childrenMetaKey);
    }

    public function testMakeWithCustomMetaKeys(): void
    {
        $r = RelationDefinition::make('facility', 'job', 'facility_parent', 'facility_children');

        $this->assertSame('facility_parent', $r->parentMetaKey);
        $this->assertSame('facility_children', $r->childrenMetaKey);
    }

    public function testAllowsSameParentAndChildPostType(): void
    {
        $r = RelationDefinition::make('page', 'page');

        $this->assertSame('page', $r->parentPostType);
        $this->assertSame('page', $r->childPostType);
    }
}
