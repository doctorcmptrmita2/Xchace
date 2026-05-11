<?php
/**
 * CDN rewrite and purge manager.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cdn;

use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class CdnManager implements ServiceProvider {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		add_filter('wp_get_attachment_url', [$this, 'rewrite_url'], 20);
		add_filter('style_loader_src', [$this, 'rewrite_url'], 20);
		add_filter('script_loader_src', [$this, 'rewrite_url'], 20);
		add_action('wpxcache_after_purge', [$this, 'purge_after_local_purge'], 10, 1);
	}

	public function rewrite_url(string $url): string {
		if (empty($this->settings['cdn']['enabled'])) {
			return $url;
		}

		$base_url = esc_url_raw((string) ($this->settings['cdn']['base_url'] ?? ''));

		if ('' === $base_url || ! wp_http_validate_url($base_url)) {
			return $url;
		}

		$home = wp_parse_url(home_url('/'));
		$source = wp_parse_url($url);
		$cdn = wp_parse_url($base_url);

		if (! is_array($home) || ! is_array($source) || ! is_array($cdn) || empty($source['host']) || empty($home['host']) || empty($cdn['host'])) {
			return $url;
		}

		if (strtolower((string) $source['host']) !== strtolower((string) $home['host'])) {
			return $url;
		}

		$path = isset($source['path']) ? (string) $source['path'] : '';

		if (! $this->is_included_type($path) || $this->is_excluded_path($path)) {
			return $url;
		}

		$scheme = isset($cdn['scheme']) ? (string) $cdn['scheme'] : 'https';
		$host = (string) $cdn['host'];
		$port = isset($cdn['port']) ? ':' . (string) $cdn['port'] : '';
		$query = isset($source['query']) ? '?' . (string) $source['query'] : '';

		return esc_url_raw($scheme . '://' . $host . $port . $path . $query);
	}

	/**
	 * @param array<int, string> $urls
	 */
	public function purge_after_local_purge(array $urls): void {
		if (empty($this->settings['cdn']['cloudflare_enabled']) || empty($this->settings['cdn']['purge_cloudflare_on_purge'])) {
			return;
		}

		$client = new CloudflareClient(
			(string) ($this->settings['cdn']['cloudflare_api_token'] ?? ''),
			(string) ($this->settings['cdn']['cloudflare_zone_id'] ?? ''),
			$this->logger
		);

		$client->purge($urls);
	}

	private function is_included_type(string $path): bool {
		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$types = is_array($this->settings['cdn']['included_file_types'] ?? null) ? $this->settings['cdn']['included_file_types'] : [];

		return '' !== $extension && in_array($extension, array_map('strtolower', array_map('strval', $types)), true);
	}

	private function is_excluded_path(string $path): bool {
		$excluded = is_array($this->settings['cdn']['excluded_paths'] ?? null) ? $this->settings['cdn']['excluded_paths'] : [];

		foreach ($excluded as $pattern) {
			if (is_scalar($pattern) && '' !== (string) $pattern && 0 === strpos($path, (string) $pattern)) {
				return true;
			}
		}

		return false;
	}
}
