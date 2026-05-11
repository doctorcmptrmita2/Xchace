<?php
/**
 * Conservative JavaScript optimization.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Optimization;

use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Logger\Logger;
use WPXCache\Security\FileGuard;

if (! defined('ABSPATH')) {
	exit;
}

final class JsOptimizer implements ServiceProvider {
	private AssetPathResolver $resolver;
	private OptimizationRules $rules;
	private Logger $logger;

	/**
	 * @param array<string, mixed>|null $settings
	 */
	public function __construct(private ?array $settings = null, ?AssetPathResolver $resolver = null, ?OptimizationRules $rules = null, ?Logger $logger = null) {
		$this->settings = $settings ?: Config::settings();
		$this->resolver = $resolver ?: new AssetPathResolver();
		$this->rules = $rules ?: new OptimizationRules($this->settings);
		$this->logger = $logger ?: new Logger();
	}

	public function register(): void {
		if (is_admin() || wp_doing_ajax()) {
			return;
		}

		if (! empty($this->settings['optimization']['minify_js'])) {
			add_filter('script_loader_src', [$this, 'minify_src'], 20, 2);
		}

		if (! empty($this->settings['optimization']['defer_js'])) {
			add_filter('script_loader_tag', [$this, 'defer_tag'], 20, 3);
		}
	}

	public function minify_src(string $src, string $handle): string {
		if ($this->rules->is_woocommerce_flow() || $this->rules->is_excluded('js', $handle, $src) || preg_match('/\.min\.js(?:$|\?)/i', $src)) {
			return $src;
		}

		$file = $this->resolver->local_file_for_url($src, ['js']);

		if (null === $file || ! $this->is_reasonable_size($file)) {
			return $src;
		}

		$filename = $this->cache_filename($file, 'js');
		$cache_path = $this->resolver->cache_path('js', $filename);

		if (! is_file($cache_path)) {
			$contents = file_get_contents($file);

			if (! is_string($contents)) {
				return $src;
			}

			$minified = $this->compact_js($contents);

			if (! $this->resolver->ensure_cache_directory('js') || ! (new FileGuard())->write_cache_file($cache_path, $minified)) {
				$this->logger->warning('JS minify cache write failed.', ['handle' => $handle]);
				return $src;
			}
		}

		return esc_url_raw(add_query_arg('ver', (string) filemtime($file), $this->resolver->cache_url('js', $filename)));
	}

	public function defer_tag(string $tag, string $handle, string $src): string {
		if ($this->rules->is_woocommerce_flow() || $this->rules->is_excluded('js', $handle, $src)) {
			return $tag;
		}

		if ('' === $src || false === stripos($tag, '<script') || false === stripos($tag, ' src=')) {
			return $tag;
		}

		foreach ([' defer', ' async', 'type="module"', "type='module'", ' nomodule'] as $needle) {
			if (false !== stripos($tag, $needle)) {
				return $tag;
			}
		}

		return preg_replace('/<script\b(?![^>]*\bdefer\b)/i', '<script defer', $tag, 1) ?: $tag;
	}

	public function compact_js(string $js): string {
		$lines = preg_split('/\r\n|\r|\n/', $js);

		if (! is_array($lines)) {
			return $js;
		}

		$output = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ('' === $line || preg_match('/^\/\/[#@]\s*sourceMappingURL=/i', $line)) {
				continue;
			}

			if (str_starts_with($line, '//') && ! str_starts_with($line, '//!') && ! str_starts_with($line, '//@')) {
				continue;
			}

			$output[] = $line;
		}

		$js = implode("\n", $output);

		return (string) apply_filters('wpxcache_minified_js', $js);
	}

	private function cache_filename(string $file, string $extension): string {
		$hash = md5(wp_normalize_path($file) . '|' . (string) filemtime($file) . '|' . (string) filesize($file) . '|' . WPXCACHE_VERSION);

		return $hash . '.min.' . $extension;
	}

	private function is_reasonable_size(string $file): bool {
		$size = filesize($file);

		return is_int($size) && $size > 0 && $size <= 2097152;
	}
}
