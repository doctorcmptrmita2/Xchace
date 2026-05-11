<?php
/**
 * Plugin Name: WP XCache Pro
 * Plugin URI: https://example.com/wp-xcache-pro
 * Description: One-click safe speed optimization for WordPress.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: WP XCache
 * Author URI: https://example.com
 * Text Domain: wpxcache
 * Domain Path: /languages
 *
 * @package WPXCache
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

define('WPXCACHE_VERSION', '0.1.0');
define('WPXCACHE_FILE', __FILE__);
define('WPXCACHE_PATH', plugin_dir_path(__FILE__));
define('WPXCACHE_URL', plugin_dir_url(__FILE__));
define('WPXCACHE_CACHE_DIR', WP_CONTENT_DIR . '/cache/wpxcache');
define('WPXCACHE_LOG_DIR', WPXCACHE_CACHE_DIR . '/logs');

spl_autoload_register(
	static function (string $class): void {
		$prefix = 'WPXCache\\';

		if (0 !== strpos($class, $prefix)) {
			return;
		}

		$relative_class = substr($class, strlen($prefix));
		$file           = WPXCACHE_PATH . 'includes/' . str_replace('\\', '/', $relative_class) . '.php';

		if (is_readable($file)) {
			require_once $file;
		}
	}
);

register_activation_hook(WPXCACHE_FILE, ['WPXCache\\Core\\Activator', 'activate']);
register_deactivation_hook(WPXCACHE_FILE, ['WPXCache\\Core\\Deactivator', 'deactivate']);

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain('wpxcache', false, dirname(plugin_basename(WPXCACHE_FILE)) . '/languages');

		$requirements = new WPXCache\Core\Requirements();

		if (! $requirements->is_met()) {
			add_action('admin_notices', [$requirements, 'render_admin_notice']);
			return;
		}

		WPXCache\Core\Plugin::instance()->boot();
	}
);

if (! function_exists('wpxcache_purge_all')) {
	/**
	 * Purge all WP XCache generated cache files.
	 */
	function wpxcache_purge_all(): bool {
		if (! class_exists('WPXCache\\Cache\\CachePurger')) {
			return false;
		}

		return (new WPXCache\Cache\CachePurger())->purge_all();
	}
}

if (! function_exists('wpxcache_is_cache_enabled')) {
	/**
	 * Return whether page cache is enabled in plugin settings.
	 */
	function wpxcache_is_cache_enabled(): bool {
		if (! class_exists('WPXCache\\Core\\Config')) {
			return false;
		}

		$settings = WPXCache\Core\Config::settings();

		return ! empty($settings['cache']['enabled']);
	}
}

if (! function_exists('wpxcache_get_cache_status')) {
	/**
	 * Return a compact cache status snapshot.
	 *
	 * @return array{enabled: bool, cached_pages: int, cache_size_bytes: int, last_purge: int}
	 */
	function wpxcache_get_cache_status(): array {
		if (! class_exists('WPXCache\\Core\\Config') || ! class_exists('WPXCache\\Cache\\CacheStorage')) {
			return [
				'enabled'          => false,
				'cached_pages'     => 0,
				'cache_size_bytes' => 0,
				'last_purge'       => 0,
			];
		}

		$settings = WPXCache\Core\Config::settings();
		$storage  = new WPXCache\Cache\CacheStorage();

		return [
			'enabled'          => ! empty($settings['cache']['enabled']),
			'cached_pages'     => $storage->html_file_count(),
			'cache_size_bytes' => $storage->size_bytes(),
			'last_purge'       => (int) get_option('wpxcache_last_purge', 0),
		];
	}
}

if (! function_exists('wpxcache_purge_url')) {
	/**
	 * Purge a single URL from WP XCache generated cache files.
	 */
	function wpxcache_purge_url(string $url): bool {
		$url = esc_url_raw($url);

		if ('' === $url) {
			return false;
		}

		return (new WPXCache\Cache\CachePurger())->purge_url($url);
	}
}

if (! function_exists('wpxcache_purge_post')) {
	/**
	 * Purge a post and its related public archive URLs.
	 */
	function wpxcache_purge_post(int $post_id): bool {
		return $post_id > 0 && (new WPXCache\Cache\CachePurger())->purge_post($post_id);
	}
}
