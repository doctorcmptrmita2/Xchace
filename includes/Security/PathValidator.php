<?php
/**
 * Path validation helpers.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class PathValidator {
	public function is_within(string $path, string $base): bool {
		$real_base = realpath($base);

		if (false === $real_base) {
			return false;
		}

		$real_path = realpath($path);

		if (false === $real_path) {
			$parent = realpath(dirname($path));

			if (false === $parent) {
				return false;
			}

			$real_path = rtrim($parent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($path);
		}

		$real_path = wp_normalize_path($real_path);
		$real_base = wp_normalize_path($real_base);

		return 0 === strpos($real_path, rtrim($real_base, '/') . '/') || $real_path === $real_base;
	}

	public function is_safe_cache_path(string $path): bool {
		return $this->is_within($path, WPXCACHE_CACHE_DIR);
	}
}
