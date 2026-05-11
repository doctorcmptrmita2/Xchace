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
use WPXCache\Logger\Logger;
use WPXCache\Optimization\AssetCacheManager;
use WPXCache\Profile\ProfileEngine;
use WPXCache\Profile\SafeSettingsApplier;
use WPXCache\Security\Capability;
use WPXCache\Security\FileGuard;
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
		$asset_stats = (new AssetCacheManager())->stats();
		$stats    = [
			'count'      => $storage->html_file_count(),
			'size'       => size_format($storage->size_bytes()),
			'last_purge' => $this->format_last_purge(),
			'last_preload' => $this->format_timestamp((int) get_option('wpxcache_last_preload', 0)),
			'optimized_assets' => $asset_stats['count'],
			'optimized_assets_size' => $asset_stats['size'],
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

		if ('prepare_cache_directories' === $action) {
			return $this->prepare_cache_directories();
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown WP XCache action.', 'wpxcache'),
		];
	}

	/**
	 * @return array{type: string, message: string}
	 */
	private function prepare_cache_directories(): array {
		$file_guard = new FileGuard();
		$cache_ready = $file_guard->ensure_directory(WPXCACHE_CACHE_DIR);
		$log_ready = $file_guard->ensure_directory(WPXCACHE_LOG_DIR);

		if ($cache_ready) {
			$this->write_protection_files(WPXCACHE_CACHE_DIR);
		}

		if ($log_ready) {
			$this->write_protection_files(WPXCACHE_LOG_DIR);
			$file_guard->write_cache_file(trailingslashit(WPXCACHE_LOG_DIR) . '.htaccess', "Deny from all\n");
		}

		$success = $cache_ready && $log_ready;
		(new Logger())->info('Cache directories prepared from Dashboard.', ['success' => $success]);

		return [
			'type'    => $success ? 'success' : 'error',
			'message' => $success ? __('Cache ve log dizinleri hazırlandı.', 'wpxcache') : __('Cache veya log dizini hazırlanamadı. Dosya izinlerini kontrol edin.', 'wpxcache'),
		];
	}

	private function write_protection_files(string $directory): void {
		$file_guard = new FileGuard();
		$index = trailingslashit($directory) . 'index.php';

		if (! is_file($index)) {
			$file_guard->write_cache_file($index, "<?php\n// Silence is golden.\n");
		}
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
