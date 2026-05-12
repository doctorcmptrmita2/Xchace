<?php
/**
 * Database admin page.
 *
 * @package WPXCache
 */

declare(strict_types=1);

namespace WPXCache\Admin;

use WPXCache\Database\DatabaseCleaner;
use WPXCache\Security\Capability;
use WPXCache\Security\Nonce;

if (! defined('ABSPATH')) {
	exit;
}

final class DatabasePage {
	public function render(): void {
		Capability::require_manage();

		$cleaner = new DatabaseCleaner();
		$notice = $this->handle_action($cleaner);
		$counts = $cleaner->counts();
		$risk_items = RiskRegistry::items('database', $counts);

		require WPXCACHE_PATH . 'templates/admin/database.php';
	}

	private function handle_action(DatabaseCleaner $cleaner): ?array {
		$action = filter_input(INPUT_POST, 'wpxcache_action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if ('clean_database' !== $action) {
			return null;
		}

		if (! Nonce::verify_request()) {
			return [
				'type'    => 'error',
				'message' => __('Security check failed. Please refresh the page and try again.', 'wpxcache'),
			];
		}

		$target = filter_input(INPUT_POST, 'cleanup_target', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$result = $cleaner->clean(is_string($target) ? $target : '');

		return [
			'type'    => $result['success'] ? 'success' : 'error',
			'message' => sprintf(
				/* translators: 1: cleanup message, 2: cleaned item count */
				__('%1$s Cleaned items: %2$d.', 'wpxcache'),
				$result['message'],
				$result['cleaned']
			),
		];
	}
}
