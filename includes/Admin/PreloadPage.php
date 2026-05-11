<?php
/**
 * Preload admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Cache\CachePreloader;
use WPXCache\Core\Config;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class PreloadPage {
	public function render(): void {
		Capability::require_manage();

		$settings = Config::settings();
		$preloader = new CachePreloader($settings);
		$notice = $this->handle_action($preloader);
		$state = $preloader->state();

		require WPXCACHE_PATH . 'templates/admin/preload.php';
	}

	private function handle_action(CachePreloader $preloader): ?array {
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

		if ('start_preload' === $action) {
			$manual_urls = $this->manual_urls();
			$result = $preloader->start($preloader->build_urls($manual_urls));

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		if ('pause_preload' === $action) {
			$result = $preloader->pause();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		if ('resume_preload' === $action) {
			$result = $preloader->resume();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		if ('reset_preload' === $action) {
			$result = $preloader->reset();

			return [
				'type'    => $result['success'] ? 'success' : 'error',
				'message' => $result['message'],
			];
		}

		return [
			'type'    => 'error',
			'message' => __('Unknown preload action.', 'wpxcache'),
		];
	}

	/**
	 * @return array<int, string>
	 */
	private function manual_urls(): array {
		$raw = filter_input(INPUT_POST, 'wpxcache_preload_urls', FILTER_UNSAFE_RAW);

		if (! is_string($raw) || '' === trim($raw)) {
			return [];
		}

		$raw = sanitize_textarea_field(wp_unslash($raw));

		return array_filter(array_map('trim', preg_split('/\R/', $raw) ?: []));
	}
}
