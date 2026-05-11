<?php
/**
 * Escaping helpers.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class Escaper {
	public static function html(string $value): string {
		return esc_html($value);
	}

	public static function attr(string $value): string {
		return esc_attr($value);
	}

	public static function url(string $value): string {
		return esc_url($value);
	}
}
