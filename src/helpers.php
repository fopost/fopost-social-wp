<?php

declare(strict_types=1);

// Prevent direct access. `return` (not `exit`) so the Composer `files`
// autoload doesn't kill CLI tools like PHPUnit that load outside WordPress.
if (! defined('ABSPATH')) {
    return;
}

use Fopost\Social\Wp\Plugin;
use Fopost\Social\Wp\Publishing\SendTo;

if (! function_exists('fopost_social')) {
    /**
     * Get the FoPost SendTo instance for publishing content.
     *
     * Usage:
     *     fopost_social()->telegram('Hello!');
     *     fopost_social()->twitter('Hello!');
     *     fopost_social()->toAll($post);
     */
    function fopost_social(): SendTo
    {
        return Plugin::instance()->sendTo();
    }
}
