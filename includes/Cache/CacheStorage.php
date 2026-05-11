<?php
/**
 * Cache file path resolver.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheStorage {
	public function __construct(private ?PathValidator $path_validator = null) {
		$this->path_validator = $path_validator ?: new PathValidator();
	}

	public function path_for_relative(string $relative_path): string {
		$relative_path = ltrim(wp_normalize_path($relative_path), '/');
		$path = WPXCACHE_CACHE_DIR . '/' . $relative_path;

		return wp_normalize_path($path);
	}

	public function path_for_request(RequestContext $request, CacheKey $key): string {
		return $this->path_for_relative($key->relative_path($request));
	}

	public function is_safe(string $path): bool {
		return $this->path_validator->is_safe_cache_path($path);
	}

	public function html_file_count(): int {
		if (! is_dir(WPXCACHE_CACHE_DIR)) {
			return 0;
		}

		$count = 0;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(WPXCACHE_CACHE_DIR, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isLink() || ! $file->isFile()) {
				continue;
			}

			if ('html' === strtolower($file->getExtension())) {
				++$count;
			}
		}

		return $count;
	}

	public function size_bytes(): int {
		if (! is_dir(WPXCACHE_CACHE_DIR)) {
			return 0;
		}

		$size = 0;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(WPXCACHE_CACHE_DIR, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file) {
			if ($file->isLink() || ! $file->isFile()) {
				continue;
			}

			$size += $file->getSize();
		}

		return $size;
	}
}
