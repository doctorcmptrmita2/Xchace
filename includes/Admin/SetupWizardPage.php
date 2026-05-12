<?php
/**
 * First-run setup wizard.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Core\Config;
use WPXCache\Diagnostics\ConflictDetector;
use WPXCache\Diagnostics\EnvironmentScanner;
use WPXCache\Diagnostics\HealthCheck;
use WPXCache\Logger\Logger;
use WPXCache\Profile\ProfileEngine;
use WPXCache\Profile\SafeSettingsApplier;
use WPXCache\Security\Capability;
use WPXCache\Security\FileGuard;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class SetupWizardPage {
	private const COMPLETED_OPTION = 'wpxcache_setup_wizard_completed';

	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_action();
		$settings = Config::settings();
		$profile = (new ProfileEngine())->detect();
		$environment = (new EnvironmentScanner())->scan();
		$health_checks = (new HealthCheck())->checks();
		$conflicts = (new ConflictDetector())->detect();
		$dropin = (new AdvancedCacheInstaller())->status();
		$wizard_completed = (bool) get_option(self::COMPLETED_OPTION, false);
		$steps = $this->steps($profile, $environment, $conflicts, $dropin, $wizard_completed);
		$safe_settings = $this->safe_settings_preview($profile['id'], $environment);

		require WPXCACHE_PATH . 'templates/admin/setup-wizard.php';
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

		if ('setup_apply_safe_settings' === $action) {
			$profile = (new ProfileEngine())->detect();
			$result = (new SafeSettingsApplier())->apply($profile['id']);
			update_option(self::COMPLETED_OPTION, true, false);
			(new Logger())->info('Setup wizard applied safe settings.', ['profile' => $profile['id']]);

			return $this->notice_from_result($result);
		}

		if ('setup_prepare_directories' === $action) {
			return $this->prepare_cache_directories();
		}

		if ('setup_install_dropin' === $action) {
			$result = (new AdvancedCacheInstaller())->install();

			return $this->notice_from_result($result);
		}

		if ('setup_mark_complete' === $action) {
			update_option(self::COMPLETED_OPTION, true, false);
			(new Logger())->info('Setup wizard marked as completed.');

			return [
				'type'    => 'success',
				'message' => __('Setup wizard marked as completed.', 'wpxcache'),
			];
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown setup wizard action.', 'wpxcache'),
		];
	}

	/**
	 * @param array{id: string, label: string, confidence: int, signals: array<int, string>} $profile
	 * @param array<string, mixed> $environment
	 * @param array<int, array{level: string, message: string}> $conflicts
	 * @param array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool} $dropin
	 * @return array<int, array{number: int, title: string, status: string, summary: string}>
	 */
	private function steps(array $profile, array $environment, array $conflicts, array $dropin, bool $wizard_completed): array {
		$red_conflicts = array_filter(
			$conflicts,
			static fn (array $conflict): bool => 'red' === ($conflict['level'] ?? '')
		);

		$directories_ready = ! empty($environment['cache_writable']) && ! empty($environment['log_writable']);
		$dropin_ready = ! empty($dropin['exists']) && ! empty($dropin['owned']);
		$wp_cache_ready = ! empty($dropin['wp_cache']);

		return [
			[
				'number'  => 1,
				'title'   => __('Site analysis', 'wpxcache'),
				'status'  => 'green',
				'summary' => sprintf(
					/* translators: 1: profile label, 2: confidence percent */
					__('Recommended profile: %1$s (%2$d%% confidence).', 'wpxcache'),
					$profile['label'],
					absint($profile['confidence'])
				),
			],
			[
				'number'  => 2,
				'title'   => __('Safety checks', 'wpxcache'),
				'status'  => [] === $red_conflicts ? 'green' : 'red',
				'summary' => [] === $red_conflicts ? __('No critical cache conflict detected.', 'wpxcache') : __('Critical cache conflict needs review before enabling page cache.', 'wpxcache'),
			],
			[
				'number'  => 3,
				'title'   => __('Required files', 'wpxcache'),
				'status'  => $directories_ready && $dropin_ready && $wp_cache_ready ? 'green' : 'yellow',
				'summary' => $directories_ready && $dropin_ready && $wp_cache_ready ? __('Cache directories and drop-in are ready.', 'wpxcache') : __('Some filesystem or drop-in tasks are still pending.', 'wpxcache'),
			],
			[
				'number'  => 4,
				'title'   => __('Safe optimization', 'wpxcache'),
				'status'  => $wizard_completed ? 'green' : 'yellow',
				'summary' => $wizard_completed ? __('Setup wizard has been completed.', 'wpxcache') : __('Apply conservative settings when you are ready.', 'wpxcache'),
			],
		];
	}

	/**
	 * @param array<string, mixed> $environment
	 * @return array<int, array{label: string, status: string, detail: string}>
	 */
	private function safe_settings_preview(string $profile_id, array $environment): array {
		$is_woocommerce = 'woocommerce' === $profile_id || ! empty($environment['woocommerce']);

		return [
			[
				'label'  => __('Page cache', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Enabled only for anonymous safe GET requests.', 'wpxcache'),
			],
			[
				'label'  => __('Logged-in users', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Never cached by default.', 'wpxcache'),
			],
			[
				'label'  => __('WooCommerce Safe Mode', 'wpxcache'),
				'status' => 'Safe',
				'detail' => $is_woocommerce ? __('Cart, checkout, account and session cookies stay protected.', 'wpxcache') : __('Kept enabled even when WooCommerce is not active.', 'wpxcache'),
			],
			[
				'label'  => __('CSS/JS risky optimization', 'wpxcache'),
				'status' => 'Risky',
				'detail' => __('Combine, delay and CSS runtime rewriting are not enabled automatically.', 'wpxcache'),
			],
			[
				'label'  => __('Preload', 'wpxcache'),
				'status' => 'Safe',
				'detail' => __('Homepage preload settings are prepared with small batches.', 'wpxcache'),
			],
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
			$this->write_index(WPXCACHE_CACHE_DIR);
		}

		if ($log_ready) {
			$this->write_index(WPXCACHE_LOG_DIR);
			$file_guard->write_cache_file(trailingslashit(WPXCACHE_LOG_DIR) . '.htaccess', "Deny from all\n");
		}

		$success = $cache_ready && $log_ready;
		(new Logger())->info('Setup wizard prepared cache directories.', ['success' => $success]);

		return [
			'type'    => $success ? 'success' : 'error',
			'message' => $success ? __('Cache and log directories are ready.', 'wpxcache') : __('Cache or log directory could not be prepared. Check file permissions.', 'wpxcache'),
		];
	}

	private function write_index(string $directory): void {
		$file = trailingslashit($directory) . 'index.php';

		if (! is_file($file)) {
			(new FileGuard())->write_cache_file($file, "<?php\n// Silence is golden.\n");
		}
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
}
