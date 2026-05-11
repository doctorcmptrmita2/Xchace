<?php
/**
 * Admin menu registration.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Core\ServiceProvider;
use WPXCache\Security\Capability;

if (! defined('ABSPATH')) {
	exit;
}

final class AdminMenu implements ServiceProvider {
	public function register(): void {
		add_action('admin_menu', [$this, 'add_menu']);
	}

	public function add_menu(): void {
		add_menu_page(
			__('WP XCache', 'wpxcache'),
			__('WP XCache', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache',
			[$this, 'render_dashboard'],
			'dashicons-performance',
			58
		);

		add_submenu_page(
			'wpxcache',
			__('Dashboard', 'wpxcache'),
			__('Dashboard', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache',
			[$this, 'render_dashboard']
		);

		add_submenu_page(
			'wpxcache',
			__('Cache', 'wpxcache'),
			__('Cache', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-cache',
			[$this, 'render_cache']
		);

		add_submenu_page(
			'wpxcache',
			__('WooCommerce', 'wpxcache'),
			__('WooCommerce', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-woocommerce',
			[$this, 'render_woocommerce']
		);

		add_submenu_page(
			'wpxcache',
			__('Preload', 'wpxcache'),
			__('Preload', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-preload',
			[$this, 'render_preload']
		);

		add_submenu_page(
			'wpxcache',
			__('File Optimization', 'wpxcache'),
			__('File Optimization', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-optimization',
			[$this, 'render_optimization']
		);

		add_submenu_page(
			'wpxcache',
			__('Media', 'wpxcache'),
			__('Media', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-media',
			[$this, 'render_media']
		);

		add_submenu_page(
			'wpxcache',
			__('Tools', 'wpxcache'),
			__('Tools', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-tools',
			[$this, 'render_tools']
		);

		add_submenu_page(
			'wpxcache',
			__('Advanced Rules', 'wpxcache'),
			__('Advanced Rules', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-advanced-rules',
			[$this, 'render_advanced_rules']
		);

		add_submenu_page(
			'wpxcache',
			__('CDN', 'wpxcache'),
			__('CDN', 'wpxcache'),
			Capability::MANAGE,
			'wpxcache-cdn',
			[$this, 'render_cdn']
		);
	}

	public function render_dashboard(): void {
		Capability::require_manage();

		(new DashboardPage())->render();
	}

	public function render_cache(): void {
		Capability::require_manage();

		(new CachePage())->render();
	}

	public function render_woocommerce(): void {
		Capability::require_manage();

		(new WooCommercePage())->render();
	}

	public function render_preload(): void {
		Capability::require_manage();

		(new PreloadPage())->render();
	}

	public function render_optimization(): void {
		Capability::require_manage();

		(new OptimizationPage())->render();
	}

	public function render_media(): void {
		Capability::require_manage();

		(new MediaPage())->render();
	}

	public function render_tools(): void {
		Capability::require_manage();

		(new ToolsPage())->render();
	}

	public function render_advanced_rules(): void {
		Capability::require_manage();

		(new AdvancedRulesPage())->render();
	}

	public function render_cdn(): void {
		Capability::require_manage();

		(new CdnPage())->render();
	}
}
