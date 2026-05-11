<?php
/**
 * Safe WordPress frontend cleanup.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;

if (! defined('ABSPATH')) {
	exit;
}

final class WordPressCleanup implements ServiceProvider {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null) {
		$this->settings = $settings ?: Config::settings();
	}

	public function register(): void {
		add_action('init', [$this, 'apply']);
	}

	public function apply(): void {
		if (! is_admin() && ! empty($this->settings['media']['disable_emoji'])) {
			remove_action('wp_head', 'print_emoji_detection_script', 7);
			remove_action('wp_print_styles', 'print_emoji_styles');
			remove_action('admin_print_scripts', 'print_emoji_detection_script');
			remove_action('admin_print_styles', 'print_emoji_styles');
			remove_filter('the_content_feed', 'wp_staticize_emoji');
			remove_filter('comment_text_rss', 'wp_staticize_emoji');
			remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
		}

		if (! is_admin() && ! empty($this->settings['media']['disable_embeds'])) {
			remove_action('wp_head', 'wp_oembed_add_discovery_links');
			remove_action('wp_head', 'wp_oembed_add_host_js');
			remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
		}

		if (! is_admin() && ! empty($this->settings['optimization']['remove_generator'])) {
			remove_action('wp_head', 'wp_generator');
		}
	}
}
