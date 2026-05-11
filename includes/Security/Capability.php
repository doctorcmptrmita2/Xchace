<?php
/**
 * Capability checks.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class Capability {
	public const MANAGE = 'manage_options';

	public static function can_manage(): bool {
		return current_user_can(self::MANAGE);
	}

	public static function require_manage(): void {
		if (! self::can_manage()) {
			wp_die(esc_html__('You do not have permission to manage WP XCache Pro.', 'wpxcache'), 403);
		}
	}
}
