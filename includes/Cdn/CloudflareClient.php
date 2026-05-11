<?php
/**
 * Cloudflare API client.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Cdn;

use WPXCache\Logger\Logger;

if (! defined('ABSPATH')) {
	exit;
}

final class CloudflareClient {
	public function __construct(private string $api_token, private string $zone_id, private ?Logger $logger = null) {
		$this->logger = $logger ?: new Logger();
	}

	/**
	 * @param array<int, string> $urls
	 * @return array{success: bool, message: string}
	 */
	public function purge(array $urls = []): array {
		if ('' === $this->api_token || '' === $this->zone_id) {
			return $this->result(false, __('Cloudflare API token or Zone ID is missing.', 'wpxcache'));
		}

		$endpoint = 'https://api.cloudflare.com/client/v4/zones/' . rawurlencode($this->zone_id) . '/purge_cache';
		$body = [] === $urls ? ['purge_everything' => true] : ['files' => array_values(array_unique(array_map('esc_url_raw', $urls)))];

		$response = wp_remote_post(
			$endpoint,
			[
				'timeout' => 12,
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_token,
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode($body),
			]
		);

		if (is_wp_error($response)) {
			$this->logger->error('Cloudflare purge request failed.', ['error' => $response->get_error_message()]);
			return $this->result(false, __('Cloudflare purge request failed.', 'wpxcache'));
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$payload = json_decode(wp_remote_retrieve_body($response), true);
		$success = $code >= 200 && $code < 300 && is_array($payload) && ! empty($payload['success']);

		$this->logger->info('Cloudflare purge completed.', ['success' => $success, 'status' => $code]);

		return $this->result($success, $success ? __('Cloudflare cache purge completed.', 'wpxcache') : __('Cloudflare cache purge failed.', 'wpxcache'));
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
