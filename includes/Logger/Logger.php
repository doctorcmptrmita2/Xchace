<?php
/**
 * Privacy-aware file logger.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Logger;

use WPXCache\Security\FileGuard;
use WPXCache\Security\PathValidator;

if (! defined('ABSPATH')) {
	exit;
}

final class Logger {
	private const LEVELS = ['error', 'warning', 'info', 'debug'];

	public function __construct(
		private ?FileGuard $file_guard = null,
		private ?PathValidator $path_validator = null
	) {
		$this->file_guard = $file_guard ?: new FileGuard();
		$this->path_validator = $path_validator ?: new PathValidator();
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function error(string $message, array $context = []): void {
		$this->log('error', $message, $context);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function warning(string $message, array $context = []): void {
		$this->log('warning', $message, $context);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function info(string $message, array $context = []): void {
		$this->log('info', $message, $context);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function debug(string $message, array $context = []): void {
		$this->log('debug', $message, $context);
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function log(string $level, string $message, array $context = []): void {
		if (! in_array($level, self::LEVELS, true)) {
			$level = 'info';
		}

		if ('debug' === $level && ! $this->is_debug_enabled()) {
			return;
		}

		if (! $this->file_guard->ensure_directory(WPXCACHE_LOG_DIR)) {
			return;
		}

		$file = WPXCACHE_LOG_DIR . '/wpxcache-' . gmdate('Y-m-d') . '.log';

		if (! $this->path_validator->is_within($file, WPXCACHE_LOG_DIR)) {
			return;
		}

		$line = wp_json_encode(
			[
				'time'    => gmdate('c'),
				'level'   => $level,
				'message' => $this->mask($message),
				'context' => $this->mask_context($context),
			],
			JSON_UNESCAPED_SLASHES
		);

		if (! is_string($line)) {
			return;
		}

		file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function recent(int $limit = 10): array {
		$limit = max(1, min(50, $limit));
		$file = WPXCACHE_LOG_DIR . '/wpxcache-' . gmdate('Y-m-d') . '.log';

		if (! is_readable($file) || ! $this->path_validator->is_within($file, WPXCACHE_LOG_DIR)) {
			return [];
		}

		$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if (! is_array($lines)) {
			return [];
		}

		$lines = array_slice($lines, -$limit);
		$items = [];

		foreach ($lines as $line) {
			$decoded = json_decode($line, true);

			if (is_array($decoded)) {
				$items[] = $decoded;
			}
		}

		return array_reverse($items);
	}

	private function is_debug_enabled(): bool {
		$settings = get_option('wpxcache_settings', []);

		return is_array($settings) && ! empty($settings['advanced']['debug_mode']);
	}

	/**
	 * @param array<string, mixed> $context
	 * @return array<string, mixed>
	 */
	private function mask_context(array $context): array {
		$masked = [];

		foreach ($context as $key => $value) {
			$key = sanitize_key((string) $key);

			if ($this->is_sensitive_key($key)) {
				$masked[$key] = '[masked]';
				continue;
			}

			if (is_array($value)) {
				$masked[$key] = $this->mask_context($value);
				continue;
			}

			$masked[$key] = is_scalar($value) ? $this->mask((string) $value) : '[non-scalar]';
		}

		return $masked;
	}

	private function mask(string $value): string {
		$value = preg_replace('/([?&](?:token|key|nonce|password|secret|api_key|apikey)=)[^&\s]+/i', '$1[masked]', $value);
		$value = preg_replace('/(Authorization:\s*Bearer\s+)[A-Za-z0-9\-\._~\+\/]+=*/i', '$1[masked]', (string) $value);

		return is_string($value) ? $value : '';
	}

	private function is_sensitive_key(string $key): bool {
		return (bool) preg_match('/token|key|nonce|password|secret|cookie|authorization|api/i', $key);
	}
}
