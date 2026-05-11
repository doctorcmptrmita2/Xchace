<?php
/**
 * Main plugin coordinator.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

use WPXCache\Admin\AdminMenu;
use WPXCache\Admin\Assets;
use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Cache\PageCache;
use WPXCache\Cache\CachePreloader;
use WPXCache\Compatibility\WooCommerce;

if (! defined('ABSPATH')) {
	exit;
}

final class Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		(new PageCache())->register();
		(new CachePreloader())->register();
		(new WooCommerce())->register();
		add_action('update_option_' . Config::OPTION_NAME, [$this, 'sync_dropin_config'], 10, 0);

		if (is_admin()) {
			(new Assets())->register();
			(new AdminMenu())->register();
		}
	}

	public function sync_dropin_config(): void {
		(new AdvancedCacheInstaller())->write_config();
	}
}
