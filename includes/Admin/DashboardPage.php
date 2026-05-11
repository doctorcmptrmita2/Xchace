<?php
/**
 * Dashboard page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Core\Config;

if (! defined('ABSPATH')) {
	exit;
}

final class DashboardPage {
	public function render(): void {
		$settings = Config::settings();
		$checks   = $this->get_foundation_checks();

		require WPXCACHE_PATH . 'templates/admin/dashboard.php';
	}

	/**
	 * @return array<int, array{label: string, status: string, message: string}>
	 */
	private function get_foundation_checks(): array {
		return [
			[
				'label'   => __('PHP version', 'wpxcache'),
				'status'  => version_compare(PHP_VERSION, '8.1', '>=') ? 'green' : 'red',
				'message' => sprintf(
					/* translators: %s: PHP version */
					__('Current PHP version: %s', 'wpxcache'),
					PHP_VERSION
				),
			],
			[
				'label'   => __('WordPress version', 'wpxcache'),
				'status'  => version_compare(get_bloginfo('version'), '6.4', '>=') ? 'green' : 'red',
				'message' => sprintf(
					/* translators: %s: WordPress version */
					__('Current WordPress version: %s', 'wpxcache'),
					get_bloginfo('version')
				),
			],
			[
				'label'   => __('Cache directory', 'wpxcache'),
				'status'  => is_dir(WPXCACHE_CACHE_DIR) && wp_is_writable(WPXCACHE_CACHE_DIR) ? 'green' : 'yellow',
				'message' => WPXCACHE_CACHE_DIR,
			],
			[
				'label'   => __('Log directory', 'wpxcache'),
				'status'  => is_dir(WPXCACHE_LOG_DIR) && wp_is_writable(WPXCACHE_LOG_DIR) ? 'green' : 'yellow',
				'message' => WPXCACHE_LOG_DIR,
			],
		];
	}
}
