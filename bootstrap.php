<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Application.php';
require_once __DIR__ . '/src/Infrastructure/WordPress/ScriptStyleRegistrar.php';
require_once __DIR__ . '/src/Infrastructure/ShortcodeRegistrar.php';
require_once __DIR__ . '/src/Support/ArgsResolver.php';
require_once __DIR__ . '/src/View/Renderer.php';

use Period\WpFramework\Application;

if (!function_exists('pwf')) {
    function pwf(): Application
    {
        static $instance = null;

        if (!$instance instanceof Application) {
            $instance = new Application(__DIR__);
        }

        return $instance;
    }
}
