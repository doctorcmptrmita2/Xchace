<?php
/**
 * WP-CLI commands for WP XCache Pro.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cli;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\CachePreloader;
use WPXCache\Cache\CachePurger;
use WPXCache\Cache\CacheStorage;
use WPXCache\Core\Config;
use WPXCache\Diagnostics\DiagnosticsReport;
use WPXCache\Optimization\AssetCacheManager;
use WPXCache\Tools\SettingsManager;

if (! defined('ABSPATH')) {
	exit;
}

final class WpCliCommands {
	public function register(): void {
		\WP_CLI::add_command('wpxcache', $this);
	}

	/**
	 * Show cache status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache status
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function status(array $args = [], array $assoc_args = []): void {
		unset($args, $assoc_args);

		$settings  = Config::settings();
		$storage   = new CacheStorage();
		$dropin    = (new AdvancedCacheInstaller())->status();
		$preloader = new CachePreloader($settings);
		$preload   = $preloader->state();
		$asset_stats = (new AssetCacheManager())->stats();

		$items = [
			[
				'name'  => 'Plugin version',
				'value' => WPXCACHE_VERSION,
			],
			[
				'name'  => 'Page cache',
				'value' => ! empty($settings['cache']['enabled']) ? 'enabled' : 'disabled',
			],
			[
				'name'  => 'WP_CACHE',
				'value' => defined('WP_CACHE') && WP_CACHE ? 'enabled' : 'disabled',
			],
			[
				'name'  => 'advanced-cache.php',
				'value' => $this->dropin_label($dropin),
			],
			[
				'name'  => 'Cached pages',
				'value' => (string) $storage->html_file_count(),
			],
			[
				'name'  => 'Cache size',
				'value' => size_format($storage->size_bytes(), 2),
			],
			[
				'name'  => 'Last purge',
				'value' => $this->format_timestamp((int) get_option('wpxcache_last_purge', 0)),
			],
			[
				'name'  => 'Last preload',
				'value' => $this->format_timestamp((int) get_option('wpxcache_last_preload', 0)),
			],
			[
				'name'  => 'Preload status',
				'value' => sanitize_key((string) ($preload['status'] ?? 'idle')),
			],
			[
				'name'  => 'Optimized assets',
				'value' => (string) $asset_stats['count'],
			],
			[
				'name'  => 'Optimized asset size',
				'value' => (string) $asset_stats['size'],
			],
		];

		\WP_CLI\Utils\format_items('table', $items, ['name', 'value']);
	}

	/**
	 * Purge cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache purge all
	 *     wp wpxcache purge url https://example.com/page/
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function purge(array $args, array $assoc_args = []): void {
		unset($assoc_args);

		$type   = sanitize_key((string) ($args[0] ?? 'all'));
		$purger = new CachePurger();

		if ('all' === $type) {
			$this->finish($purger->purge_all(), 'All cache files purged.', 'Cache purge failed.');
			return;
		}

		if ('url' === $type) {
			$url = isset($args[1]) ? esc_url_raw((string) $args[1]) : '';

			if ('' === $url || ! wp_http_validate_url($url)) {
				\WP_CLI::error('Please provide a valid URL.');
			}

			$this->finish($purger->purge_url($url), 'URL cache purged.', 'URL cache purge failed.');
			return;
		}

		\WP_CLI::error('Unknown purge target. Use: all or url <url>.');
	}

	/**
	 * Manage optimized asset cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache assets status
	 *     wp wpxcache assets clear
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function assets(array $args = [], array $assoc_args = []): void {
		unset($assoc_args);

		$action = sanitize_key((string) ($args[0] ?? 'status'));
		$manager = new AssetCacheManager();

		if ('clear' === $action) {
			$result = $manager->clear();
			$this->finish($result['success'], $result['message'], $result['message']);
			return;
		}

		if ('status' === $action) {
			$stats = $manager->stats();
			\WP_CLI\Utils\format_items(
				'table',
				[
					['name' => 'Exists', 'value' => $stats['exists'] ? 'yes' : 'no'],
					['name' => 'Writable', 'value' => $stats['writable'] ? 'yes' : 'no'],
					['name' => 'Optimized assets', 'value' => (string) $stats['count']],
					['name' => 'CSS files', 'value' => (string) $stats['css_count']],
					['name' => 'JS files', 'value' => (string) $stats['js_count']],
					['name' => 'Size', 'value' => (string) $stats['size']],
				],
				['name', 'value']
			);
			return;
		}

		\WP_CLI::error('Unknown assets action. Use: status or clear.');
	}

	/**
	 * Start a safe cache preload.
	 *
	 * ## OPTIONS
	 *
	 * [<url>...]
	 * : Optional URLs to include with the configured preload URLs.
	 *
	 * [--url=<url>]
	 * : Optional URL to include. Can be repeated by WP-CLI.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache preload
	 *     wp wpxcache preload https://example.com/page/
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function preload(array $args = [], array $assoc_args = []): void {
		$settings  = Config::settings();
		$preloader = new CachePreloader($settings);
		$manual    = $this->collect_urls($args, $assoc_args);
		$result    = $preloader->start($preloader->build_urls($manual));

		$this->finish($result['success'], $result['message'], $result['message']);
	}

	/**
	 * Manage the advanced-cache.php drop-in.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache dropin install
	 *     wp wpxcache dropin remove
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function dropin(array $args, array $assoc_args = []): void {
		unset($assoc_args);

		$action    = sanitize_key((string) ($args[0] ?? 'status'));
		$installer = new AdvancedCacheInstaller();

		if ('install' === $action) {
			$result = $installer->install();
			$this->finish($result['success'], $result['message'], $result['message']);
			return;
		}

		if ('remove' === $action) {
			$result = $installer->remove();
			$this->finish($result['success'], $result['message'], $result['message']);
			return;
		}

		if ('status' === $action) {
			$status = $installer->status();
			\WP_CLI\Utils\format_items(
				'table',
				[
					['name' => 'Exists', 'value' => $status['exists'] ? 'yes' : 'no'],
					['name' => 'Owned by WP XCache', 'value' => $status['owned'] ? 'yes' : 'no'],
					['name' => 'WP_CACHE', 'value' => $status['wp_cache'] ? 'enabled' : 'disabled'],
					['name' => 'Config file', 'value' => $status['config_exists'] ? 'exists' : 'missing'],
					['name' => 'WP content writable', 'value' => $status['writable'] ? 'yes' : 'no'],
				],
				['name', 'value']
			);
			return;
		}

		\WP_CLI::error('Unknown drop-in action. Use: status, install or remove.');
	}

	/**
	 * Print a redacted diagnostics report as JSON.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache diagnostics
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function diagnostics(array $args = [], array $assoc_args = []): void {
		unset($args, $assoc_args);

		$json = wp_json_encode((new DiagnosticsReport())->generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		\WP_CLI::line(is_string($json) ? $json : '{}');
	}

	/**
	 * Export or import settings.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpxcache settings export
	 *     wp wpxcache settings import settings.json
	 *
	 * @param array<int, string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function settings(array $args, array $assoc_args = []): void {
		unset($assoc_args);

		$action = sanitize_key((string) ($args[0] ?? ''));

		if ('export' === $action) {
			$json = wp_json_encode(Config::settings(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			\WP_CLI::line(is_string($json) ? $json : '{}');
			return;
		}

		if ('import' === $action) {
			$file = isset($args[1]) ? (string) $args[1] : '';

			if ('' === $file) {
				\WP_CLI::error('Please provide a JSON file path.');
			}

			$json = $this->read_import_file($file);
			$result = (new SettingsManager())->import($json);
			$this->finish($result['success'], $result['message'], $result['message']);
			return;
		}

		\WP_CLI::error('Unknown settings action. Use: export or import <file>.');
	}

	/**
	 * @param array<string, mixed> $dropin
	 */
	private function dropin_label(array $dropin): string {
		if (empty($dropin['exists'])) {
			return 'missing';
		}

		return ! empty($dropin['owned']) ? 'installed' : 'conflict';
	}

	private function format_timestamp(int $timestamp): string {
		if ($timestamp <= 0) {
			return 'never';
		}

		return date_i18n('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * @param array<int, string> $args
	 * @param array<string, mixed> $assoc_args
	 * @return array<int, string>
	 */
	private function collect_urls(array $args, array $assoc_args): array {
		$urls = $args;
		$extra = $assoc_args['url'] ?? [];

		if (is_string($extra)) {
			$urls[] = $extra;
		}

		if (is_array($extra)) {
			foreach ($extra as $url) {
				if (is_scalar($url)) {
					$urls[] = (string) $url;
				}
			}
		}

		return array_values(array_filter(array_map('esc_url_raw', $urls)));
	}

	private function read_import_file(string $file): string {
		$real_path = realpath($file);

		if (! is_string($real_path) || ! is_file($real_path) || is_link($real_path) || ! is_readable($real_path)) {
			\WP_CLI::error('Settings file is missing or not readable.');
		}

		$contents = file_get_contents($real_path);

		if (! is_string($contents) || '' === trim($contents)) {
			\WP_CLI::error('Settings file is empty or could not be read.');
		}

		return $contents;
	}

	private function finish(bool $success, string $success_message, string $error_message): void {
		if ($success) {
			\WP_CLI::success($success_message);
			return;
		}

		\WP_CLI::error($error_message);
	}
}
