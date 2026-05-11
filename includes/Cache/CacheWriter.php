<?php
/**
 * Cache writer.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\Config;
use WPXCache\Security\FileGuard;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheWriter {
	public function __construct(
		private ?CacheStorage $storage = null,
		private ?CacheKey $cache_key = null,
		private ?FileGuard $file_guard = null
	) {
		$this->storage    = $storage ?: new CacheStorage();
		$this->cache_key  = $cache_key ?: new CacheKey();
		$this->file_guard = $file_guard ?: new FileGuard();
	}

	public function write(RequestContext $request, string $html): bool {
		if ('' === trim($html) || false === stripos($html, '<html')) {
			return false;
		}

		$path = $this->storage->path_for_request($request, $this->cache_key);

		if (! $this->storage->is_safe($path)) {
			return false;
		}

		$directory = dirname($path);

		if (! $this->file_guard->ensure_directory($directory)) {
			return false;
		}

		/**
		 * Fires before a cache file is saved.
		 *
		 * @param string $url Request URL.
		 */
		do_action('wpxcache_before_cache_save', $request->url());

		$result = $this->file_guard->write_cache_file($path, $this->add_metadata($html));

		if ($result && function_exists('gzencode')) {
			$this->file_guard->write_cache_file($path . '.gz', gzencode($this->add_metadata($html), 6));
		}

		if ($result) {
			/**
			 * Fires after a cache file is saved.
			 *
			 * @param string $url  Request URL.
			 * @param string $file Cache file path.
			 */
			do_action('wpxcache_after_cache_save', $request->url(), $path);
		}

		return $result;
	}

	private function add_metadata(string $html): string {
		return $html . "\n<!-- Cached by WP XCache Pro " . esc_html(WPXCACHE_VERSION) . ' -->';
	}
}
