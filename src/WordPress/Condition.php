<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class Condition
{
    /**
     * Returns true when the resolved post matches one of the given post types.
     *
     * @param string|string[] $postType
     * @param int|\WP_Post|null $post Defaults to the current global post.
     */
    public function isPostType(string|array $postType, int|object|null $post = null): bool
    {
        if (!function_exists('get_post_type')) {
            return false;
        }

        $resolved = get_post_type($post);

        if ($resolved === false) {
            return false;
        }

        return in_array($resolved, (array) $postType, true);
    }

    /**
     * Returns true when the resolved user matches one of the given user IDs, logins, or emails.
     *
     * @param int|string|array<int|string> $user
     * @param int|\WP_User|null $currentUser Defaults to the current logged-in user.
     */
    public function isUser(int|string|array $user, int|object|null $currentUser = null): bool
    {
        if (!function_exists('get_userdata') || !function_exists('wp_get_current_user')) {
            return false;
        }

        if ($currentUser === null) {
            $currentUser = wp_get_current_user();
        }

        if (is_int($currentUser)) {
            $currentUser = get_userdata($currentUser);
        }

        if (!$currentUser || empty($currentUser->ID)) {
            return false;
        }

        foreach ((array) $user as $candidate) {
            if (is_int($candidate) && $candidate === (int) $currentUser->ID) {
                return true;
            }

            if (is_string($candidate)) {
                if ($candidate === $currentUser->user_login || $candidate === $currentUser->user_email) {
                    return true;
                }
            }
        }

        return false;
    }
}
