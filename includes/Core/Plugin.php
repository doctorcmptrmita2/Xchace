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
use WPXCache\Cache\PageCache;

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

		if (is_admin()) {
			(new Assets())->register();
			(new AdminMenu())->register();
		}
	}
}
