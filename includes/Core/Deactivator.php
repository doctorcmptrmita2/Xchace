<?php
/**
 * Deactivation logic.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

if (! defined('ABSPATH')) {
	exit;
}

final class Deactivator {
	/**
	 * Keep settings and cache data for safe reactivation.
	 */
	public static function deactivate(): void {
		update_option('wpxcache_deactivated_at', time(), false);
	}
}
