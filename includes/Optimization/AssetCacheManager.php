<?php
/**
 * Optimized asset cache statistics and cleanup.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Logger\Logger;
use WPXCache\Security\FileGuard;
use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class AssetCacheManager {
	private string $asset_dir;
	private PathValidator $path_validator;
	private FileGuard $file_guard;
	private Logger $logger;

	public function __construct(?PathValidator $path_validator = null, ?FileGuard $file_guard = null, ?Logger $logger = null) {
		$this->asset_dir = wp_normalize_path(WPXCACHE_CACHE_DIR . '/assets');
		$this->path_validator = $path_validator ?: new PathValidator();
		$this->file_guard = $file_guard ?: new FileGuard();
		$this->logger = $logger ?: new Logger();
	}

	/**
	 * @return array{exists: bool, writable: bool, count: int, css_count: int, js_count: int, size_bytes: int, size: string}
	 */
	public function stats(): array {
		$count = 0;
		$css_count = 0;
		$js_count = 0;
		$size = 0;

		if (is_dir($this->asset_dir) && $this->is_safe_asset_path($this->asset_dir)) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->asset_dir, \FilesystemIterator::SKIP_DOTS)
			);

			foreach ($iterator as $file) {
				$path = $file->getPathname();

				if ($file->isLink() || ! $file->isFile() || ! $this->is_safe_asset_path($path)) {
					continue;
				}

				$extension = strtolower($file->getExtension());

				if (! in_array($extension, ['css', 'js'], true)) {
					continue;
				}

				++$count;
				$size += (int) $file->getSize();

				if ('css' === $extension) {
					++$css_count;
				}

				if ('js' === $extension) {
					++$js_count;
				}
			}
		}

		return [
			'exists'     => is_dir($this->asset_dir),
			'writable'   => is_dir($this->asset_dir) && wp_is_writable($this->asset_dir),
			'count'      => $count,
			'css_count'  => $css_count,
			'js_count'   => $js_count,
			'size_bytes' => $size,
			'size'       => size_format($size),
		];
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function clear(): array {
		if (! is_dir($this->asset_dir)) {
			return $this->result(true, __('Optimized asset cache is already empty.', 'wpxcache'));
		}

		if (! $this->is_safe_asset_path($this->asset_dir)) {
			$this->logger->error('Optimized asset cache clear blocked because directory is unsafe.', ['path' => $this->asset_dir]);
			return $this->result(false, __('Optimized asset cache directory is unsafe.', 'wpxcache'));
		}

		$ok = true;
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->asset_dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($iterator as $file) {
			$path = $file->getPathname();

			if ($file->isLink() || ! $this->is_safe_asset_path($path)) {
				$ok = false;
				continue;
			}

			if ($file->isDir()) {
				$ok = $this->file_guard->remove_cache_directory($path) && $ok;
				continue;
			}

			$ok = $this->file_guard->delete_cache_file($path) && $ok;
		}

		$this->file_guard->ensure_directory($this->asset_dir);
		$this->logger->info('Optimized asset cache cleared.', ['success' => $ok]);

		return $this->result(
			$ok,
			$ok ? __('Optimized asset cache cleared.', 'wpxcache') : __('Some optimized asset files could not be cleared.', 'wpxcache')
		);
	}

	private function is_safe_asset_path(string $path): bool {
		return $this->path_validator->is_safe_cache_path($path) && $this->path_validator->is_within($path, $this->asset_dir);
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
