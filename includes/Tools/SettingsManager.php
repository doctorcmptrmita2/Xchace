<?php
/**
 * Settings import/export and maintenance tools.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Tools;

use WPXCache\Core\Config;
use WPXCache\Logger\Logger;
use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class SettingsManager {
	public function __construct(private ?Logger $logger = null, private ?PathValidator $path_validator = null) {
		$this->logger = $logger ?: new Logger();
		$this->path_validator = $path_validator ?: new PathValidator();
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function import(string $json): array {
		$decoded = json_decode($json, true);

		if (! is_array($decoded)) {
			return $this->result(false, __('Invalid settings JSON.', 'wpxcache'));
		}

		$settings = $this->sanitize_settings($decoded);
		update_option(Config::OPTION_NAME, $settings, false);
		$this->logger->info('Settings imported from Tools.');

		return $this->result(true, __('Settings imported.', 'wpxcache'));
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function reset(): array {
		update_option(Config::OPTION_NAME, Config::defaults(), false);
		$this->logger->info('Settings reset to defaults from Tools.');

		return $this->result(true, __('Settings reset to safe defaults.', 'wpxcache'));
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function clear_logs(): array {
		if (! is_dir(WPXCACHE_LOG_DIR) || ! $this->path_validator->is_safe_cache_path(WPXCACHE_LOG_DIR)) {
			return $this->result(false, __('Log directory is missing or unsafe.', 'wpxcache'));
		}

		$ok = true;
		$files = glob(WPXCACHE_LOG_DIR . '/*.log');

		if (! is_array($files)) {
			$files = [];
		}

		foreach ($files as $file) {
			if (! is_string($file) || is_link($file) || ! $this->path_validator->is_safe_cache_path($file)) {
				$ok = false;
				continue;
			}

			$ok = wp_delete_file($file) && $ok;
		}

		$this->logger->info('Logs cleared from Tools.', ['success' => $ok]);

		return $this->result($ok, $ok ? __('Logs cleared.', 'wpxcache') : __('Some log files could not be cleared.', 'wpxcache'));
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>
	 */
	private function sanitize_settings(array $input): array {
		$defaults = Config::defaults();
		$merged = array_replace_recursive($defaults, $input);

		return $this->sanitize_against_schema($merged, $defaults);
	}

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $schema
	 * @return array<string, mixed>
	 */
	private function sanitize_against_schema(array $values, array $schema): array {
		$sanitized = [];

		foreach ($schema as $key => $default) {
			$value = $values[$key] ?? $default;

			if (is_array($default)) {
				if ([] === $default || array_is_list($default)) {
					$sanitized[$key] = is_array($value) ? $this->sanitize_list($value) : [];
					continue;
				}

				$sanitized[$key] = is_array($value) ? $this->sanitize_against_schema($value, $default) : $default;
				continue;
			}

			if (is_bool($default)) {
				$sanitized[$key] = (bool) rest_sanitize_boolean($value);
				continue;
			}

			if (is_int($default)) {
				$sanitized[$key] = absint($value);
				continue;
			}

			$sanitized[$key] = sanitize_text_field(is_scalar($value) ? (string) $value : (string) $default);
		}

		return $sanitized;
	}

	/**
	 * @param array<int|string, mixed> $values
	 * @return array<int, string>
	 */
	private function sanitize_list(array $values): array {
		$sanitized = [];

		foreach ($values as $value) {
			if (is_scalar($value)) {
				$sanitized[] = sanitize_text_field((string) $value);
			}
		}

		return array_values(array_unique($sanitized));
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
