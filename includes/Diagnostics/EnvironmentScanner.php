<?php
/**
 * Environment scanner.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

if (! defined('ABSPATH')) {
	exit;
}

final class EnvironmentScanner {
	/**
	 * @return array<string, mixed>
	 */
	public function scan(): array {
		return [
			'php_version'       => PHP_VERSION,
			'wp_version'        => get_bloginfo('version'),
			'server_software'   => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash((string) $_SERVER['SERVER_SOFTWARE'])) : '',
			'wp_cache'          => defined('WP_CACHE') && WP_CACHE,
			'permalink_enabled' => (bool) get_option('permalink_structure'),
			'is_ssl'            => is_ssl(),
			'is_multisite'      => is_multisite(),
			'object_cache'      => wp_using_ext_object_cache(),
			'opcache'           => function_exists('opcache_get_status') && false !== opcache_get_status(false),
			'gzip'              => function_exists('gzencode'),
			'brotli'            => function_exists('brotli_compress'),
			'wp_cron_disabled'  => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
			'cache_dir'         => WPXCACHE_CACHE_DIR,
			'cache_writable'    => is_dir(WPXCACHE_CACHE_DIR) && wp_is_writable(WPXCACHE_CACHE_DIR),
			'log_dir'           => WPXCACHE_LOG_DIR,
			'log_writable'      => is_dir(WPXCACHE_LOG_DIR) && wp_is_writable(WPXCACHE_LOG_DIR),
			'disk_free_bytes'   => is_dir(WP_CONTENT_DIR) ? (int) disk_free_space(WP_CONTENT_DIR) : 0,
			'woocommerce'       => class_exists('WooCommerce'),
			'cloudflare'        => $this->looks_like_cloudflare(),
			'litespeed'         => $this->looks_like_litespeed(),
		];
	}

	private function looks_like_cloudflare(): bool {
		return isset($_SERVER['HTTP_CF_RAY']) || isset($_SERVER['HTTP_CF_CONNECTING_IP']);
	}

	private function looks_like_litespeed(): bool {
		$software = isset($_SERVER['SERVER_SOFTWARE']) ? (string) wp_unslash($_SERVER['SERVER_SOFTWARE']) : '';

		return false !== stripos($software, 'litespeed');
	}
}
