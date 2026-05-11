<?php
/**
 * Applies conservative smart profile settings.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Profile;

use WPXCache\Cache\AdvancedCacheInstaller;
use WPXCache\Core\Config;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class SafeSettingsApplier {
	public function __construct(private ?Logger $logger = null) {
		$this->logger = $logger ?: new Logger();
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function apply(string $profile): array {
		$settings = Config::settings();

		$settings['cache']['enabled'] = true;
		$settings['cache']['cache_logged_in_users'] = false;
		$settings['cache']['cache_rest_api'] = false;
		$settings['cache']['cache_search'] = false;
		$settings['cache']['cache_404'] = false;
		$settings['cache']['cache_feeds'] = false;
		$settings['cache']['purge_home_on_update'] = true;
		$settings['cache']['purge_archives_on_update'] = true;

		$settings['optimization']['safe_mode'] = true;
		$settings['optimization']['combine_css'] = false;
		$settings['optimization']['defer_css'] = false;
		$settings['optimization']['defer_js'] = false;
		$settings['optimization']['delay_js'] = false;

		$settings['woocommerce']['safe_mode'] = true;
		$settings['woocommerce']['stock_update_purge'] = true;
		$settings['woocommerce']['price_update_purge'] = true;

		$settings['preload']['preload_homepage'] = true;
		$settings['preload']['batch_size'] = 3;
		$settings['preload']['delay'] = 10;

		if ('woocommerce' === $profile || class_exists('WooCommerce')) {
			$settings['cache']['ttl'] = 1800;
			$settings['preload']['preload_products'] = false;
		} elseif ('news' === $profile) {
			$settings['cache']['ttl'] = 900;
			$settings['preload']['preload_posts'] = true;
		} elseif ('membership' === $profile) {
			$settings['cache']['ttl'] = 1800;
			$settings['cache']['never_cache_urls'][] = '/members';
			$settings['cache']['never_cache_urls'][] = '/account';
		} else {
			$settings['cache']['ttl'] = 3600;
		}

		$settings['cache']['never_cache_urls'] = array_values(array_unique(array_filter($settings['cache']['never_cache_urls'])));
		update_option(Config::OPTION_NAME, $settings, false);
		(new AdvancedCacheInstaller())->write_config();

		$this->logger->info('Smart Optimize safe profile applied.', ['profile' => $profile]);

		return [
			'success' => true,
			'message' => __('Smart Optimize applied safe settings. Risky optimizations were left disabled.', 'wpxcache'),
		];
	}
}
