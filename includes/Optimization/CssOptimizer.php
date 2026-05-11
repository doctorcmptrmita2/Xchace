<?php
/**
 * Conservative CSS optimization.
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

final class CssOptimizer implements ServiceProvider {
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

		/**
		 * CSS URL rewriting is intentionally opt-in at runtime. A blocked public
		 * asset cache path can remove all theme styles, so safe mode keeps the
		 * saved settings visible without touching frontend CSS delivery.
		 *
		 * @param bool $enabled Whether CSS runtime optimization is enabled.
		 */
		$runtime_enabled = (bool) apply_filters('wpxcache_enable_css_runtime_optimization', false);

		if (! $runtime_enabled) {
			return;
		}

		if (! empty($this->settings['optimization']['minify_css'])) {
			add_filter('style_loader_src', [$this, 'minify_src'], 20, 2);
		}

		/**
		 * CSS defer can cause visible layout breakage without Critical CSS.
		 *
		 * @param bool $enabled Whether CSS defer is allowed.
		 */
		$defer_enabled = (bool) apply_filters('wpxcache_enable_css_defer_runtime', false);

		if ($defer_enabled && ! empty($this->settings['optimization']['defer_css'])) {
			add_filter('style_loader_tag', [$this, 'defer_tag'], 20, 4);
		}
	}

	public function minify_src(string $src, string $handle): string {
		if ($this->rules->is_excluded('css', $handle, $src) || preg_match('/\.min\.css(?:$|\?)/i', $src)) {
			return $src;
		}

		$file = $this->resolver->local_file_for_url($src, ['css']);

		if (null === $file || ! $this->is_reasonable_size($file)) {
			return $src;
		}

		$filename = $this->cache_filename($file, 'css');
		$cache_path = $this->resolver->cache_path('css', $filename);

		if (! is_file($cache_path)) {
			$contents = file_get_contents($file);

			if (! is_string($contents)) {
				return $src;
			}

			$minified = $this->minify_css($contents);

			if (! $this->resolver->ensure_cache_directory('css') || ! (new FileGuard())->write_cache_file($cache_path, $minified)) {
				$this->logger->warning('CSS minify cache write failed.', ['handle' => $handle]);
				return $src;
			}
		}

		return esc_url_raw(add_query_arg('ver', (string) filemtime($file), $this->resolver->cache_url('css', $filename)));
	}

	public function defer_tag(string $html, string $handle, string $href, string $media): string {
		if ($this->rules->is_woocommerce_flow() || $this->rules->is_excluded('css', $handle, $href)) {
			return $html;
		}

		if ('' !== $media && 'all' !== strtolower($media)) {
			return $html;
		}

		if (false === stripos($html, 'stylesheet') || false !== stripos($html, 'rel="preload"') || false !== stripos($html, "rel='preload'")) {
			return $html;
		}

		$href = esc_url($href);

		if ('' === $href) {
			return $html;
		}

		return '<link rel="preload" as="style" href="' . $href . '" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n" . '<noscript>' . $html . '</noscript>';
	}

	public function minify_css(string $css): string {
		$css = preg_replace('!/\*(?!\!)(?:.|\r|\n)*?\*/!', '', $css);
		$css = is_string($css) ? preg_replace('/\s+/', ' ', $css) : '';
		$css = is_string($css) ? preg_replace('/\s*([{}:;,>])\s*/', '$1', $css) : '';
		$css = is_string($css) ? str_replace(';}', '}', $css) : '';

		return trim((string) apply_filters('wpxcache_minified_css', $css));
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
