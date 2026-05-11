<?php
/**
 * Smart site profile detection.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Profile;

if (! defined('ABSPATH')) {
	exit;
}

final class ProfileEngine {
	/**
	 * @return array{id: string, label: string, confidence: int, signals: array<int, string>}
	 */
	public function detect(): array {
		$signals = [];
		$scores = [
			'blog'        => 10,
			'business'    => 10,
			'woocommerce' => 0,
			'news'        => 0,
			'membership'  => 0,
			'agency'      => 0,
			'developer'   => 0,
		];

		if (class_exists('WooCommerce') || function_exists('WC')) {
			$scores['woocommerce'] += 80;
			$signals[] = __('WooCommerce detected', 'wpxcache');
		}

		if ($this->plugin_active(['memberpress/memberpress.php', 'paid-memberships-pro/paid-memberships-pro.php', 'learndash/learndash.php', 'buddypress/bp-loader.php'])) {
			$scores['membership'] += 70;
			$signals[] = __('Membership or learning plugin detected', 'wpxcache');
		}

		if ($this->plugin_active(['elementor/elementor.php', 'elementor-pro/elementor-pro.php', 'js_composer/js_composer.php', 'beaver-builder-lite-version/fl-builder.php'])) {
			$scores['business'] += 20;
			$signals[] = __('Page builder detected', 'wpxcache');
		}

		$post_count = (int) wp_count_posts('post')->publish;
		$page_count = (int) wp_count_posts('page')->publish;

		if ($post_count >= 100) {
			$scores['news'] += 35;
			$signals[] = __('High post count detected', 'wpxcache');
		}

		if ($post_count > $page_count * 3) {
			$scores['blog'] += 25;
			$signals[] = __('Post-heavy site structure detected', 'wpxcache');
		}

		if ($page_count >= $post_count && $page_count >= 5) {
			$scores['business'] += 25;
			$signals[] = __('Page-heavy site structure detected', 'wpxcache');
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			$scores['developer'] += 30;
			$signals[] = __('WP_DEBUG is enabled', 'wpxcache');
		}

		arsort($scores);
		$profile = (string) array_key_first($scores);
		$score = (int) reset($scores);

		return [
			'id'         => $profile,
			'label'      => $this->label($profile),
			'confidence' => max(10, min(95, $score)),
			'signals'    => array_values(array_unique($signals)),
		];
	}

	private function label(string $profile): string {
		$labels = [
			'blog'        => __('Blog', 'wpxcache'),
			'business'    => __('Business site', 'wpxcache'),
			'woocommerce' => __('WooCommerce store', 'wpxcache'),
			'news'        => __('News site', 'wpxcache'),
			'membership'  => __('Membership site', 'wpxcache'),
			'agency'      => __('Agency/client site', 'wpxcache'),
			'developer'   => __('Developer/manual mode', 'wpxcache'),
		];

		return $labels[$profile] ?? $labels['business'];
	}

	/**
	 * @param array<int, string> $plugins
	 */
	private function plugin_active(array $plugins): bool {
		if (! function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ($plugins as $plugin) {
			if (is_plugin_active($plugin)) {
				return true;
			}
		}

		return false;
	}
}
