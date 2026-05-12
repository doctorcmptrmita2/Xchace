<?php
/**
 * File optimization admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Core\Config;
use WPXCache\Logger\Logger;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class OptimizationPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_save();
		$settings = Config::settings();
		$optimization = is_array($settings['optimization'] ?? null) ? $settings['optimization'] : [];
		$risk_items = RiskRegistry::items('optimization', $optimization);

		require WPXCACHE_PATH . 'templates/admin/optimization.php';
	}

	private function handle_save(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ('save_optimization_settings' !== $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$settings = Config::settings();
		$settings['optimization']['minify_html'] = $this->posted_checkbox('minify_html');
		$settings['optimization']['minify_css'] = $this->posted_checkbox('minify_css');
		$settings['optimization']['combine_css'] = $this->posted_checkbox('combine_css');
		$settings['optimization']['defer_css'] = $this->posted_checkbox('defer_css');
		$settings['optimization']['minify_js'] = $this->posted_checkbox('minify_js');
		$settings['optimization']['defer_js'] = $this->posted_checkbox('defer_js');
		$settings['optimization']['delay_js'] = $this->posted_checkbox('delay_js');
		$settings['optimization']['remove_generator'] = $this->posted_checkbox('remove_generator');
		$settings['optimization']['safe_mode'] = $this->posted_checkbox('safe_mode');

		if (filter_has_var(INPUT_POST, 'exclude_css')) {
			$settings['optimization']['exclude_css'] = $this->posted_list('exclude_css');
		}

		if (filter_has_var(INPUT_POST, 'exclude_js')) {
			$settings['optimization']['exclude_js'] = $this->posted_list('exclude_js');
		}

		update_option(Config::OPTION_NAME, $settings, false);
		(new Logger())->info('File optimization settings saved.');

		return [
			'type'    => 'success',
			'message' => __('File optimization settings saved.', 'wpxcache'),
		];
	}

	private function posted_checkbox(string $key): bool {
		$value = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($value)) {
			return false;
		}

		return (bool) rest_sanitize_boolean(wp_unslash($value));
	}

	/**
	 * @return array<int, string>
	 */
	private function posted_list(string $key): array {
		$raw = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($raw)) {
			return [];
		}

		$raw = wp_unslash($raw);
		$items = preg_split('/[\r\n,]+/', $raw);

		if (! is_array($items)) {
			return [];
		}

		$sanitized = [];

		foreach ($items as $item) {
			$item = sanitize_text_field(trim((string) $item));

			if ('' !== $item) {
				$sanitized[] = $item;
			}
		}

		return array_values(array_unique($sanitized));
	}
}
