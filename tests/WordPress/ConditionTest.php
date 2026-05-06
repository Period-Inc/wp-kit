<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Condition;

final class ConditionTest extends TestCase
{
    // --- isPostType ---

    /** @runInSeparateProcess */
    public function testIsPostTypeReturnsTrueWhenMatches(): void
    {
        eval('function get_post_type($post = null): string|false { return "news"; }');

        $this->assertTrue((new Condition())->isPostType('news'));
    }

    /** @runInSeparateProcess */
    public function testIsPostTypeReturnsFalseWhenMismatch(): void
    {
        eval('function get_post_type($post = null): string|false { return "post"; }');

        $this->assertFalse((new Condition())->isPostType('news'));
    }

    /** @runInSeparateProcess */
    public function testIsPostTypeAcceptsArray(): void
    {
        eval('function get_post_type($post = null): string|false { return "event"; }');

        $this->assertTrue((new Condition())->isPostType(['news', 'event']));
    }

    /** @runInSeparateProcess */
    public function testIsPostTypeReturnsFalseWhenGetPostTypeReturnsFalse(): void
    {
        eval('function get_post_type($post = null): string|false { return false; }');

        $this->assertFalse((new Condition())->isPostType('news'));
    }

    public function testIsPostTypeReturnsFalseWithoutWordPress(): void
    {
        $this->assertFalse((new Condition())->isPostType('news'));
    }

    // --- isUser ---

    /** @runInSeparateProcess */
    public function testIsUserReturnsTrueForMatchingId(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false {
                return (object)["ID" => 5, "user_login" => "alice", "user_email" => "alice@example.com"];
            }
        ');

        $this->assertTrue((new Condition())->isUser(5, 5));
    }

    /** @runInSeparateProcess */
    public function testIsUserReturnsTrueForMatchingLogin(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false {
                return (object)["ID" => 5, "user_login" => "alice", "user_email" => "alice@example.com"];
            }
        ');

        $this->assertTrue((new Condition())->isUser('alice', 5));
    }

    /** @runInSeparateProcess */
    public function testIsUserReturnsTrueForMatchingEmail(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false {
                return (object)["ID" => 5, "user_login" => "alice", "user_email" => "alice@example.com"];
            }
        ');

        $this->assertTrue((new Condition())->isUser('alice@example.com', 5));
    }

    /** @runInSeparateProcess */
    public function testIsUserReturnsFalseForMismatch(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false {
                return (object)["ID" => 5, "user_login" => "alice", "user_email" => "alice@example.com"];
            }
        ');

        $this->assertFalse((new Condition())->isUser(99, 5));
    }

    /** @runInSeparateProcess */
    public function testIsUserReturnsFalseWhenGetUserdataReturnsFalse(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false { return false; }
        ');

        $this->assertFalse((new Condition())->isUser(5, 5));
    }

    /** @runInSeparateProcess */
    public function testIsUserReturnsFalseWhenUserIdIsZero(): void
    {
        eval('
            function wp_get_current_user(): object { return (object)["ID" => 0]; }
            function get_userdata(int $id): object|false {
                return (object)["ID" => 0, "user_login" => "", "user_email" => ""];
            }
        ');

        $this->assertFalse((new Condition())->isUser(0, 0));
    }

    public function testIsUserReturnsFalseWithoutWordPress(): void
    {
        $this->assertFalse((new Condition())->isUser(1));
    }
}
