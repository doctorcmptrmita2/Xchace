<?php
/**
 * WP XCache Pro advanced-cache.php drop-in.
 *
 * @package WPXCache
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! defined('WP_CACHE') || ! WP_CACHE) {
	return;
}

if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
	return;
}

$wpxcache_config = __DIR__ . '/cache/wpxcache/dropin-config.php';

if (! is_file($wpxcache_config) || is_link($wpxcache_config) || ! is_readable($wpxcache_config)) {
	if (! headers_sent()) {
		header('X-WPXCache: MISS');
	}
	return;
}

$wpxcache_settings = require $wpxcache_config;

if (! is_array($wpxcache_settings) || empty($wpxcache_settings['enabled']) || empty($wpxcache_settings['cache_dir'])) {
	if (! headers_sent()) {
		header('X-WPXCache: MISS');
	}
	return;
}

$wpxcache_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

if ('GET' !== $wpxcache_method) {
	return;
}

$wpxcache_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$wpxcache_path = parse_url($wpxcache_uri, PHP_URL_PATH);

if (! is_string($wpxcache_path) || '' === $wpxcache_path) {
	$wpxcache_path = '/';
}

$wpxcache_query = [];
$wpxcache_query_string = parse_url($wpxcache_uri, PHP_URL_QUERY);

if (is_string($wpxcache_query_string) && '' !== $wpxcache_query_string) {
	parse_str($wpxcache_query_string, $wpxcache_query);
}

$wpxcache_sensitive_query = ['add-to-cart', 'preview', 's', 'wc-ajax', 'order', 'token', 'key', 'nonce', '_wpnonce'];

foreach ($wpxcache_sensitive_query as $wpxcache_key) {
	if (array_key_exists($wpxcache_key, $wpxcache_query)) {
		return;
	}
}

$wpxcache_query_whitelist = isset($wpxcache_settings['query_string_whitelist']) && is_array($wpxcache_settings['query_string_whitelist'])
	? array_map('strval', $wpxcache_settings['query_string_whitelist'])
	: [];

if ([] !== $wpxcache_query) {
	foreach (array_keys($wpxcache_query) as $wpxcache_query_key) {
		if (! in_array((string) $wpxcache_query_key, $wpxcache_query_whitelist, true)) {
			return;
		}
	}

	return;
}

$wpxcache_cookie_prefixes = isset($wpxcache_settings['never_cache_cookies']) && is_array($wpxcache_settings['never_cache_cookies'])
	? $wpxcache_settings['never_cache_cookies']
	: [];

foreach (array_keys($_COOKIE) as $wpxcache_cookie) {
	foreach ($wpxcache_cookie_prefixes as $wpxcache_prefix) {
		if (is_string($wpxcache_prefix) && 0 === strpos((string) $wpxcache_cookie, $wpxcache_prefix)) {
			return;
		}
	}
}

$wpxcache_excluded_urls = isset($wpxcache_settings['never_cache_urls']) && is_array($wpxcache_settings['never_cache_urls'])
	? $wpxcache_settings['never_cache_urls']
	: [];

$wpxcache_excluded_agents = isset($wpxcache_settings['never_cache_user_agents']) && is_array($wpxcache_settings['never_cache_user_agents'])
	? $wpxcache_settings['never_cache_user_agents']
	: [];

$wpxcache_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

foreach ($wpxcache_excluded_agents as $wpxcache_agent) {
	if (is_string($wpxcache_agent) && '' !== $wpxcache_agent && false !== stripos($wpxcache_user_agent, $wpxcache_agent)) {
		return;
	}
}

$wpxcache_normalized_path = '/' . trim(rawurldecode($wpxcache_path), '/');
$wpxcache_normalized_path = '/' === $wpxcache_normalized_path ? '/' : rtrim($wpxcache_normalized_path, '/');

foreach ($wpxcache_excluded_urls as $wpxcache_pattern) {
	if (! is_string($wpxcache_pattern) || '' === $wpxcache_pattern) {
		continue;
	}

	$wpxcache_pattern = '/' . trim($wpxcache_pattern, '/');

	if ($wpxcache_pattern === $wpxcache_normalized_path || 0 === strpos($wpxcache_normalized_path . '/', rtrim($wpxcache_pattern, '/') . '/')) {
		return;
	}
}

$wpxcache_cache_dir = (string) $wpxcache_settings['cache_dir'];
$wpxcache_real_cache_dir = realpath($wpxcache_cache_dir);

if (false === $wpxcache_real_cache_dir || ! is_dir($wpxcache_real_cache_dir)) {
	return;
}

$wpxcache_https = isset($_SERVER['HTTPS']) && 'off' !== strtolower((string) $_SERVER['HTTPS']);
$wpxcache_host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : 'localhost';
$wpxcache_host = preg_replace('/[^a-z0-9\.\-:]/i', '', $wpxcache_host);
$wpxcache_host = '' === $wpxcache_host ? 'localhost' : $wpxcache_host;
$wpxcache_host_dir = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $wpxcache_host);
$wpxcache_path_part = trim($wpxcache_normalized_path, '/');

if ('' === $wpxcache_path_part) {
	$wpxcache_path_part = 'home';
}

$wpxcache_segments = array_filter(explode('/', $wpxcache_path_part), static function ($segment) {
	return '' !== $segment && false === strpos($segment, '..');
});

$wpxcache_segments = array_map(static function ($segment) {
	$segment = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $segment);
	return '' === $segment ? 'page' : $segment;
}, $wpxcache_segments);

$wpxcache_relative = $wpxcache_host_dir . '/' . implode('/', $wpxcache_segments) . '/index.html';
$wpxcache_file = $wpxcache_real_cache_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $wpxcache_relative);
$wpxcache_real_file = realpath($wpxcache_file);

if (false === $wpxcache_real_file || is_link($wpxcache_real_file) || ! is_readable($wpxcache_real_file)) {
	if (! headers_sent()) {
		header('X-WPXCache: MISS');
	}
	return;
}

$wpxcache_base = rtrim(str_replace('\\', '/', $wpxcache_real_cache_dir), '/') . '/';
$wpxcache_target = str_replace('\\', '/', $wpxcache_real_file);

if (0 !== strpos($wpxcache_target, $wpxcache_base)) {
	return;
}

$wpxcache_gzip_file = $wpxcache_real_file . '.gz';
$wpxcache_accepts_gzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && false !== stripos((string) $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');

if ($wpxcache_accepts_gzip && is_file($wpxcache_gzip_file) && ! is_link($wpxcache_gzip_file) && is_readable($wpxcache_gzip_file)) {
	$wpxcache_real_gzip = realpath($wpxcache_gzip_file);

	if (is_string($wpxcache_real_gzip) && 0 === strpos(str_replace('\\', '/', $wpxcache_real_gzip), $wpxcache_base)) {
		if (! headers_sent()) {
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			header('Content-Type: text/html; charset=UTF-8');
			header('Cache-Control: public, max-age=0, must-revalidate');
			header('X-WPXCache: HIT');
		}

		readfile($wpxcache_real_gzip);
		exit;
	}
}

if (! headers_sent()) {
	header('Content-Type: text/html; charset=UTF-8');
	header('Cache-Control: public, max-age=0, must-revalidate');
	header('X-WPXCache: HIT');
}

readfile($wpxcache_real_file);
exit;
