<?php
/**
 * advanced-cache.php drop-in installer.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\Config;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class AdvancedCacheInstaller {
	public const SIGNATURE = 'WP XCache Pro advanced-cache.php drop-in';

	private string $dropin_path;
	private string $source_path;
	private string $config_path;
	private Logger $logger;

	public function __construct(?Logger $logger = null) {
		$this->dropin_path = WP_CONTENT_DIR . '/advanced-cache.php';
		$this->source_path = WPXCACHE_PATH . 'dropins/advanced-cache.php';
		$this->config_path = WPXCACHE_CACHE_DIR . '/dropin-config.php';
		$this->logger = $logger ?: new Logger();
	}

	public function install(): array {
		$status = $this->status();

		if (! is_readable($this->source_path)) {
			$this->logger->error('Drop-in source file is missing.', ['path' => $this->source_path]);
			return $this->result(false, __('Drop-in source file is missing.', 'wpxcache'));
		}

		if ($status['exists'] && ! $status['owned']) {
			$this->logger->warning('Skipped drop-in install because another advanced-cache.php exists.', ['path' => $this->dropin_path]);
			return $this->result(false, __('Another advanced-cache.php drop-in is already installed. WP XCache Pro will not overwrite it.', 'wpxcache'));
		}

		if (! wp_mkdir_p(WPXCACHE_CACHE_DIR)) {
			$this->logger->error('Cache directory could not be created.', ['path' => WPXCACHE_CACHE_DIR]);
			return $this->result(false, __('Cache directory could not be created.', 'wpxcache'));
		}

		if ($status['exists'] && ! $this->backup_existing()) {
			$this->logger->error('Existing drop-in could not be backed up.', ['path' => $this->dropin_path]);
			return $this->result(false, __('Existing drop-in could not be backed up.', 'wpxcache'));
		}

		$source = file_get_contents($this->source_path);

		if (! is_string($source) || '' === $source) {
			$this->logger->error('Drop-in source file could not be read.', ['path' => $this->source_path]);
			return $this->result(false, __('Drop-in source file could not be read.', 'wpxcache'));
		}

		if (false === file_put_contents($this->dropin_path, $source, LOCK_EX)) {
			$this->logger->error('advanced-cache.php could not be written.', ['path' => $this->dropin_path]);
			return $this->result(false, __('advanced-cache.php could not be written.', 'wpxcache'));
		}

		$this->write_config();
		$this->logger->info('advanced-cache.php installed.', ['path' => $this->dropin_path]);

		return $this->result(true, __('advanced-cache.php installed successfully.', 'wpxcache'));
	}

	public function remove(): array {
		$status = $this->status();

		if (! $status['exists']) {
			$this->remove_config();
			$this->logger->info('Drop-in remove requested but no advanced-cache.php was installed.');
			return $this->result(true, __('No advanced-cache.php drop-in was installed.', 'wpxcache'));
		}

		if (! $status['owned']) {
			$this->logger->warning('Skipped drop-in removal because advanced-cache.php is not owned by WP XCache.', ['path' => $this->dropin_path]);
			return $this->result(false, __('The installed advanced-cache.php does not belong to WP XCache Pro, so it was not removed.', 'wpxcache'));
		}

		if (! wp_delete_file($this->dropin_path)) {
			$this->logger->error('advanced-cache.php could not be removed.', ['path' => $this->dropin_path]);
			return $this->result(false, __('advanced-cache.php could not be removed.', 'wpxcache'));
		}

		$this->remove_config();
		$this->logger->info('advanced-cache.php removed.', ['path' => $this->dropin_path]);

		return $this->result(true, __('advanced-cache.php removed successfully.', 'wpxcache'));
	}

	/**
	 * @return array{exists: bool, owned: bool, wp_cache: bool, path: string, config_exists: bool, writable: bool}
	 */
	public function status(): array {
		$exists = is_file($this->dropin_path);
		$owned = false;

		if ($exists && is_readable($this->dropin_path)) {
			$contents = file_get_contents($this->dropin_path, false, null, 0, 512);
			$owned = is_string($contents) && false !== strpos($contents, self::SIGNATURE);
		}

		return [
			'exists'        => $exists,
			'owned'         => $owned,
			'wp_cache'      => defined('WP_CACHE') && WP_CACHE,
			'path'          => $this->dropin_path,
			'config_exists' => is_file($this->config_path),
			'writable'      => wp_is_writable(WP_CONTENT_DIR),
		];
	}

	public function write_config(): bool {
		$settings = Config::settings();
		$config = [
			'enabled'             => ! empty($settings['cache']['enabled']),
			'cache_dir'           => WPXCACHE_CACHE_DIR,
			'never_cache_urls'    => is_array($settings['cache']['never_cache_urls'] ?? null) ? array_values($settings['cache']['never_cache_urls']) : [],
			'never_cache_cookies' => is_array($settings['cache']['never_cache_cookies'] ?? null) ? array_values($settings['cache']['never_cache_cookies']) : [],
			'never_cache_user_agents' => is_array($settings['cache']['never_cache_user_agents'] ?? null) ? array_values($settings['cache']['never_cache_user_agents']) : [],
			'query_string_whitelist'  => is_array($settings['cache']['query_string_whitelist'] ?? null) ? array_values($settings['cache']['query_string_whitelist']) : [],
		];

		if (! wp_mkdir_p(WPXCACHE_CACHE_DIR)) {
			return false;
		}

		$contents = "<?php\n// Generated by WP XCache Pro. Do not edit manually.\nreturn " . var_export($config, true) . ";\n";

		$result = false !== file_put_contents($this->config_path, $contents, LOCK_EX);

		if ($result) {
			$this->protect_cache_directory();
		}

		return $result;
	}

	private function remove_config(): void {
		if (is_file($this->config_path)) {
			wp_delete_file($this->config_path);
		}
	}

	private function backup_existing(): bool {
		if (! is_file($this->dropin_path)) {
			return true;
		}

		$backup_path = WP_CONTENT_DIR . '/advanced-cache.php.wpxcache-backup-' . gmdate('YmdHis');

		return copy($this->dropin_path, $backup_path);
	}

	private function protect_cache_directory(): void {
		$index = WPXCACHE_CACHE_DIR . '/index.php';

		if (! is_file($index)) {
			file_put_contents($index, "<?php\n// Silence is golden.\n", LOCK_EX);
		}

		$htaccess = WPXCACHE_CACHE_DIR . '/.htaccess';

		if (! is_file($htaccess)) {
			file_put_contents($htaccess, "Deny from all\n", LOCK_EX);
		}
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
