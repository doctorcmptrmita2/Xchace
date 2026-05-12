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
use WPXCache\Cdn\CdnManager;
use WPXCache\Cli\WpCliCommands;
use WPXCache\Compatibility\WooCommerce;
use WPXCache\Optimization\CssOptimizer;
use WPXCache\Optimization\HtmlMinifier;
use WPXCache\Optimization\JsOptimizer;
use WPXCache\Optimization\LazyLoad;
use WPXCache\Optimization\WordPressCleanup;
use WPXCache\Purge\SmartPurge;
use WPXCache\Rest\RestController;

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
		(new HtmlMinifier())->register();
		(new CssOptimizer())->register();
		(new JsOptimizer())->register();
		(new LazyLoad())->register();
		(new WordPressCleanup())->register();
		(new SmartPurge())->register();
		(new RestController())->register();
		(new CdnManager())->register();
		add_action('update_option_' . Config::OPTION_NAME, [$this, 'sync_dropin_config'], 10, 0);

		if (defined('WP_CLI') && WP_CLI) {
			(new WpCliCommands())->register();
		}

		if (is_admin()) {
			(new Assets())->register();
			(new AdminMenu())->register();
			add_action('admin_init', [$this, 'maybe_redirect_setup_wizard']);
		}
	}

	public function maybe_redirect_setup_wizard(): void {
		if (! current_user_can('manage_options') || wp_doing_ajax()) {
			return;
		}

		if (! get_option('wpxcache_setup_redirect', false)) {
			return;
		}

		delete_option('wpxcache_setup_redirect');

		if ((bool) get_option('wpxcache_setup_wizard_completed', false)) {
			return;
		}

		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$is_bulk_activation = is_string(filter_input(INPUT_GET, 'activate-multi', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

		if ('wpxcache-setup' === $page || $is_bulk_activation) {
			return;
		}

		wp_safe_redirect(admin_url('admin.php?page=wpxcache-setup'));
		exit;
	}

	public function sync_dropin_config(): void {
		(new AdvancedCacheInstaller())->write_config();
	}
}
