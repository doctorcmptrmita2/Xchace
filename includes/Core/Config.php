<?php
/**
 * Default configuration.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Core;

if (! defined('ABSPATH')) {
	exit;
}

final class Config {
	public const OPTION_NAME = 'wpxcache_settings';

	/**
	 * Safe default settings. Aggressive optimization stays disabled.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			'cache'        => [
				'enabled'                  => false,
				'ttl'                      => 3600,
				'separate_mobile_cache'    => false,
				'cache_logged_in_users'    => false,
				'cache_404'                => false,
				'cache_search'             => false,
				'cache_feeds'              => false,
				'cache_rest_api'           => false,
				'purge_home_on_update'     => true,
				'purge_archives_on_update' => true,
			],
			'optimization' => [
				'minify_html'        => false,
				'minify_css'         => false,
				'combine_css'        => false,
				'defer_css'          => false,
				'minify_js'          => false,
				'defer_js'           => false,
				'delay_js'           => false,
				'safe_mode'          => true,
			],
			'media'        => [
				'lazy_load_images'   => false,
				'lazy_load_iframes'  => false,
				'youtube_placeholder'=> false,
				'disable_emoji'      => false,
				'disable_embeds'     => false,
			],
			'woocommerce'  => [
				'safe_mode'               => true,
				'stock_update_purge'      => true,
				'price_update_purge'      => true,
				'cart_fragment_safe_mode' => true,
			],
			'advanced'     => [
				'debug_headers' => false,
				'debug_mode'    => false,
			],
		];
	}

	/**
	 * Read settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		$settings = get_option(self::OPTION_NAME, []);

		if (! is_array($settings)) {
			$settings = [];
		}

		return array_replace_recursive(self::defaults(), $settings);
	}
}
