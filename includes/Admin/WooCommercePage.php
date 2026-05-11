<?php
/**
 * WooCommerce admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Compatibility\WooCommerce;
use WPXCache\Core\Config;
use WPXCache\Security\Capability;

if (! defined('ABSPATH')) {
	exit;
}

final class WooCommercePage {
	public function render(): void {
		Capability::require_manage();

		$settings = Config::settings();
		$woocommerce = new WooCommerce($settings);
		$items = $woocommerce->status_items();

		require WPXCACHE_PATH . 'templates/admin/woocommerce.php';
	}
}
