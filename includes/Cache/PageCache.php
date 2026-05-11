<?php
/**
 * WordPress-loaded page cache generator.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cache;

use WPXCache\Core\ServiceProvider;

if (! defined('ABSPATH')) {
	exit;
}

final class PageCache implements ServiceProvider {
	private ?RequestContext $request = null;
	private bool $should_cache = false;

	public function __construct(
		private ?CacheRules $rules = null,
		private ?CacheWriter $writer = null
	) {
		$this->rules  = $rules ?: new CacheRules();
		$this->writer = $writer ?: new CacheWriter();
	}

	public function register(): void {
		add_action('template_redirect', [$this, 'maybe_start_buffer'], 0);
	}

	public function maybe_start_buffer(): void {
		$this->request = RequestContext::from_globals();
		$this->should_cache = $this->rules->should_cache($this->request);

		if (! $this->should_cache || is_admin()) {
			return;
		}

		if (headers_sent()) {
			return;
		}

		ob_start([$this, 'capture']);
	}

	public function capture(string $html): string {
		if (! $this->should_cache || null === $this->request || ! $this->rules->is_serveable_response()) {
			return $html;
		}

		$this->writer->write($this->request, $html);

		if (! headers_sent()) {
			header('X-WPXCache: MISS');
		}

		return $html;
	}
}
