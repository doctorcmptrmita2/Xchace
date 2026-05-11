<?php
/**
 * REST API controller.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WPXCache\Cache\CachePreloader;
use WPXCache\Cache\CachePurger;
use WPXCache\Cache\CacheStorage;
use WPXCache\Core\Config;
use WPXCache\Core\ServiceProvider;
use WPXCache\Diagnostics\DiagnosticsReport;
use WPXCache\Security\Capability;

if (! defined('ABSPATH')) {
	exit;
}

final class RestController implements ServiceProvider {
	private const NAMESPACE = 'wpxcache/v1';

	public function register(): void {
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/status',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'status'],
				'permission_callback' => [$this, 'can_manage'],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/purge',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'purge'],
				'permission_callback' => [$this, 'can_manage'],
				'args'                => [
					'url' => [
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
						'required'          => false,
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/preload',
			[
				'methods'             => 'POST',
				'callback'            => [$this, 'preload'],
				'permission_callback' => [$this, 'can_manage'],
				'args'                => [
					'urls' => [
						'type'     => 'array',
						'required' => false,
						'items'    => [
							'type' => 'string',
						],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/diagnostics',
			[
				'methods'             => 'GET',
				'callback'            => [$this, 'diagnostics'],
				'permission_callback' => [$this, 'can_manage'],
			]
		);
	}

	public function can_manage(): bool {
		return Capability::can_manage();
	}

	public function status(): WP_REST_Response {
		$settings = Config::settings();
		$storage = new CacheStorage();
		$preloader = new CachePreloader($settings);

		return $this->response(
			[
				'enabled'          => ! empty($settings['cache']['enabled']),
				'cached_pages'     => $storage->html_file_count(),
				'cache_size_bytes' => $storage->size_bytes(),
				'last_purge'       => (int) get_option('wpxcache_last_purge', 0),
				'last_preload'     => (int) get_option('wpxcache_last_preload', 0),
				'preload'          => $preloader->state(),
			]
		);
	}

	public function purge(WP_REST_Request $request): WP_REST_Response {
		$url = $request->get_param('url');
		$purger = new CachePurger();
		$success = is_string($url) && '' !== $url ? $purger->purge_url($url) : $purger->purge_all();

		return $this->response(
			[
				'success' => $success,
				'message' => $success ? __('Cache purge completed.', 'wpxcache') : __('Cache purge failed.', 'wpxcache'),
			],
			$success ? 200 : 500
		);
	}

	public function preload(WP_REST_Request $request): WP_REST_Response {
		$settings = Config::settings();
		$preloader = new CachePreloader($settings);
		$urls = $request->get_param('urls');
		$urls = is_array($urls) ? array_values(array_filter($urls, 'is_scalar')) : [];
		$urls = array_map(static fn ($url): string => esc_url_raw((string) $url), $urls);
		$result = $preloader->start($preloader->build_urls($urls));

		return $this->response($result, $result['success'] ? 200 : 400);
	}

	public function diagnostics(): WP_REST_Response {
		return $this->response((new DiagnosticsReport())->generate());
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function response(array $data, int $status = 200): WP_REST_Response {
		return new WP_REST_Response($data, $status);
	}
}
