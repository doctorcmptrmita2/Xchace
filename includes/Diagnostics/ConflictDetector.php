<?php
/**
 * Basic cache conflict detection.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

use WPXCache\Cache\AdvancedCacheInstaller;

if (! defined('ABSPATH')) {
	exit;
}

final class ConflictDetector {
	/**
	 * @return array<int, array{level: string, message: string}>
	 */
	public function detect(): array {
		$warnings = [];
		$status = (new AdvancedCacheInstaller())->status();
		$env = (new EnvironmentScanner())->scan();

		if ($status['exists'] && ! $status['owned']) {
			$warnings[] = [
				'level'   => 'red',
				'message' => __('Another advanced-cache.php drop-in is active. Running multiple page cache systems can show incorrect content.', 'wpxcache'),
			];
		}

		if (! $status['wp_cache']) {
			$warnings[] = [
				'level'   => 'yellow',
				'message' => __('WP_CACHE is not enabled, so the drop-in cannot serve cached pages before WordPress loads.', 'wpxcache'),
			];
		}

		if (! empty($env['cloudflare'])) {
			$warnings[] = [
				'level'   => 'yellow',
				'message' => __('Cloudflare headers were detected. Use CDN purge carefully so visitors do not receive stale HTML.', 'wpxcache'),
			];
		}

		if (! empty($env['litespeed'])) {
			$warnings[] = [
				'level'   => 'yellow',
				'message' => __('LiteSpeed server was detected. Avoid enabling two independent full-page cache layers for the same pages.', 'wpxcache'),
			];
		}

		if (! empty($env['object_cache'])) {
			$warnings[] = [
				'level'   => 'green',
				'message' => __('External object cache appears to be active. This can work alongside page cache when page cache exclusions are respected.', 'wpxcache'),
			];
		}

		$server = is_scalar($env['server_software'] ?? '') ? (string) $env['server_software'] : '';

		if (false !== stripos($server, 'varnish')) {
			$warnings[] = [
				'level'   => 'yellow',
				'message' => __('Varnish-like server software was detected. Coordinate purge rules between server cache and WP XCache.', 'wpxcache'),
			];
		}

		if (false !== stripos($server, 'nginx')) {
			$warnings[] = [
				'level'   => 'green',
				'message' => __('Nginx was detected. WP XCache can run safely, but any FastCGI cache layer should be reviewed for overlap.', 'wpxcache'),
			];
		}

		if (function_exists('is_plugin_active')) {
			$known_plugins = [
				'wp-rocket/wp-rocket.php'                       => 'WP Rocket',
				'w3-total-cache/w3-total-cache.php'             => 'W3 Total Cache',
				'wp-fastest-cache/wpFastestCache.php'           => 'WP Fastest Cache',
				'litespeed-cache/litespeed-cache.php'           => 'LiteSpeed Cache',
				'autoptimize/autoptimize.php'                   => 'Autoptimize',
				'sg-cachepress/sg-cachepress.php'               => 'SiteGround Optimizer',
				'cache-enabler/cache-enabler.php'               => 'Cache Enabler',
				'redis-cache/redis-cache.php'                   => 'Redis Object Cache',
			];

			foreach ($known_plugins as $plugin => $name) {
				if (is_plugin_active($plugin)) {
					$warnings[] = [
						'level'   => 'yellow',
						'message' => sprintf(
							/* translators: %s: plugin name */
							__('Detected active cache-related plugin: %s. Review settings to avoid overlapping page cache behavior.', 'wpxcache'),
							$name
						),
					];
				}
			}
		}

		return $warnings;
	}
}
