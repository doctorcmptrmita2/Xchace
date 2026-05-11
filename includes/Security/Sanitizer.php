<?php
/**
 * Sanitization helpers.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class Sanitizer {
	public static function bool(mixed $value): bool {
		return (bool) rest_sanitize_boolean($value);
	}

	public static function int(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int {
		$value = absint($value);

		return max($min, min($max, $value));
	}

	public static function text(mixed $value): string {
		return sanitize_text_field(is_scalar($value) ? (string) $value : '');
	}
}
