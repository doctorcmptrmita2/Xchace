<?php
/**
 * Cache key generator.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\Config;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheKey {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null) {
		$this->settings = $settings ?: Config::settings();
	}

	public function key(RequestContext $request): string {
		$parts = [
			'scheme' => $request->scheme(),
			'host'   => $request->host(),
			'path'   => $this->normalize_path($request->path()),
			'query'  => $this->whitelisted_query_string($request),
			'device' => $this->device_vary($request),
		];

		$key = implode('|', $parts);

		/**
		 * Filter the generated cache key.
		 *
		 * @param string         $key     Cache key.
		 * @param RequestContext $request Request context.
		 */
		return (string) apply_filters('wpxcache_cache_key', $key, $request);
	}

	public function relative_path(RequestContext $request): string {
		$host = $this->sanitize_segment($request->host());
		$path = trim($this->normalize_path($request->path()), '/');

		if ('' === $path) {
			$path = 'home';
		}

		$segments = array_map([$this, 'sanitize_segment'], explode('/', $path));
		$relative = $host . '/' . implode('/', array_filter($segments));
		$query = $this->whitelisted_query_string($request);
		$device = $this->device_vary($request);

		if ('' !== $query) {
			$relative .= '/query-' . substr(hash('sha256', $query), 0, 16);
		}

		if ('desktop' !== $device) {
			$relative .= '/' . $device;
		}

		return $relative . '/index.html';
	}

	private function normalize_path(string $path): string {
		$path = '/' . trim($path, '/');

		return '/' === $path ? '/' : untrailingslashit($path);
	}

	private function whitelisted_query_string(RequestContext $request): string {
		$whitelist = $this->settings['cache']['query_string_whitelist'] ?? [];

		if (! is_array($whitelist) || [] === $whitelist) {
			return '';
		}

		$query = array_intersect_key($request->query(), array_flip(array_map('sanitize_key', $whitelist)));

		return $request->query_string($query);
	}

	private function device_vary(RequestContext $request): string {
		if (empty($this->settings['cache']['separate_mobile_cache'])) {
			return 'desktop';
		}

		return wp_is_mobile() || preg_match('/Mobile|Android|iPhone|iPad/i', $request->user_agent()) ? 'mobile' : 'desktop';
	}

	private function sanitize_segment(string $segment): string {
		$segment = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $segment);

		return is_string($segment) && '' !== $segment ? $segment : 'page';
	}
}
