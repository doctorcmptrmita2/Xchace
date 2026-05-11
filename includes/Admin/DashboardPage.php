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
use WPXCache\Diagnostics\HealthCheck;
use WPXCache\Diagnostics\LogReader;
use WPXCache\Profile\ProfileEngine;
use WPXCache\Profile\SafeSettingsApplier;
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
		$profile = (new ProfileEngine())->detect();
		$checks   = (new HealthCheck())->checks();
		$storage  = new CacheStorage();
		$dropin   = (new AdvancedCacheInstaller())->status();
		$conflicts = (new ConflictDetector())->detect();
		$logs = (new LogReader())->recent(6);
		$stats    = [
			'count'      => $storage->html_file_count(),
			'size'       => size_format($storage->size_bytes()),
			'last_purge' => $this->format_last_purge(),
			'last_preload' => $this->format_timestamp((int) get_option('wpxcache_last_preload', 0)),
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

		if ('apply_smart_optimize' === $action) {
			$profile = (new ProfileEngine())->detect();
			$result = (new SafeSettingsApplier())->apply($profile['id']);

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

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

	private function format_last_purge(): string {
		return $this->format_timestamp((int) get_option('wpxcache_last_purge', 0));
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
