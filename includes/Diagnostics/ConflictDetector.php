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
