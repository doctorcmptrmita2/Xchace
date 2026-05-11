<?php
/**
 * Shared optimization exclusion rules.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Core\Config;

if (! defined('ABSPATH')) {
	exit;
}

final class OptimizationRules {
	private const SAFE_MODE_PATTERNS = [
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'woocommerce',
		'wc-',
		'wc_cart',
		'wc-checkout',
		'wc-cart',
		'wc-add-to-cart',
		'cart-fragments',
		'checkout',
		'cart',
		'select2',
		'selectwoo',
		'elementor',
		'elementor-pro',
		'divi',
		'contact-form-7',
		'wpcf7',
		'wpforms',
		'gform',
		'gravityforms',
		'gravity-forms',
		'stripe',
		'paypal',
		'square',
		'authorize',
		'klarna',
		'mollie',
		'braintree',
		'razorpay',
		'recaptcha',
		'grecaptcha',
		'hcaptcha',
		'turnstile',
	];

	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null) {
		$this->settings = $settings ?: Config::settings();
	}

	public function is_excluded(string $type, string $handle, string $src): bool {
		$excluded = (bool) apply_filters('wpxcache_asset_optimization_excluded', false, $type, $handle, $src);

		if ($excluded) {
			return true;
		}

		$haystack = strtolower($handle . ' ' . $src);

		foreach ($this->patterns($type) as $pattern) {
			if ('' !== $pattern && false !== strpos($haystack, strtolower($pattern))) {
				return true;
			}
		}

		return false;
	}

	public function is_safe_mode(): bool {
		return ! empty($this->settings['optimization']['safe_mode']);
	}

	public function is_woocommerce_flow(): bool {
		if (! $this->is_safe_mode()) {
			return false;
		}

		$checks = ['is_cart', 'is_checkout', 'is_account_page'];

		foreach ($checks as $check) {
			if (function_exists($check) && $check()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<int, string>
	 */
	private function patterns(string $type): array {
		$key = 'css' === $type ? 'exclude_css' : 'exclude_js';
		$user_patterns = $this->settings['optimization'][$key] ?? [];
		$user_patterns = is_array($user_patterns) ? array_values(array_filter(array_map('strval', $user_patterns))) : [];
		$patterns = $this->is_safe_mode() ? array_merge(self::SAFE_MODE_PATTERNS, $user_patterns) : $user_patterns;

		/**
		 * Filters asset optimization exclusion patterns.
		 *
		 * @param array<int, string> $patterns Exclusion patterns.
		 * @param string             $type     css or js.
		 */
		$patterns = apply_filters('wpxcache_optimization_exclude_patterns', $patterns, $type);

		return is_array($patterns) ? array_values(array_filter(array_map('strval', $patterns))) : [];
	}
}
