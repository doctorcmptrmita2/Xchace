<?php
/**
 * Cache writer.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\Config;
use WPXCache\Logger\Logger;
use WPXCache\Security\FileGuard;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheWriter {
	public function __construct(
		private ?CacheStorage $storage = null,
		private ?CacheKey $cache_key = null,
		private ?FileGuard $file_guard = null,
		private ?Logger $logger = null,
		private ?CacheRules $rules = null
	) {
		$this->storage    = $storage ?: new CacheStorage();
		$this->cache_key  = $cache_key ?: new CacheKey();
		$this->file_guard = $file_guard ?: new FileGuard();
		$this->logger     = $logger ?: new Logger();
		$this->rules      = $rules ?: new CacheRules();
	}

	public function write(RequestContext $request, string $html): bool {
		if ('' === trim($html) || false === stripos($html, '<html')) {
			return false;
		}

		$path = $this->storage->path_for_request($request, $this->cache_key);

		if (! $this->storage->is_safe($path)) {
			$this->logger->error('Blocked cache write outside cache directory.', ['path' => $path, 'url' => $request->url()]);
			return false;
		}

		$directory = dirname($path);

		if (! $this->file_guard->ensure_directory($directory)) {
			$this->logger->error('Cache directory could not be prepared.', ['path' => $directory, 'url' => $request->url()]);
			return false;
		}

		/**
		 * Fires before a cache file is saved.
		 *
		 * @param string $url Request URL.
		 */
		do_action('wpxcache_before_cache_save', $request->url());

		$result = $this->file_guard->write_cache_file($path, $this->add_metadata($html, $request));

		if ($result && function_exists('gzencode')) {
			$this->file_guard->write_cache_file($path . '.gz', gzencode($this->add_metadata($html, $request), 6));
		}

		if ($result) {
			$this->logger->info('Cache file written.', ['path' => $path, 'url' => $request->url()]);
			/**
			 * Fires after a cache file is saved.
			 *
			 * @param string $url  Request URL.
			 * @param string $file Cache file path.
			 */
			do_action('wpxcache_after_cache_save', $request->url(), $path);
		} else {
			$this->logger->error('Cache file could not be written.', ['path' => $path, 'url' => $request->url()]);
		}

		return $result;
	}

	private function add_metadata(string $html, RequestContext $request): string {
		$ttl = $this->rules->ttl_for_request($request);
		$expires = gmdate('c', time() + $ttl);

		return $html . "\n<!-- Cached by WP XCache Pro " . esc_html(WPXCACHE_VERSION) . '; ttl=' . esc_html((string) $ttl) . '; expires=' . esc_html($expires) . ' -->';
	}
}
