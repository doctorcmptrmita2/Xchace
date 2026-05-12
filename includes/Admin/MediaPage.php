<?php
/**
 * Media optimization admin page.
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

final class MediaPage {
	public function render(): void {
		Capability::require_manage();

		$notice = $this->handle_save();
		$settings = Config::settings();
		$media = is_array($settings['media'] ?? null) ? $settings['media'] : [];
		$risk_items = RiskRegistry::items('media', $media);

		require WPXCACHE_PATH . 'templates/admin/media.php';
	}

	private function handle_save(): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ('save_media_settings' !== $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$settings = Config::settings();
		$settings['media']['lazy_load_images'] = $this->posted_checkbox('lazy_load_images');
		$settings['media']['lazy_load_iframes'] = $this->posted_checkbox('lazy_load_iframes');
		$settings['media']['youtube_placeholder'] = $this->posted_checkbox('youtube_placeholder');
		$settings['media']['disable_emoji'] = $this->posted_checkbox('disable_emoji');
		$settings['media']['disable_embeds'] = $this->posted_checkbox('disable_embeds');

		update_option(Config::OPTION_NAME, $settings, false);
		(new Logger())->info('Media optimization settings saved.');

		return [
			'type'    => 'success',
			'message' => __('Media optimization settings saved.', 'wpxcache'),
		];
	}

	private function posted_checkbox(string $key): bool {
		return filter_has_var(INPUT_POST, $key);
	}
}
