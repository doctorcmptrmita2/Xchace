<?php
/**
 * Media optimization admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Core\Config;
use WPXCache\Security\Capability;

if (! defined('ABSPATH')) {
	exit;
}

final class MediaPage {
	public function render(): void {
		Capability::require_manage();

		$settings = Config::settings();

		require WPXCACHE_PATH . 'templates/admin/media.php';
	}
}
