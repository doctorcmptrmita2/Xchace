<?php
/**
 * CDN admin page.
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

final class CdnPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_save();
		$settings = Config::settings();
		$cdn = is_array($settings['cdn'] ?? null) ? $settings['cdn'] : [];

		require WPXCACHE_PATH . 'templates/admin/cdn.php';
	}

	private function handle_save(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ('save_cdn' !== $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$settings = Config::settings();
		$settings['cdn'] = [
			'enabled'                   => isset($_POST['cdn_enabled']),
			'base_url'                  => esc_url_raw((string) wp_unslash($_POST['cdn_base_url'] ?? '')),
			'included_file_types'       => $this->csv('cdn_included_file_types'),
			'excluded_paths'            => $this->lines('cdn_excluded_paths'),
			'cloudflare_enabled'        => isset($_POST['cloudflare_enabled']),
			'cloudflare_api_token'      => sanitize_text_field((string) wp_unslash($_POST['cloudflare_api_token'] ?? '')),
			'cloudflare_zone_id'        => sanitize_text_field((string) wp_unslash($_POST['cloudflare_zone_id'] ?? '')),
			'purge_cloudflare_on_purge' => isset($_POST['purge_cloudflare_on_purge']),
		];

		update_option(Config::OPTION_NAME, $settings, false);
		(new Logger())->info('CDN settings saved.');

		return [
			'type'    => 'success',
			'message' => __('CDN settings saved.', 'wpxcache'),
		];
	}

	/**
	 * @return array<int, string>
	 */
	private function csv(string $key): array {
		$value = sanitize_text_field((string) wp_unslash($_POST[$key] ?? ''));
		$items = array_map('trim', explode(',', $value));

		return array_values(array_unique(array_filter(array_map('sanitize_key', $items))));
	}

	/**
	 * @return array<int, string>
	 */
	private function lines(string $key): array {
		$value = sanitize_textarea_field((string) wp_unslash($_POST[$key] ?? ''));
		$items = preg_split('/\R/', $value) ?: [];
		$items = array_map('trim', $items);

		return array_values(array_unique(array_filter(array_map('sanitize_text_field', $items))));
	}
}
