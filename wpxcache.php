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
