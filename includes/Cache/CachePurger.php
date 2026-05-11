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

	public function __construct(private ?PathValidator $path_validator = null, ?Logger $logger = null) {
		$this->path_validator = $path_validator ?: new PathValidator();
		$this->logger = $logger ?: new Logger();
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
