<?php
/**
 * Nonce helpers.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class Nonce {
	public const ACTION = 'wpxcache_admin_action';
	public const FIELD  = 'wpxcache_nonce';

	public static function field(): void {
		wp_nonce_field(self::ACTION, self::FIELD);
	}

	public static function verify_request(): bool {
		$value = filter_input(INPUT_POST, self::FIELD, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		return is_string($value) && wp_verify_nonce($value, self::ACTION);
	}
}
