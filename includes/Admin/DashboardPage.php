<?php
/**
 * Dashboard page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\CacheStorage;
use WPXCache\Core\Config;
use WPXCache\Diagnostics\ConflictDetector;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class DashboardPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_action();
		$settings = Config::settings();
		$checks   = $this->get_foundation_checks();
		$storage  = new CacheStorage();
		$dropin   = (new AdvancedCacheInstaller())->status();
		$conflicts = (new ConflictDetector())->detect();
		$stats    = [
			'count'      => $storage->html_file_count(),
			'size'       => size_format($storage->size_bytes()),
			'last_purge' => $this->format_last_purge(),
		];

		require WPXCACHE_PATH . 'templates/admin/dashboard.php';
	}

	private function handle_action(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (! is_string($action) || '' === $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$installer = new AdvancedCacheInstaller();

		if ('install_dropin' === $action || 'regenerate_dropin' === $action) {
			$result = $installer->install();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		if ('remove_dropin' === $action) {
			$result = $installer->remove();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown WP XCache action.', 'wpxcache'),
		];
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
			[
				'label'   => __('WP_CACHE constant', 'wpxcache'),
				'status'  => defined('WP_CACHE') && WP_CACHE ? 'green' : 'yellow',
				'message' => defined('WP_CACHE') && WP_CACHE ? __('Enabled', 'wpxcache') : __('Not enabled', 'wpxcache'),
			],
		];
	}

	private function format_last_purge(): string {
		$timestamp = (int) get_option('wpxcache_last_purge', 0);

		if (0 === $timestamp) {
			return __('Never', 'wpxcache');
		}

		return sprintf(
			/* translators: %s: human-readable time difference */
			__('%s ago', 'wpxcache'),
			human_time_diff($timestamp, time())
		);
	}
}
