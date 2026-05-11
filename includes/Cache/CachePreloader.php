<?php
/**
 * Safe cache preload queue.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class CachePreloader implements ServiceProvider {
	public const STATE_OPTION = 'wpxcache_preload_state';
	public const CRON_HOOK    = 'wpxcache_preload_batch';

	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		add_action(self::CRON_HOOK, [$this, 'process_batch']);
		add_action('wpxcache_after_purge', [$this, 'maybe_start_after_purge']);
	}

	public function maybe_start_after_purge(): void {
		if (empty($this->settings['preload']['auto_after_purge'])) {
			return;
		}

		$urls = $this->build_urls();

		if ([] === $urls) {
			return;
		}

		$this->start($urls);
	}

	/**
	 * @param array<int, string> $urls
	 */
	public function start(array $urls): array {
		$urls = $this->normalize_urls($urls);

		if ([] === $urls) {
			return $this->result(false, __('No valid preload URLs were found.', 'wpxcache'));
		}

		$state = [
			'status'     => 'running',
			'queued'     => array_values($urls),
			'processed'  => [],
			'failed'     => [],
			'total'      => count($urls),
			'started_at' => time(),
			'updated_at' => time(),
		];

		update_option(self::STATE_OPTION, $state, false);
		$this->schedule_next_batch(1);
		$this->logger->info('Preload started.', ['total' => count($urls)]);

		return $this->result(true, __('Preload started safely in the background.', 'wpxcache'));
	}

	public function pause(): array {
		$state = $this->state();
		$state['status'] = 'paused';
		$state['updated_at'] = time();
		update_option(self::STATE_OPTION, $state, false);
		$this->logger->info('Preload paused.');

		return $this->result(true, __('Preload paused.', 'wpxcache'));
	}

	public function resume(): array {
		$state = $this->state();

		if ([] === ($state['queued'] ?? [])) {
			return $this->result(false, __('There are no queued preload URLs to resume.', 'wpxcache'));
		}

		$state['status'] = 'running';
		$state['updated_at'] = time();
		update_option(self::STATE_OPTION, $state, false);
		$this->schedule_next_batch(1);
		$this->logger->info('Preload resumed.');

		return $this->result(true, __('Preload resumed.', 'wpxcache'));
	}

	public function reset(): array {
		delete_option(self::STATE_OPTION);
		wp_clear_scheduled_hook(self::CRON_HOOK);
		$this->logger->info('Preload state reset.');

		return $this->result(true, __('Preload state reset.', 'wpxcache'));
	}

	public function process_batch(): void {
		$state = $this->state();

		if (($state['status'] ?? '') !== 'running') {
			return;
		}

		$queued = is_array($state['queued'] ?? null) ? $state['queued'] : [];

		if ([] === $queued) {
			$state['status'] = 'completed';
			$state['updated_at'] = time();
			update_option(self::STATE_OPTION, $state, false);
			update_option('wpxcache_last_preload', time(), false);
			$this->logger->info('Preload completed.', ['processed' => count($state['processed'] ?? [])]);
			return;
		}

		$batch_size = $this->setting_int('batch_size', 3, 1, 10);
		$batch = array_splice($queued, 0, $batch_size);
		$processed = is_array($state['processed'] ?? null) ? $state['processed'] : [];
		$failed = is_array($state['failed'] ?? null) ? $state['failed'] : [];

		foreach ($batch as $url) {
			$response = wp_remote_get(
				$url,
				[
					'timeout'     => 8,
					'redirection' => 2,
					'blocking'    => true,
					'user-agent'  => 'WP XCache Pro Preloader/' . WPXCACHE_VERSION,
					'headers'     => [
						'X-WPXCache-Preload' => '1',
					],
				]
			);

			if (is_wp_error($response)) {
				$failed[] = $url;
				$this->logger->warning('Preload URL failed.', ['url' => $url, 'error' => $response->get_error_message()]);
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code($response);

			if ($code >= 200 && $code < 400) {
				$processed[] = $url;
				continue;
			}

			$failed[] = $url;
			$this->logger->warning('Preload URL returned unexpected status.', ['url' => $url, 'status' => $code]);
		}

		$state['queued'] = array_values($queued);
		$state['processed'] = array_values(array_unique($processed));
		$state['failed'] = array_values(array_unique($failed));
		$state['updated_at'] = time();

		if ([] === $queued) {
			$state['status'] = 'completed';
			update_option('wpxcache_last_preload', time(), false);
			$this->logger->info('Preload completed.', ['processed' => count($state['processed']), 'failed' => count($state['failed'])]);
		}

		update_option(self::STATE_OPTION, $state, false);

		if ('running' === ($state['status'] ?? '')) {
			$this->schedule_next_batch($this->setting_int('delay', 10, 1, 120));
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function state(): array {
		$state = get_option(self::STATE_OPTION, []);

		if (! is_array($state)) {
			return $this->empty_state();
		}

		return array_replace($this->empty_state(), $state);
	}

	/**
	 * @param array<int, string> $manual_urls
	 * @return array<int, string>
	 */
	public function build_urls(array $manual_urls = []): array {
		$urls = [];

		if (! empty($this->settings['preload']['preload_homepage'])) {
			$urls[] = home_url('/');
		}

		$urls = array_merge($urls, $manual_urls);

		if (! empty($this->settings['preload']['preload_posts'])) {
			$urls = array_merge($urls, $this->recent_post_urls('post'));
		}

		if (! empty($this->settings['preload']['preload_pages'])) {
			$urls = array_merge($urls, $this->recent_post_urls('page'));
		}

		$sitemap_url = isset($this->settings['preload']['sitemap_url']) ? esc_url_raw((string) $this->settings['preload']['sitemap_url']) : '';

		if ('' !== $sitemap_url) {
			$urls = array_merge($urls, $this->sitemap_urls($sitemap_url));
		}

		return $this->normalize_urls($urls);
	}

	/**
	 * @param array<int, string> $urls
	 * @return array<int, string>
	 */
	private function normalize_urls(array $urls): array {
		$home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		$limit = $this->setting_int('url_limit', 100, 1, 1000);
		$normalized = [];

		foreach ($urls as $url) {
			$url = esc_url_raw(trim((string) $url));

			if ('' === $url || ! wp_http_validate_url($url)) {
				continue;
			}

			$host = wp_parse_url($url, PHP_URL_HOST);

			if (! is_string($host) || strtolower($host) !== strtolower((string) $home_host)) {
				continue;
			}

			$normalized[] = $url;
		}

		return array_slice(array_values(array_unique($normalized)), 0, $limit);
	}

	/**
	 * @return array<int, string>
	 */
	private function recent_post_urls(string $post_type): array {
		$posts = get_posts(
			[
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 20,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		$urls = [];

		foreach ($posts as $post) {
			$url = get_permalink($post);

			if (is_string($url)) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * @return array<int, string>
	 */
	private function sitemap_urls(string $sitemap_url): array {
		$response = wp_remote_get(
			$sitemap_url,
			[
				'timeout'     => 8,
				'redirection' => 2,
				'user-agent'  => 'WP XCache Pro Sitemap Reader/' . WPXCACHE_VERSION,
			]
		);

		if (is_wp_error($response)) {
			$this->logger->warning('Sitemap preload fetch failed.', ['url' => $sitemap_url, 'error' => $response->get_error_message()]);
			return [];
		}

		$body = wp_remote_retrieve_body($response);

		if ('' === $body) {
			return [];
		}

		$urls = [];

		if (preg_match_all('/<loc>\s*([^<]+)\s*<\/loc>/i', $body, $matches)) {
			foreach ($matches[1] as $url) {
				$urls[] = html_entity_decode(trim($url), ENT_QUOTES, 'UTF-8');
			}
		}

		return $urls;
	}

	private function schedule_next_batch(int $delay): void {
		if (! wp_next_scheduled(self::CRON_HOOK)) {
			wp_schedule_single_event(time() + max(1, $delay), self::CRON_HOOK);
		}
	}

	private function setting_int(string $key, int $default, int $min, int $max): int {
		$value = $this->settings['preload'][$key] ?? $default;
		$value = absint($value);

		return max($min, min($max, $value));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function empty_state(): array {
		return [
			'status'     => 'idle',
			'queued'     => [],
			'processed'  => [],
			'failed'     => [],
			'total'      => 0,
			'started_at' => 0,
			'updated_at' => 0,
		];
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	private function result(bool $success, string $message): array {
		return [
			'success' => $success,
			'message' => $message,
		];
	}
}
