<?php
/**
 * Advanced rules admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Core\Config;
use WPXCache\Logger\Logger;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class AdvancedRulesPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_save();
		$settings = Config::settings();
		$cache = is_array($settings['cache'] ?? null) ? $settings['cache'] : [];
		$risk_items = RiskRegistry::items('advanced', $cache);

		require WPXCACHE_PATH . 'templates/admin/advanced-rules.php';
	}

	private function handle_save(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ('save_advanced_rules' !== $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$settings = Config::settings();
		$settings['cache']['never_cache_urls'] = $this->textarea_lines('never_cache_urls');
		$settings['cache']['never_cache_cookies'] = $this->textarea_lines('never_cache_cookies');
		$settings['cache']['never_cache_user_agents'] = $this->textarea_lines('never_cache_user_agents');
		$settings['cache']['query_string_whitelist'] = array_map('sanitize_key', $this->textarea_lines('query_string_whitelist'));
		$settings['cache']['custom_ttl'] = $this->custom_ttl();

		update_option(Config::OPTION_NAME, $settings, false);
		(new AdvancedCacheInstaller())->write_config();
		(new Logger())->info('Advanced cache rules saved.');

		return [
			'type'    => 'success',
			'message' => __('Advanced rules saved.', 'wpxcache'),
		];
	}

	/**
	 * @return array<int, string>
	 */
	private function textarea_lines(string $key): array {
		$raw = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);

		if (! is_string($raw)) {
			return [];
		}

		$raw = sanitize_textarea_field(wp_unslash($raw));
		$lines = preg_split('/\R/', $raw) ?: [];
		$lines = array_map('trim', $lines);
		$lines = array_filter($lines, static fn (string $line): bool => '' !== $line);

		return array_values(array_unique($lines));
	}

	/**
	 * @return array<int, array{pattern: string, ttl: int}>
	 */
	private function custom_ttl(): array {
		$rules = [];

		foreach ($this->textarea_lines('custom_ttl') as $line) {
			$parts = array_map('trim', explode('|', $line, 2));

			if (2 !== count($parts)) {
				continue;
			}

			$pattern = '/' . trim($parts[0], '/');
			$ttl = absint($parts[1]);

			if ($ttl <= 0) {
				continue;
			}

			$rules[] = [
				'pattern' => sanitize_text_field($pattern),
				'ttl'     => min($ttl, WEEK_IN_SECONDS),
			];
		}

		return $rules;
	}
}
