<?php
/**
 * Safe file-operation foundation.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Security;

if (! defined('ABSPATH')) {
	exit;
}

final class FileGuard {
	private PathValidator $path_validator;

	public function __construct(?PathValidator $path_validator = null) {
		$this->path_validator = $path_validator ?: new PathValidator();
	}

	public function ensure_directory(string $path): bool {
		$normalized_path = wp_normalize_path($path);

		if (! $this->path_validator->is_potentially_within($normalized_path, WP_CONTENT_DIR)) {
			return false;
		}

		if (is_link($normalized_path)) {
			return false;
		}

		if (! is_dir($normalized_path)) {
			return wp_mkdir_p($normalized_path);
		}

		return wp_is_writable($normalized_path);
	}

	public function can_write_cache_path(string $path): bool {
		if (is_link($path) || ! $this->path_validator->is_safe_cache_path($path)) {
			return false;
		}

		$directory = is_dir($path) ? $path : dirname($path);

		return is_dir($directory) && wp_is_writable($directory);
	}

	public function write_cache_file(string $path, string $contents): bool {
		if (! $this->can_write_cache_path($path)) {
			return false;
		}

		return false !== file_put_contents($path, $contents, LOCK_EX);
	}
}
