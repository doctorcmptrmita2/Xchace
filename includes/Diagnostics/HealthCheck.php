<?php
/**
 * Health check registry.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Diagnostics;

use WPXCache\Cache\AdvancedCacheInstaller;

if (! defined('ABSPATH')) {
	exit;
}

final class HealthCheck {
	/**
	 * @return array<int, array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool}>
	 */
	public function checks(): array {
		$env = (new EnvironmentScanner())->scan();
		$dropin = (new AdvancedCacheInstaller())->status();
		$checks = [];

		$checks[] = $this->item(
			'wp_cache',
			__('WP_CACHE constant', 'wpxcache'),
			$env['wp_cache'] ? 'green' : 'yellow',
			__('WP_CACHE is not enabled.', 'wpxcache'),
			__('The advanced-cache.php drop-in only runs before WordPress when WP_CACHE is true.', 'wpxcache'),
			__('Enable WP_CACHE in wp-config.php after taking a backup.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'dropin',
			__('advanced-cache.php ownership', 'wpxcache'),
			! $dropin['exists'] ? 'yellow' : ($dropin['owned'] ? 'green' : 'red'),
			__('The active advanced-cache.php file is missing or belongs to another system.', 'wpxcache'),
			__('Only one full-page cache drop-in should control early cache serving.', 'wpxcache'),
			__('Install or regenerate the WP XCache drop-in, or disable the conflicting cache plugin first.', 'wpxcache'),
			! $dropin['exists'] || $dropin['owned']
		);

		$checks[] = $this->item(
			'cache_dir',
			__('Cache directory writable', 'wpxcache'),
			$env['cache_writable'] ? 'green' : 'red',
			__('The cache directory is not writable.', 'wpxcache'),
			__('WP XCache cannot save static HTML files without write access.', 'wpxcache'),
			__('Check ownership and permissions for wp-content/cache/wpxcache.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'log_dir',
			__('Log directory writable', 'wpxcache'),
			$env['log_writable'] ? 'green' : 'yellow',
			__('The log directory is not writable.', 'wpxcache'),
			__('Diagnostics and purge history need a private writable log directory.', 'wpxcache'),
			__('Check ownership and permissions for wp-content/cache/wpxcache/logs.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'gzip',
			__('Gzip support', 'wpxcache'),
			$env['gzip'] ? 'green' : 'yellow',
			__('PHP gzip support is unavailable.', 'wpxcache'),
			__('Without gzip, WP XCache can still cache HTML but cannot generate compressed copies.', 'wpxcache'),
			__('Enable the zlib extension in PHP if your host supports it.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'permalinks',
			__('Pretty permalinks', 'wpxcache'),
			$env['permalink_enabled'] ? 'green' : 'yellow',
			__('Pretty permalinks are disabled.', 'wpxcache'),
			__('Plain query-based URLs are harder to cache safely and predictably.', 'wpxcache'),
			__('Use a pretty permalink structure from WordPress Permalinks settings.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'disk_space',
			__('Disk space', 'wpxcache'),
			$env['disk_free_bytes'] > 100 * MB_IN_BYTES ? 'green' : 'red',
			__('Available disk space is low.', 'wpxcache'),
			__('Page cache files need disk space and low space can cause partial writes.', 'wpxcache'),
			__('Free disk space or reduce cache lifespan before enabling cache.', 'wpxcache'),
			false
		);

		$checks[] = $this->item(
			'woocommerce',
			__('WooCommerce safe exclusions', 'wpxcache'),
			$env['woocommerce'] ? 'green' : 'green',
			__('WooCommerce dynamic pages must stay excluded from cache.', 'wpxcache'),
			__('Cart, checkout, account and session cookies contain user-specific data.', 'wpxcache'),
			__('Keep WooCommerce Safe Mode enabled unless a developer has audited the store.', 'wpxcache'),
			false
		);

		return $checks;
	}

	/**
	 * @return array{id: string, label: string, status: string, problem: string, why: string, fix: string, auto_fix: bool}
	 */
	private function item(string $id, string $label, string $status, string $problem, string $why, string $fix, bool $auto_fix): array {
		return compact('id', 'label', 'status', 'problem', 'why', 'fix', 'auto_fix');
	}
}
