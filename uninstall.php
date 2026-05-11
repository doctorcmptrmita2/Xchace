<?php
/**
 * Uninstall handler.
 *
 * @package WPXCache
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

delete_option('wpxcache_settings');
delete_option('wpxcache_version');
