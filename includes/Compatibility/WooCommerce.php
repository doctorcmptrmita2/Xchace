<?php
/**
 * WooCommerce safety integration.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Compatibility;

use WPXCache\Cache\RequestContext;
use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class WooCommerce implements ServiceProvider {
	private const SESSION_COOKIE_PREFIXES = [
		'woocommerce_cart_hash',
		'woocommerce_items_in_cart',
		'wp_woocommerce_session_',
	];

	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		add_action('woocommerce_update_product', [$this, 'purge_product_cache'], 10, 1);
		add_action('woocommerce_product_set_stock', [$this, 'purge_stock_cache'], 10, 1);
		add_action('woocommerce_variation_set_stock', [$this, 'purge_stock_cache'], 10, 1);
		add_action('woocommerce_product_object_updated_props', [$this, 'purge_updated_props_cache'], 10, 2);
	}

	public function is_active(): bool {
		return class_exists('WooCommerce') || function_exists('WC');
	}

	public function safe_mode_enabled(): bool {
		return ! empty($this->settings['woocommerce']['safe_mode']);
	}

	public function should_bypass_request(RequestContext $request): bool {
		if (! $this->safe_mode_enabled()) {
			return false;
		}

		if ($this->is_dynamic_page() || $this->has_dynamic_query($request) || $this->has_session_cookie($request) || $this->matches_dynamic_path($request)) {
			$this->logger->debug('WooCommerce request bypassed from page cache.', ['url' => $request->url()]);
			return true;
		}

		return false;
	}

	/**
	 * @return array<int, array{label: string, status: string, message: string}>
	 */
	public function status_items(): array {
		$active = $this->is_active();
		$safe_mode = $this->safe_mode_enabled();

		return [
			[
				'label'   => __('WooCommerce detected', 'wpxcache'),
				'status'  => $active ? 'green' : 'yellow',
				'message' => $active ? __('WooCommerce is active.', 'wpxcache') : __('WooCommerce is not active on this site.', 'wpxcache'),
			],
			[
				'label'   => __('Safe Mode', 'wpxcache'),
				'status'  => $safe_mode ? 'green' : 'red',
				'message' => $safe_mode ? __('Cart, checkout and account areas are protected.', 'wpxcache') : __('WooCommerce Safe Mode is disabled.', 'wpxcache'),
			],
			[
				'label'   => __('Cart page', 'wpxcache'),
				'status'  => 'green',
				'message' => __('Cart URLs and cart cookies bypass page cache.', 'wpxcache'),
			],
			[
				'label'   => __('Checkout page', 'wpxcache'),
				'status'  => 'green',
				'message' => __('Checkout and order-received URLs bypass page cache.', 'wpxcache'),
			],
			[
				'label'   => __('My Account page', 'wpxcache'),
				'status'  => 'green',
				'message' => __('Account URLs bypass page cache because they can contain private data.', 'wpxcache'),
			],
			[
				'label'   => __('Product pages', 'wpxcache'),
				'status'  => 'green',
				'message' => __('Public product pages may be cached when no cart/session cookie is present.', 'wpxcache'),
			],
			[
				'label'   => __('Stock update purge', 'wpxcache'),
				'status'  => ! empty($this->settings['woocommerce']['stock_update_purge']) ? 'green' : 'yellow',
				'message' => ! empty($this->settings['woocommerce']['stock_update_purge']) ? __('Stock changes trigger cache purge hooks.', 'wpxcache') : __('Stock change purge is disabled.', 'wpxcache'),
			],
			[
				'label'   => __('Price update purge', 'wpxcache'),
				'status'  => ! empty($this->settings['woocommerce']['price_update_purge']) ? 'green' : 'yellow',
				'message' => ! empty($this->settings['woocommerce']['price_update_purge']) ? __('Price changes trigger cache purge hooks.', 'wpxcache') : __('Price change purge is disabled.', 'wpxcache'),
			],
		];
	}

	public function purge_product_cache(int $product_id): void {
		if ($product_id <= 0) {
			return;
		}

		$this->logger->info('WooCommerce product update purge requested.', ['product_id' => $product_id]);
		wpxcache_purge_post($product_id);
	}

	public function purge_stock_cache(mixed $product): void {
		if (empty($this->settings['woocommerce']['stock_update_purge'])) {
			return;
		}

		$product_id = $this->resolve_product_id($product);

		if ($product_id <= 0) {
			return;
		}

		$this->logger->info('WooCommerce stock update purge requested.', ['product_id' => $product_id]);
		wpxcache_purge_post($product_id);
	}

	public function purge_price_cache(mixed $product): void {
		if (empty($this->settings['woocommerce']['price_update_purge'])) {
			return;
		}

		$product_id = $this->resolve_product_id($product);

		if ($product_id <= 0) {
			return;
		}

		$this->logger->info('WooCommerce price update purge requested.', ['product_id' => $product_id]);
		wpxcache_purge_post($product_id);
	}

	/**
	 * @param array<int, string> $updated_props
	 */
	public function purge_updated_props_cache(mixed $product, array $updated_props): void {
		$stock_props = ['stock_quantity', 'stock_status', 'manage_stock'];
		$price_props = ['price', 'regular_price', 'sale_price'];

		if ([] !== array_intersect($stock_props, $updated_props)) {
			$this->purge_stock_cache($product);
			return;
		}

		if ([] !== array_intersect($price_props, $updated_props)) {
			$this->purge_price_cache($product);
		}
	}

	private function is_dynamic_page(): bool {
		return function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
	}

	private function has_dynamic_query(RequestContext $request): bool {
		$query = $request->query();

		return isset($query['add-to-cart']) || isset($query['wc-ajax']);
	}

	private function has_session_cookie(RequestContext $request): bool {
		foreach (array_keys($request->cookies()) as $cookie) {
			foreach (self::SESSION_COOKIE_PREFIXES as $prefix) {
				if (0 === strpos($cookie, $prefix)) {
					return true;
				}
			}
		}

		return false;
	}

	private function matches_dynamic_path(RequestContext $request): bool {
		$path = trim($request->path(), '/');
		$dynamic_paths = ['cart', 'checkout', 'my-account', 'order-received'];

		foreach ($dynamic_paths as $dynamic_path) {
			if ($path === $dynamic_path || str_starts_with($path, $dynamic_path . '/')) {
				return true;
			}
		}

		return false;
	}

	private function resolve_product_id(mixed $product): int {
		if (is_int($product)) {
			return $product;
		}

		if (is_object($product) && method_exists($product, 'get_id')) {
			return absint($product->get_id());
		}

		return 0;
	}
}
