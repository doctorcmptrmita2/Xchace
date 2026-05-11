<?php
/**
 * Conservative HTML minifier.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class HtmlMinifier implements ServiceProvider {
	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, private ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		add_action('template_redirect', [$this, 'maybe_start_buffer'], 20);
	}

	public function maybe_start_buffer(): void {
		if (empty($this->settings['optimization']['minify_html']) || is_admin() || wp_doing_ajax() || is_feed() || is_preview()) {
			return;
		}

		if (headers_sent()) {
			return;
		}

		ob_start([$this, 'minify']);
	}

	public function minify(string $html): string {
		if ('' === trim($html) || false === stripos($html, '<html')) {
			return $html;
		}

		$tokens = [];
		$index = 0;

		$html = preg_replace_callback(
			'/<(script|style|textarea|pre)\b[^>]*>.*?<\/\1>/is',
			static function (array $matches) use (&$tokens, &$index): string {
				$key = '%%WPXCACHE_HTML_BLOCK_' . $index++ . '%%';
				$tokens[$key] = $matches[0];
				return $key;
			},
			$html
		);

		if (! is_string($html)) {
			return '';
		}

		$html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);
		$html = preg_replace('/>\s+</', '><', (string) $html);
		$html = preg_replace('/[ \t]{2,}/', ' ', (string) $html);

		if (! is_string($html)) {
			return '';
		}

		foreach ($tokens as $key => $block) {
			$html = str_replace($key, $block, $html);
		}

		$this->logger->debug('HTML minified.');

		return trim($html);
	}
}
