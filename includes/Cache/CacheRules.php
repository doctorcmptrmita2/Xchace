<?php
/**
 * Safe page-cache eligibility rules.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Compatibility\WooCommerce;
use WPXCache\Core\Config;

if (! defined('ABSPATH')) {
	exit;
}

final class CacheRules {
	private const SENSITIVE_QUERY_KEYS = [
		'add-to-cart',
		'preview',
		's',
		'wc-ajax',
		'order',
		'token',
		'key',
		'nonce',
		'_wpnonce',
	];

	private const SENSITIVE_COOKIE_PREFIXES = [
		'wordpress_logged_in_',
		'wp-postpass_',
		'woocommerce_cart_hash',
		'woocommerce_items_in_cart',
		'wp_woocommerce_session_',
		'comment_author_',
	];

	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?WooCommerce $woocommerce = null) {
		$this->settings = $settings ?: Config::settings();
		$this->woocommerce = $woocommerce ?: new WooCommerce($this->settings);
	}

	public function should_cache(RequestContext $request): bool {
		$should_cache = true;

		if (empty($this->settings['cache']['enabled'])) {
			$should_cache = false;
		}

		if ('GET' !== $request->method()) {
			$should_cache = false;
		}

		if (defined('DONOTCACHEPAGE') && DONOTCACHEPAGE) {
			$should_cache = false;
		}

		if (is_user_logged_in() && empty($this->settings['cache']['cache_logged_in_users'])) {
			$should_cache = false;
		}

		if (is_admin() || wp_doing_ajax() || $this->is_rest_request($request) || $this->is_login_request($request)) {
			$should_cache = false;
		}

		if (is_preview() || post_password_required()) {
			$should_cache = false;
		}

		if (is_search() && empty($this->settings['cache']['cache_search'])) {
			$should_cache = false;
		}

		if (is_404() && empty($this->settings['cache']['cache_404'])) {
			$should_cache = false;
		}

		if (is_feed() && empty($this->settings['cache']['cache_feeds'])) {
			$should_cache = false;
		}

		if ($this->has_sensitive_query($request) || $this->has_bypass_cookie($request) || $this->has_no_store_header($request)) {
			$should_cache = false;
		}

		if ($this->matches_excluded_url($request) || $this->matches_excluded_user_agent($request)) {
			$should_cache = false;
		}

		if ($this->woocommerce->should_bypass_request($request)) {
			$should_cache = false;
		}

		/**
		 * Filter whether the current request should be cached.
		 *
		 * @param bool           $should_cache Cache decision.
		 * @param RequestContext $request      Request context.
		 */
		return (bool) apply_filters('wpxcache_should_cache_request', $should_cache, $request);
	}

	public function is_serveable_response(): bool {
		$status = http_response_code();

		if ($status < 200 || $status >= 300) {
			return 404 === $status && ! empty($this->settings['cache']['cache_404']);
		}

		foreach (headers_list() as $header) {
			if (0 === stripos($header, 'Cache-Control:') && preg_match('/no-store|private/i', $header)) {
				return false;
			}
		}

		return true;
	}

	public function ttl_for_request(RequestContext $request): int {
		$ttl = isset($this->settings['cache']['ttl']) ? absint($this->settings['cache']['ttl']) : 3600;
		$rules = is_array($this->settings['cache']['custom_ttl'] ?? null) ? $this->settings['cache']['custom_ttl'] : [];
		$path = trailingslashit($request->path());

		foreach ($rules as $rule) {
			if (! is_array($rule) || empty($rule['pattern']) || empty($rule['ttl'])) {
				continue;
			}

			$pattern = trailingslashit('/' . trim((string) $rule['pattern'], '/'));

			if ('//' !== $pattern && 0 === strpos($path, $pattern)) {
				$ttl = absint($rule['ttl']);
				break;
			}
		}

		return (int) apply_filters('wpxcache_cache_ttl', max(60, $ttl), $request->url());
	}

	private function is_rest_request(RequestContext $request): bool {
		if (! empty($this->settings['cache']['cache_rest_api'])) {
			return false;
		}

		return 0 === strpos($request->path(), '/wp-json/') || (defined('REST_REQUEST') && REST_REQUEST);
	}

	private function is_login_request(RequestContext $request): bool {
		$path = trim($request->path(), '/');

		return in_array($path, ['wp-login.php', 'wp-register.php'], true) || str_contains($path, 'wp-login.php');
	}

	private function has_sensitive_query(RequestContext $request): bool {
		foreach (array_keys($request->query()) as $key) {
			if (in_array($key, self::SENSITIVE_QUERY_KEYS, true)) {
				return true;
			}
		}

		return false;
	}

	private function has_bypass_cookie(RequestContext $request): bool {
		$prefixes = array_merge(
			self::SENSITIVE_COOKIE_PREFIXES,
			is_array($this->settings['cache']['never_cache_cookies'] ?? null) ? $this->settings['cache']['never_cache_cookies'] : []
		);

		foreach (array_keys($request->cookies()) as $cookie) {
			foreach ($prefixes as $prefix) {
				if (0 === strpos($cookie, $prefix)) {
					return true;
				}
			}
		}

		return false;
	}

	private function matches_excluded_url(RequestContext $request): bool {
		$patterns = apply_filters(
			'wpxcache_never_cache_urls',
			is_array($this->settings['cache']['never_cache_urls'] ?? null) ? $this->settings['cache']['never_cache_urls'] : []
		);

		if (! is_array($patterns)) {
			return false;
		}

		$path = trailingslashit($request->path());

		foreach ($patterns as $pattern) {
			if (! is_scalar($pattern)) {
				continue;
			}

			$pattern = trailingslashit('/' . trim((string) $pattern, '/'));

			if ('//' !== $pattern && 0 === strpos($path, $pattern)) {
				return true;
			}
		}

		return false;
	}

	private function matches_excluded_user_agent(RequestContext $request): bool {
		$patterns = is_array($this->settings['cache']['never_cache_user_agents'] ?? null) ? $this->settings['cache']['never_cache_user_agents'] : [];

		foreach ($patterns as $pattern) {
			if (is_scalar($pattern) && '' !== (string) $pattern && false !== stripos($request->user_agent(), (string) $pattern)) {
				return true;
			}
		}

		return false;
	}

	private function has_no_store_header(RequestContext $request): bool {
		$headers = $request->headers();

		return isset($headers['cache-control']) && preg_match('/no-store|no-cache/i', $headers['cache-control']);
	}

}
