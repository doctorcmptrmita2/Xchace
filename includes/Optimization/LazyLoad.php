<?php
/**
 * Conservative lazy load filters.
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

final class LazyLoad implements ServiceProvider {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null) {
		$this->settings = $settings ?: Config::settings();
	}

	public function register(): void {
		if (! empty($this->settings['media']['lazy_load_images'])) {
			add_filter('the_content', [$this, 'lazy_load_images'], 20);
			add_filter('post_thumbnail_html', [$this, 'lazy_load_images'], 20);
			add_filter('wp_get_attachment_image_attributes', [$this, 'attachment_image_attributes'], 20);
		}

		if (! empty($this->settings['media']['lazy_load_iframes'])) {
			add_filter('the_content', [$this, 'lazy_load_iframes'], 21);
		}
	}

	public function lazy_load_images(string $html): string {
		if (false === stripos($html, '<img')) {
			return $html;
		}

		return preg_replace_callback(
			'/<img\b(?![^>]*\bloading=)([^>]*)>/i',
			static function (array $matches): string {
				return '<img loading="lazy" decoding="async"' . $matches[1] . '>';
			},
			$html
		) ?: $html;
	}

	/**
	 * @param array<string, string> $attributes
	 * @return array<string, string>
	 */
	public function attachment_image_attributes(array $attributes): array {
		$attributes['loading'] = $attributes['loading'] ?? 'lazy';
		$attributes['decoding'] = $attributes['decoding'] ?? 'async';

		return $attributes;
	}

	public function lazy_load_iframes(string $html): string {
		if (false === stripos($html, '<iframe')) {
			return $html;
		}

		return preg_replace_callback(
			'/<iframe\b(?![^>]*\bloading=)([^>]*)>/i',
			static function (array $matches): string {
				return '<iframe loading="lazy"' . $matches[1] . '>';
			},
			$html
		) ?: $html;
	}
}
