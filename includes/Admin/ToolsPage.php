<?php
/**
 * Tools admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\CachePurger;
use WPXCache\Core\Config;
use WPXCache\Diagnostics\DiagnosticsReport;
use WPXCache\Logger\Logger;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;
use WPXCache\Tools\SettingsManager;

if (! defined('ABSPATH')) {
	exit;
}

final class ToolsPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_action();
		$settings = Config::settings();
		$export = wp_json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		if (! is_string($export)) {
			$export = '{}';
		}

		require WPXCACHE_PATH . 'templates/admin/tools.php';
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

		$settings_manager = new SettingsManager();
		$logger = new Logger();

		if ('import_settings' === $action) {
			$raw = filter_input(INPUT_POST, 'wpxcache_import_settings', FILTER_UNSAFE_RAW);
			$result = $settings_manager->import(is_string($raw) ? wp_unslash($raw) : '');

			return $this->notice_from_result($result);
		}

		if ('reset_settings' === $action) {
			$result = $settings_manager->reset();

			return $this->notice_from_result($result);
		}

		if ('clear_cache' === $action) {
			$success = (new CachePurger())->purge_all();
			$logger->info('Manual clear cache requested from Tools.', ['success' => $success]);

			return [
				'type'    => $success ? 'success' : 'error',
				'message' => $success ? __('Cache cleared.', 'wpxcache') : __('Cache could not be cleared.', 'wpxcache'),
			];
		}

		if ('clear_logs' === $action) {
			$result = $settings_manager->clear_logs();

			return $this->notice_from_result($result);
		}

		if ('regenerate_dropin' === $action) {
			$result = (new AdvancedCacheInstaller())->install();

			return $this->notice_from_result($result);
		}

		if ('remove_dropin' === $action) {
			$result = (new AdvancedCacheInstaller())->remove();

			return $this->notice_from_result($result);
		}

		if ('download_diagnostics' === $action) {
			$this->download_diagnostics();
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown tools action.', 'wpxcache'),
		];
	}

	/**
	 * @param array{success: bool, message: string} $result
	 * @return array{type: string, message: string}
	 */
	private function notice_from_result(array $result): array {
		return [
			'type'    => $result['success'] ? 'success' : 'error',
			'message' => $result['message'],
		];
	}

	private function download_diagnostics(): void {
		$report = (new DiagnosticsReport())->generate();
		$json = wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		if (! is_string($json)) {
			$json = '{}';
		}

		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="wpxcache-diagnostics-' . gmdate('Ymd-His') . '.json"');
		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
