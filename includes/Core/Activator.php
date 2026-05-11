<?php
/**
 * Activation logic.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

use WPXCache\Security\FileGuard;

if (! defined('ABSPATH')) {
	exit;
}

final class Activator {
	/**
	 * Prepare safe defaults and directories.
	 */
	public static function activate(): void {
		$requirements = new Requirements();

		if (! $requirements->is_met()) {
			deactivate_plugins(plugin_basename(WPXCACHE_FILE));
			wp_die(
				esc_html__('WP XCache Pro cannot be activated because the server does not meet the minimum requirements.', 'wpxcache'),
				esc_html__('Plugin activation failed', 'wpxcache'),
				['back_link' => true]
			);
		}

		if (false === get_option(Config::OPTION_NAME, false)) {
			add_option(Config::OPTION_NAME, Config::defaults(), '', false);
		}

		update_option('wpxcache_version', WPXCACHE_VERSION, false);

		if (! is_dir(WPXCACHE_CACHE_DIR)) {
			wp_mkdir_p(WPXCACHE_CACHE_DIR);
		}

		if (! is_dir(WPXCACHE_LOG_DIR)) {
			wp_mkdir_p(WPXCACHE_LOG_DIR);
		}

		self::write_index_file(WPXCACHE_CACHE_DIR);
		self::write_index_file(WPXCACHE_LOG_DIR);
	}

	private static function write_index_file(string $dir): void {
		$file = trailingslashit($dir) . 'index.php';

		if (is_dir($dir) && ! file_exists($file)) {
			(new FileGuard())->write_cache_file($file, "<?php\n// Silence is golden.\n");
		}
	}
}
