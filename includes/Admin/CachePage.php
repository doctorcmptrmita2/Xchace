<?php
/**
 * Cache admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\CachePurger;
use WPXCache\Cache\CacheStorage;
use WPXCache\Core\Config;
use WPXCache\Logger\Logger;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class CachePage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_action();
		$settings = Config::settings();
		$cache = is_array($settings['cache'] ?? null) ? $settings['cache'] : [];
		$storage = new CacheStorage();
		$dropin = (new AdvancedCacheInstaller())->status();
		$stats = [
			'count' => $storage->html_file_count(),
			'size'  => size_format($storage->size_bytes()),
			'last_purge' => $this->format_timestamp((int) get_option('wpxcache_last_purge', 0)),
		];

		require WPXCACHE_PATH . 'templates/admin/cache.php';
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

		if ('save_cache_settings' === $action) {
			return $this->save_settings();
		}

		if ('clear_cache' === $action) {
			$success = (new CachePurger())->purge_all();

			return [
				'type'    => $success ? 'success' : 'error',
				'message' => $success ? __('Cache cleared.', 'wpxcache') : __('Cache could not be cleared.', 'wpxcache'),
			];
		}

		if ('regenerate_dropin' === $action) {
			$result = (new AdvancedCacheInstaller())->install();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown cache action.', 'wpxcache'),
		];
	}

	private function save_settings(): array {
		$settings = Config::settings();
		$settings['cache']['enabled'] = $this->posted_checkbox('cache_enabled');
		$settings['cache']['ttl'] = $this->int('cache_ttl', 3600, 60, WEEK_IN_SECONDS);
		$settings['cache']['separate_mobile_cache'] = $this->posted_checkbox('separate_mobile_cache');
		$settings['cache']['cache_logged_in_users'] = $this->posted_checkbox('cache_logged_in_users');
		$settings['cache']['cache_404'] = $this->posted_checkbox('cache_404');
		$settings['cache']['cache_search'] = $this->posted_checkbox('cache_search');
		$settings['cache']['cache_feeds'] = $this->posted_checkbox('cache_feeds');
		$settings['cache']['cache_rest_api'] = $this->posted_checkbox('cache_rest_api');
		$settings['cache']['purge_home_on_update'] = $this->posted_checkbox('purge_home_on_update');
		$settings['cache']['purge_archives_on_update'] = $this->posted_checkbox('purge_archives_on_update');

		update_option(Config::OPTION_NAME, $settings, false);
		(new AdvancedCacheInstaller())->write_config();
		(new Logger())->info('Cache settings saved.');

		return [
			'type'    => 'success',
			'message' => __('Cache settings saved.', 'wpxcache'),
		];
	}

	private function int(string $key, int $default, int $min, int $max): int {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
		$value = is_string($value) ? absint(wp_unslash($value)) : $default;

		return max($min, min($max, $value));
	}

	private function posted_checkbox(string $key): bool {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($value)) {
			return false;
		}

		return (bool) rest_sanitize_boolean(wp_unslash($value));
	}

	private function format_timestamp(int $timestamp): string {
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
