<?php
/**
 * Central risk metadata for admin settings.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

if (! defined('ABSPATH')) {
	exit;
}

final class RiskRegistry {
	public const SAFE = 'safe';
	public const MEDIUM = 'medium';
	public const RISKY = 'risky';

	/**
	 * @param array<string, mixed> $values
	 * @return array<string, array{key: string, label: string, level: string, risk_label: string, risk_class: string, message: string, enabled: bool}>
	 */
	public static function items(string $group, array $values = []): array {
		$items = [];

		foreach (self::definitions($group) as $key => $definition) {
			$level = is_string($definition['level'] ?? null) ? $definition['level'] : self::SAFE;
			$value_key = 'cache' === $group && 'cache_enabled' === $key ? 'enabled' : $key;
			$items[$key] = [
				'key'        => $key,
				'label'      => is_string($definition['label'] ?? null) ? $definition['label'] : $key,
				'level'      => $level,
				'risk_label' => self::label($level),
				'risk_class' => 'is-' . $level,
				'message'    => is_string($definition['message'] ?? null) ? $definition['message'] : self::default_message($level),
				'enabled'    => ! empty($values[$value_key]),
			];
		}

		return $items;
	}

	public static function label(string $level): string {
		return match ($level) {
			self::RISKY  => __('Risky', 'wpxcache'),
			self::MEDIUM => __('Medium', 'wpxcache'),
			default      => __('Safe', 'wpxcache'),
		};
	}

	public static function default_message(string $level): string {
		return match ($level) {
			self::RISKY  => __('This option can break dynamic pages or user-specific experiences. Enable only after testing.', 'wpxcache'),
			self::MEDIUM => __('This option is usually safe, but test forms, layout and dynamic pages after enabling it.', 'wpxcache'),
			default      => __('This option is conservative and suitable for safe default configurations.', 'wpxcache'),
		};
	}

	/**
	 * @param array<string, mixed> $item
	 */
	public static function badge(array $item): string {
		$class = is_string($item['risk_class'] ?? null) ? $item['risk_class'] : 'is-safe';
		$label = is_string($item['risk_label'] ?? null) ? $item['risk_label'] : self::label(self::SAFE);

		return sprintf(
			'<span class="wpxcache-risk %1$s">%2$s</span>',
			esc_attr($class),
			esc_html($label)
		);
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function definitions(string $group): array {
		return match ($group) {
			'cache'        => self::cache(),
			'optimization' => self::optimization(),
			'media'        => self::media(),
			'preload'      => self::preload(),
			'woocommerce'  => self::woocommerce(),
			'cdn'          => self::cdn(),
			'advanced'     => self::advanced(),
			'database'     => self::database(),
			default        => [],
		};
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function cache(): array {
		return [
			'cache_enabled'           => [
				'label'   => __('Enable page cache', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Caches only anonymous public pages when WP XCache rules allow it.', 'wpxcache'),
			],
			'ttl'                     => [
				'label'   => __('Cache lifespan TTL', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Controls how long cached HTML remains valid before regeneration.', 'wpxcache'),
			],
			'separate_mobile_cache'   => [
				'label'   => __('Separate mobile cache', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Creates extra cache variants. Use it when the theme renders different HTML for mobile visitors.', 'wpxcache'),
			],
			'purge_home_on_update'    => [
				'label'   => __('Purge homepage on update', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Keeps the homepage fresh after content changes.', 'wpxcache'),
			],
			'purge_archives_on_update'=> [
				'label'   => __('Purge related archives', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Refreshes category, tag and archive cache after content changes.', 'wpxcache'),
			],
			'cache_logged_in_users'   => [
				'label'   => __('Cache logged-in users', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Can expose account-specific content if a site has memberships, carts, forms or dashboards.', 'wpxcache'),
			],
			'cache_404'               => [
				'label'   => __('Cache 404 pages', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Useful on high-traffic sites, but stale 404 cache can hide newly published URLs.', 'wpxcache'),
			],
			'cache_search'            => [
				'label'   => __('Cache search pages', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Search output can vary by plugin, query and user context. Test before enabling.', 'wpxcache'),
			],
			'cache_feeds'             => [
				'label'   => __('Cache feeds', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Feed cache can delay RSS updates until purge or expiry.', 'wpxcache'),
			],
			'cache_rest_api'          => [
				'label'   => __('Cache REST API', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('REST responses often contain dynamic, private or nonce-protected data. Keep disabled unless audited.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function optimization(): array {
		return [
			'safe_mode'        => [
				'label'   => __('Safe Mode', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Protects WooCommerce, forms, checkout and payment scripts from aggressive optimization.', 'wpxcache'),
			],
			'minify_html'      => [
				'label'   => __('Minify HTML', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Reduces safe whitespace and comments in final HTML output.', 'wpxcache'),
			],
			'remove_generator' => [
				'label'   => __('Remove generator meta', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Removes the WordPress generator meta tag.', 'wpxcache'),
			],
			'minify_css'       => [
				'label'   => __('Minify CSS', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('CSS optimization can affect layout if exclusions are incomplete. Runtime rewriting stays protected by default.', 'wpxcache'),
			],
			'minify_js'        => [
				'label'   => __('Minify JS', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('JavaScript optimization can affect plugin behavior. Safe Mode skips protected scripts.', 'wpxcache'),
			],
			'combine_css'      => [
				'label'   => __('Combine CSS', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Can break CSS load order on complex themes and page builders.', 'wpxcache'),
			],
			'defer_css'        => [
				'label'   => __('Defer CSS', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Can change first paint or layout without Critical CSS.', 'wpxcache'),
			],
			'defer_js'         => [
				'label'   => __('Defer JS', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Can break menus, sliders, forms, checkout or tracking scripts when exclusions are missing.', 'wpxcache'),
			],
			'delay_js'         => [
				'label'   => __('Delay JS execution', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Requires per-plugin compatibility testing before use.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function media(): array {
		return [
			'lazy_load_images'   => [
				'label'   => __('Lazy load images', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Adds native lazy loading to content images.', 'wpxcache'),
			],
			'lazy_load_iframes'  => [
				'label'   => __('Lazy load iframes', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Adds native lazy loading to iframe output.', 'wpxcache'),
			],
			'disable_emoji'      => [
				'label'   => __('Disable emoji', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Removes WordPress emoji script and style output.', 'wpxcache'),
			],
			'youtube_placeholder'=> [
				'label'   => __('YouTube placeholder', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Can improve video-heavy pages, but embeds should be tested before enabling.', 'wpxcache'),
			],
			'disable_embeds'     => [
				'label'   => __('Disable embeds', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Can affect sites that rely on oEmbed previews or embed discovery.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function preload(): array {
		return [
			'enabled'          => [
				'label'   => __('Enable preload', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Preload creates background requests. Keep batch sizes small on shared hosting.', 'wpxcache'),
			],
			'sitemap_url'      => [
				'label'   => __('Sitemap URL', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Only same-site URLs are accepted by the preload queue.', 'wpxcache'),
			],
			'preload_homepage' => [
				'label'   => __('Preload homepage', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Keeps the most important public page warm.', 'wpxcache'),
			],
			'preload_posts'    => [
				'label'   => __('Preload posts', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Adds recent posts to the warmup queue.', 'wpxcache'),
			],
			'preload_pages'    => [
				'label'   => __('Preload pages', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Adds public pages to the warmup queue.', 'wpxcache'),
			],
			'preload_products' => [
				'label'   => __('Preload products', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Product catalogs can be large. Enable after checking hosting limits.', 'wpxcache'),
			],
			'batch_size'       => [
				'label'   => __('Batch size', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Higher batches warm faster but increase server load.', 'wpxcache'),
			],
			'delay'            => [
				'label'   => __('Delay between batches', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('A delay protects slower servers during warmup.', 'wpxcache'),
			],
			'auto_after_purge' => [
				'label'   => __('Auto preload after purge', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Can create background load after frequent content updates.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function woocommerce(): array {
		return [
			'safe_mode'               => [
				'label'   => __('WooCommerce Safe Mode', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Protects cart, checkout, account pages and WooCommerce session cookies.', 'wpxcache'),
			],
			'product_cache_ttl'       => [
				'label'   => __('Product cache TTL', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Product pages are public, but stock and price purge rules should stay enabled.', 'wpxcache'),
			],
			'shop_archive_cache_ttl'  => [
				'label'   => __('Shop archive cache TTL', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Shop archives can be cached when cart and session bypass rules are active.', 'wpxcache'),
			],
			'stock_update_purge'      => [
				'label'   => __('Stock update purge', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Refreshes product cache when stock changes.', 'wpxcache'),
			],
			'price_update_purge'      => [
				'label'   => __('Price update purge', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Refreshes product cache when prices change.', 'wpxcache'),
			],
			'cart_fragment_safe_mode' => [
				'label'   => __('Mini cart compatibility mode', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Keeps mini-cart behavior protected for dynamic cart fragments.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function cdn(): array {
		return [
			'enabled'                   => [
				'label'   => __('Enable CDN rewrite', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Rewrites static asset URLs. Verify HTTPS, CORS and font loading after enabling.', 'wpxcache'),
			],
			'cloudflare_enabled'        => [
				'label'   => __('Cloudflare integration', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Requires a scoped API token and correct zone ID.', 'wpxcache'),
			],
			'purge_cloudflare_on_purge' => [
				'label'   => __('Purge Cloudflare on local purge', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Coordinates CDN purge with local cache purge to reduce stale content risk.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function advanced(): array {
		return [
			'never_cache_urls'        => [
				'label'   => __('Never cache URLs', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Protects dynamic paths from page cache.', 'wpxcache'),
			],
			'never_cache_cookies'     => [
				'label'   => __('Never cache cookies', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Bypasses cache when sensitive session cookies are present.', 'wpxcache'),
			],
			'never_cache_user_agents' => [
				'label'   => __('Never cache user agents', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Incorrect user-agent rules can reduce hit rate or skip legitimate visitors.', 'wpxcache'),
			],
			'query_string_whitelist'  => [
				'label'   => __('Query string whitelist', 'wpxcache'),
				'level'   => self::RISKY,
				'message' => __('Unsafe query keys can create cache variants for personalized or sensitive pages.', 'wpxcache'),
			],
			'custom_ttl'              => [
				'label'   => __('Custom TTL per path', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Very long TTL values can keep stale content visible after updates.', 'wpxcache'),
			],
		];
	}

	/**
	 * @return array<string, array{label: string, level: string, message: string}>
	 */
	private static function database(): array {
		return [
			'revisions'          => [
				'label'   => __('Post revisions', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Deletes old revisions permanently. Take a database backup first.', 'wpxcache'),
			],
			'auto_drafts'        => [
				'label'   => __('Auto drafts', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Removes temporary auto-draft posts.', 'wpxcache'),
			],
			'trashed_posts'      => [
				'label'   => __('Trashed posts', 'wpxcache'),
				'level'   => self::MEDIUM,
				'message' => __('Permanently deletes posts and pages already moved to trash.', 'wpxcache'),
			],
			'spam_comments'      => [
				'label'   => __('Spam comments', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Permanently removes comments already marked as spam.', 'wpxcache'),
			],
			'expired_transients' => [
				'label'   => __('Expired transients', 'wpxcache'),
				'level'   => self::SAFE,
				'message' => __('Removes expired temporary option records.', 'wpxcache'),
			],
		];
	}
}
