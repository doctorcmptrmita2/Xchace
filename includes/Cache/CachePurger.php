<?php
/**
 * Cache purger.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Logger\Logger;
use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class CachePurger {
	private Logger $logger;
	private CacheKey $cache_key;
	private CacheStorage $storage;

	public function __construct(private ?PathValidator $path_validator = null, ?Logger $logger = null, ?CacheKey $cache_key = null, ?CacheStorage $storage = null) {
		$this->path_validator = $path_validator ?: new PathValidator();
		$this->logger = $logger ?: new Logger();
		$this->cache_key = $cache_key ?: new CacheKey();
		$this->storage = $storage ?: new CacheStorage();
	}

	public function purge_all(): bool {
		if (! is_dir(WPXCACHE_CACHE_DIR) || ! $this->path_validator->is_safe_cache_path(WPXCACHE_CACHE_DIR)) {
			$this->logger->error('Full cache purge blocked because cache directory is unsafe or missing.', ['path' => WPXCACHE_CACHE_DIR]);
			return false;
		}

		/**
		 * Fires before cache purge.
		 *
		 * @param array<int, string> $urls Purged URLs. Empty for full purge.
		 */
		do_action('wpxcache_before_purge', []);

		$result = $this->delete_children(WPXCACHE_CACHE_DIR);

		update_option('wpxcache_last_purge', time(), false);
		$this->logger->info('Full cache purge completed.', ['success' => $result]);

		/**
		 * Fires after cache purge.
		 *
		 * @param array<int, string> $urls Purged URLs. Empty for full purge.
		 */
		do_action('wpxcache_after_purge', []);

		return $result;
	}

	/**
	 * @param array<int, string> $urls
	 */
	public function purge_urls(array $urls): bool {
		$urls = array_values(array_unique(array_filter(array_map('esc_url_raw', $urls))));

		if ([] === $urls) {
			return true;
		}

		do_action('wpxcache_before_purge', $urls);

		$ok = true;

		foreach ($urls as $url) {
			$ok = $this->purge_url_path($url) && $ok;
		}

		update_option('wpxcache_last_purge', time(), false);
		$this->logger->info('URL cache purge completed.', ['count' => count($urls), 'success' => $ok]);
		do_action('wpxcache_after_purge', $urls);

		return $ok;
	}

	public function purge_url(string $url): bool {
		return $this->purge_urls([$url]);
	}

	public function purge_post(int $post_id): bool {
		$urls = $this->post_related_urls($post_id);

		return [] === $urls ? true : $this->purge_urls($urls);
	}

	/**
	 * @return array<int, string>
	 */
	private function post_related_urls(int $post_id): array {
		if ($post_id <= 0) {
			return [];
		}

		$urls = [];
		$permalink = get_permalink($post_id);

		if (is_string($permalink)) {
			$urls[] = $permalink;
		}

		$urls[] = home_url('/');

		$terms = get_the_terms($post_id, 'category');

		if (is_array($terms)) {
			foreach ($terms as $term) {
				$url = get_term_link($term);

				if (is_string($url)) {
					$urls[] = $url;
				}
			}
		}

		$tags = get_the_terms($post_id, 'post_tag');

		if (is_array($tags)) {
			foreach ($tags as $term) {
				$url = get_term_link($term);

				if (is_string($url)) {
					$urls[] = $url;
				}
			}
		}

		$author_id = (int) get_post_field('post_author', $post_id);

		if ($author_id > 0) {
			$urls[] = get_author_posts_url($author_id);
		}

		return array_values(array_unique(array_filter($urls)));
	}

	private function purge_url_path(string $url): bool {
		$request = RequestContext::from_url($url);

		if (null === $request) {
			$this->logger->warning('Skipped invalid purge URL.', ['url' => $url]);
			return false;
		}

		$file = $this->storage->path_for_request($request, $this->cache_key);
		$directory = dirname($file);

		if (! $this->path_validator->is_safe_cache_path($directory)) {
			$this->logger->error('Blocked URL purge outside cache directory.', ['url' => $url, 'path' => $directory]);
			return false;
		}

		if (! is_dir($directory)) {
			return true;
		}

		$deleted = $this->delete_children($directory);
		$removed = @rmdir($directory);

		$this->logger->info('Single URL purge completed.', ['url' => $url, 'success' => $deleted && $removed]);

		return $deleted && $removed;
	}

	private function delete_children(string $directory): bool {
		if (! $this->path_validator->is_safe_cache_path($directory)) {
			return false;
		}

		$ok = true;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $file) {
			$path = $file->getPathname();

			if ($file->isLink() || ! $this->path_validator->is_safe_cache_path($path)) {
				$ok = false;
				continue;
			}

			if ('index.php' === $file->getFilename()) {
				continue;
			}

			if ($file->isDir()) {
				$ok = @rmdir($path) && $ok;
				continue;
			}

			$ok = @unlink($path) && $ok;
		}

		return $ok;
	}
}
