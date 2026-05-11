<?php
/**
 * Cache reader for WordPress-loaded requests.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheReader {
	public function __construct(private ?CacheStorage $storage = null, private ?CacheKey $cache_key = null) {
		$this->storage   = $storage ?: new CacheStorage();
		$this->cache_key = $cache_key ?: new CacheKey();
	}

	public function read(RequestContext $request): ?string {
		$path = $this->storage->path_for_request($request, $this->cache_key);

		if (! $this->storage->is_safe($path) || ! is_readable($path) || is_link($path)) {
			return null;
		}

		$contents = file_get_contents($path);

		return is_string($contents) ? $contents : null;
	}
}
