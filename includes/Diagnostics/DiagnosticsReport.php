<?php
/**
 * Diagnostics report generator.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\CachePreloader;
use WPXCache\Cache\CacheStorage;
use WPXCache\Core\Config;
use WPXCache\Profile\ProfileEngine;

if (! defined('ABSPATH')) {
	exit;
}

final class DiagnosticsReport {
	/**
	 * @return array<string, mixed>
	 */
	public function generate(): array {
		$storage = new CacheStorage();
		$settings = Config::settings();

		return [
			'generated_at' => gmdate('c'),
			'plugin'       => [
				'version' => WPXCACHE_VERSION,
			],
			'environment'  => (new EnvironmentScanner())->scan(),
			'health'       => (new HealthCheck())->checks(),
			'conflicts'    => (new ConflictDetector())->detect(),
			'profile'      => (new ProfileEngine())->detect(),
			'dropin'       => (new AdvancedCacheInstaller())->status(),
			'cache'        => [
				'enabled'          => ! empty($settings['cache']['enabled']),
				'cached_pages'     => $storage->html_file_count(),
				'cache_size_bytes' => $storage->size_bytes(),
				'last_purge'       => (int) get_option('wpxcache_last_purge', 0),
			],
			'preload'      => (new CachePreloader($settings))->state(),
			'cdn'          => [
				'enabled'                  => ! empty($settings['cdn']['enabled']),
				'base_url'                 => $settings['cdn']['base_url'] ?? '',
				'cloudflare_enabled'       => ! empty($settings['cdn']['cloudflare_enabled']),
				'cloudflare_zone_id'       => ! empty($settings['cdn']['cloudflare_zone_id']) ? '[masked]' : '',
				'purge_cloudflare_on_purge'=> ! empty($settings['cdn']['purge_cloudflare_on_purge']),
			],
			'settings'     => $this->redacted_settings($settings),
		];
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private function redacted_settings(array $settings): array {
		$redacted = $settings;
		$sensitive_keys = ['api_token', 'token', 'secret', 'password', 'zone_id'];

		array_walk_recursive(
			$redacted,
			static function (&$value, $key) use ($sensitive_keys): void {
				$key = (string) $key;

				foreach ($sensitive_keys as $sensitive_key) {
					if (false !== stripos($key, $sensitive_key)) {
						$value = '[masked]';
						return;
					}
				}
			}
		);

		return $redacted;
	}
}
