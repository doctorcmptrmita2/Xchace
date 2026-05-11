<?php
/**
 * Admin asset loader.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Core\ServiceProvider;

if (! defined('ABSPATH')) {
	exit;
}

final class Assets implements ServiceProvider {
	public function register(): void {
		add_action('admin_enqueue_scripts', [$this, 'enqueue']);
	}

	public function enqueue(string $hook_suffix): void {
		if (false === strpos($hook_suffix, 'wpxcache')) {
			return;
		}

		wp_enqueue_style(
			'wpxcache-admin',
			WPXCACHE_URL . 'assets/admin/css/admin.css',
			[],
			WPXCACHE_VERSION
		);

		wp_enqueue_script(
			'wpxcache-admin',
			WPXCACHE_URL . 'assets/admin/js/admin.js',
			[],
			WPXCACHE_VERSION,
			true
		);

		wp_localize_script(
			'wpxcache-admin',
			'WPXCacheAdmin',
			[
				'restUrl' => esc_url_raw(rest_url('wpxcache/v1')),
				'nonce'   => wp_create_nonce('wp_rest'),
			]
		);
	}
}
