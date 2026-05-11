<?php
/**
 * Local asset path resolver for file optimization.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Security\FileGuard;
use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class AssetPathResolver {
	public function __construct(private ?FileGuard $file_guard = null, private ?PathValidator $path_validator = null) {
		$this->file_guard = $file_guard ?: new FileGuard();
		$this->path_validator = $path_validator ?: new PathValidator();
	}

	/**
	 * @param array<int, string> $extensions
	 */
	public function local_file_for_url(string $src, array $extensions): ?string {
		$src = html_entity_decode($src, ENT_QUOTES, 'UTF-8');
		$parts = wp_parse_url($src);

		if (! is_array($parts)) {
			return null;
		}

		$host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';

		if ('' !== $host && ! $this->is_local_host($host)) {
			return null;
		}

		$path = isset($parts['path']) ? rawurldecode((string) $parts['path']) : '';

		if ('' === $path) {
			return null;
		}

		$mappings = [
			[content_url('/'), WP_CONTENT_DIR],
			[includes_url('/'), ABSPATH . WPINC],
			[site_url('/'), ABSPATH],
			[home_url('/'), ABSPATH],
		];

		foreach ($mappings as $mapping) {
			$candidate = $this->candidate_from_base($mapping[0], $mapping[1], $path);

			if (is_string($candidate) && $this->is_readable_asset($candidate, $extensions)) {
				return wp_normalize_path((string) realpath($candidate));
			}
		}

		return null;
	}

	public function cache_path(string $type, string $filename): string {
		return wp_normalize_path($this->cache_directory($type) . '/' . sanitize_file_name($filename));
	}

	public function cache_url(string $type, string $filename): string {
		return content_url('cache/wpxcache/assets/' . sanitize_key($type) . '/' . sanitize_file_name($filename));
	}

	public function ensure_cache_directory(string $type): bool {
		$assets_dir = wp_normalize_path(WPXCACHE_CACHE_DIR . '/assets');
		$type_dir = $this->cache_directory($type);

		if (! $this->file_guard->ensure_directory($assets_dir) || ! $this->file_guard->ensure_directory($type_dir)) {
			return false;
		}

		$this->write_public_htaccess($assets_dir);
		$this->write_public_htaccess($type_dir);

		return true;
	}

	private function cache_directory(string $type): string {
		return wp_normalize_path(WPXCACHE_CACHE_DIR . '/assets/' . sanitize_key($type));
	}

	private function is_local_host(string $host): bool {
		$local_hosts = array_filter(
			array_map(
				static fn (string $url): string => strtolower((string) wp_parse_url($url, PHP_URL_HOST)),
				[home_url('/'), site_url('/'), content_url('/'), includes_url('/')]
			)
		);

		return in_array($host, $local_hosts, true);
	}

	private function candidate_from_base(string $base_url, string $base_dir, string $request_path): ?string {
		$base_path = (string) wp_parse_url($base_url, PHP_URL_PATH);
		$base_path = '/' . trim(wp_normalize_path($base_path), '/');
		$base_path = '/' === $base_path ? '/' : trailingslashit($base_path);
		$request_path = '/' . ltrim(wp_normalize_path($request_path), '/');

		if ('/' !== $base_path && 0 !== strpos($request_path, rtrim($base_path, '/') . '/')) {
			return null;
		}

		$relative = '/' === $base_path ? ltrim($request_path, '/') : ltrim(substr($request_path, strlen(rtrim($base_path, '/'))), '/');

		if ('' === $relative || false !== strpos($relative, '../')) {
			return null;
		}

		return wp_normalize_path(trailingslashit($base_dir) . $relative);
	}

	/**
	 * @param array<int, string> $extensions
	 */
	private function is_readable_asset(string $path, array $extensions): bool {
		$real_path = realpath($path);

		if (! is_string($real_path) || ! is_file($real_path) || is_link($real_path) || ! is_readable($real_path)) {
			return false;
		}

		$extension = strtolower((string) pathinfo($real_path, PATHINFO_EXTENSION));

		if (! in_array($extension, array_map('strtolower', $extensions), true)) {
			return false;
		}

		return $this->path_validator->is_within($real_path, WP_CONTENT_DIR) || $this->path_validator->is_within($real_path, ABSPATH . WPINC);
	}

	private function write_public_htaccess(string $directory): void {
		$file = trailingslashit($directory) . '.htaccess';

		if (is_file($file)) {
			return;
		}

		$this->file_guard->write_cache_file(
			$file,
			"Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all granted\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nAllow from all\n</IfModule>\n"
		);
	}
}
