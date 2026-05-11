<?php
/**
 * Normalized request data for cache decisions.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

if (! defined('ABSPATH')) {
	exit;
}

final class RequestContext {
	/**
	 * @param array<string, string> $query
	 * @param array<string, string> $cookies
	 * @param array<string, string> $headers
	 */
	public function __construct(
		private string $method,
		private string $scheme,
		private string $host,
		private string $path,
		private array $query,
		private array $cookies,
		private array $headers,
		private string $user_agent
	) {
	}

	public static function from_globals(): self {
		$server  = wp_unslash($_SERVER);
		$get     = wp_unslash($_GET);
		$cookies = wp_unslash($_COOKIE);

		$https  = isset($server['HTTPS']) && 'off' !== strtolower((string) $server['HTTPS']);
		$scheme = $https ? 'https' : 'http';
		$host   = isset($server['HTTP_HOST']) ? sanitize_text_field((string) $server['HTTP_HOST']) : wp_parse_url(home_url(), PHP_URL_HOST);
		$uri    = isset($server['REQUEST_URI']) ? (string) $server['REQUEST_URI'] : '/';
		$path   = wp_parse_url($uri, PHP_URL_PATH);

		if (! is_string($host) || '' === $host) {
			$host = 'localhost';
		}

		if (! is_string($path) || '' === $path) {
			$path = '/';
		}

		return new self(
			isset($server['REQUEST_METHOD']) ? strtoupper(sanitize_text_field((string) $server['REQUEST_METHOD'])) : 'GET',
			$scheme,
			strtolower($host),
			'/' . ltrim(sanitize_text_field(rawurldecode($path)), '/'),
			self::sanitize_string_map($get),
			self::sanitize_string_map($cookies),
			self::headers_from_server($server),
			isset($server['HTTP_USER_AGENT']) ? sanitize_text_field((string) $server['HTTP_USER_AGENT']) : ''
		);
	}

	public static function from_url(string $url): ?self {
		$url = esc_url_raw($url);

		if ('' === $url || ! wp_http_validate_url($url)) {
			return null;
		}

		$parts = wp_parse_url($url);

		if (! is_array($parts) || empty($parts['host'])) {
			return null;
		}

		$query = [];

		if (! empty($parts['query']) && is_string($parts['query'])) {
			parse_str($parts['query'], $query);
		}

		return new self(
			'GET',
			isset($parts['scheme']) && 'http' === strtolower((string) $parts['scheme']) ? 'http' : 'https',
			strtolower((string) $parts['host']),
			isset($parts['path']) ? '/' . ltrim(sanitize_text_field(rawurldecode((string) $parts['path'])), '/') : '/',
			self::sanitize_string_map(is_array($query) ? $query : []),
			[],
			[],
			''
		);
	}

	public function method(): string {
		return $this->method;
	}

	public function scheme(): string {
		return $this->scheme;
	}

	public function host(): string {
		return $this->host;
	}

	public function path(): string {
		return $this->path;
	}

	/**
	 * @return array<string, string>
	 */
	public function query(): array {
		return $this->query;
	}

	/**
	 * @return array<string, string>
	 */
	public function cookies(): array {
		return $this->cookies;
	}

	/**
	 * @return array<string, string>
	 */
	public function headers(): array {
		return $this->headers;
	}

	public function user_agent(): string {
		return $this->user_agent;
	}

	public function url(): string {
		$query = $this->query_string($this->query);

		return $this->scheme . '://' . $this->host . $this->path . ('' !== $query ? '?' . $query : '');
	}

	/**
	 * @param array<string, string> $query
	 */
	public function query_string(array $query): string {
		if ([] === $query) {
			return '';
		}

		ksort($query);

		return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
	}

	/**
	 * @param array<string, mixed> $values
	 * @return array<string, string>
	 */
	private static function sanitize_string_map(array $values): array {
		$sanitized = [];

		foreach ($values as $key => $value) {
			if (! is_scalar($value)) {
				continue;
			}

			$sanitized[sanitize_key((string) $key)] = sanitize_text_field((string) $value);
		}

		return $sanitized;
	}

	/**
	 * @param array<string, mixed> $server
	 * @return array<string, string>
	 */
	private static function headers_from_server(array $server): array {
		$headers = [];

		foreach ($server as $key => $value) {
			if (! is_scalar($value) || 0 !== strpos((string) $key, 'HTTP_')) {
				continue;
			}

			$header = strtolower(str_replace('_', '-', substr((string) $key, 5)));
			$headers[$header] = sanitize_text_field((string) $value);
		}

		return $headers;
	}
}
