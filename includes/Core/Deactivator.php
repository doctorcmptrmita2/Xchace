<?php
/**
 * Deactivation logic.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

use WPXCache\Cache\AdvancedCacheInstaller;

if (! defined('ABSPATH')) {
	exit;
}

final class Deactivator {
	/**
	 * Keep settings and cache data for safe reactivation.
	 */
	public static function deactivate(): void {
		(new AdvancedCacheInstaller())->remove();
		update_option('wpxcache_deactivated_at', time(), false);
	}
}
