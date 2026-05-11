<?php
/**
 * Runtime requirement checks.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

if (! defined('ABSPATH')) {
	exit;
}

final class Requirements {
	private const MIN_PHP = '8.1';
	private const MIN_WP  = '6.4';

	/**
	 * Return whether the current environment can run the plugin.
	 */
	public function is_met(): bool {
		return version_compare(PHP_VERSION, self::MIN_PHP, '>=') && version_compare(get_bloginfo('version'), self::MIN_WP, '>=');
	}

	/**
	 * Render a safe admin notice when requirements are missing.
	 */
	public function render_admin_notice(): void {
		if (! current_user_can('activate_plugins')) {
			return;
		}

		$message = sprintf(
			/* translators: 1: minimum PHP version, 2: current PHP version, 3: minimum WordPress version, 4: current WordPress version */
			__('WP XCache Pro requires PHP %1$s+ and WordPress %3$s+. Current environment: PHP %2$s, WordPress %4$s.', 'wpxcache'),
			self::MIN_PHP,
			PHP_VERSION,
			self::MIN_WP,
			get_bloginfo('version')
		);

		printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($message));
	}
}
